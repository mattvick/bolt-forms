<?php

namespace Bolt\Extension\Mahango\Forms;

use Bolt;
use Silex\Application;

use Exception;
use Twig_Markup;
use Swift_Message;
use Swift_Validate;
// use Symfony\Component\Validator\Constraints as Assert;

/**
 * Core API functions for MahangoForms
 *
 * Copyright (C) 2014
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Gawain Lynch <gawain.lynch@gmail.com>
 * @author    Matthew Vickery <vickery.matthew@gmail.com>
 * @copyright Copyright (c) 2014
 * @license   http://opensource.org/licenses/GPL-3.0 GNU Public License 3.0
 */
class Email
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $config;

    /**
     * \Swift_Message
     */
    private $message;

    public function __construct(Application $app)
    {
        $this->app = $this->config = $app;
        $this->config = $app[Extension::CONTAINER]->config;
    }

    /**
     *
     */
    public function doNotification($notification, $formdata, $config = null)
    {
        // $emailconfig = $this->getEmailConfig($formname, $formdata);

        //
        $this->doCompose($notification, $formdata, $config = null);

        //
        $this->doAddress($notification, $formdata, $config = null);

        //
        $this->doSend();
    }

    /**
     * Compose the email data to be sent
     */
    private function doCompose($notification, $formdata, $config = null)
    {
        /*
         * Subject
         *
         * TODO: move to a method and write tests
         */
        if (!empty($notification['subject'])) {
            $subject = (string) $notification['subject'];
        } else {
            // TODO: get a default subject from somewhere
            throw new Exception('A "Subject" must be set in the form notification.', 1);
        }

        /*
         * Body
         *
         * TODO: move to a method and write tests
         */
        // render the html email body with the form data
        // var_dump($formdata);
        // $body = $this->app['safe_render']->render($notification['body_html'], array(
        //     'data' => $formdata,
        // ));

        $body = $this->app['safe_render']->render($notification['body_html'], $formdata);

        // var_dump($body);
        $body = new Twig_Markup($body, 'UTF-8');
        // var_dump($body);

        // insert the body into an email layout template if one was selected
        if (!empty($notification['template_html'])) {

            // Set our Twig lookup path
            $this->addTwigPath();

            $body = $this->app['render']->render($notification['template_html'], array(
                'body' => $body,
            ));
            $body = new Twig_Markup($body, 'UTF-8');
        }

        /*
         * Build email
         */
        $this->message = Swift_Message::newInstance()
                ->setSubject($subject)
                ->setBody(strip_tags($body))
                ->addPart($body, 'text/html');
    }

    /**
     * Set the addresses
     *
     * @param array $emailconfig
     */
    private function doAddress($notification, $formdata, $config = null)
    {
        /*
         * From address
         *
         * TODO: move to a method and write tests
         */
        if (!empty($notification['from_address'])) {
            $fromAddress = (string) $notification['from_address'];

            if (!Swift_Validate::email($fromAddress)) {
                throw new Exception('The provided "From address" is not a valid email address.', 1);
            }
        } else {
            // TODO: get a default subject from somewhere
            throw new Exception('A "From address" must be set in the form notification.', 1);
        }

        /*
         * From name
         *
         * TODO: move to a method and write tests
         * TODO: get a default from name from somewhere else
         */
        $fromName = isset($notification['from_name']) ? $notification['from_name'] : 'MahangoForms';

        /*
         * From
         */
        $this->message->setFrom(array($fromAddress => $fromName));


        /*
         * To name (search form data for 'firstname' and 'lastname' or simple 'name')
         *
         * TODO: move to a method and write tests
         * TODO: expand search and move to a method or helper class
         * TODO: Add ability to add multiple to addresses 
         * (^^^ not really necessary as we already have support for Cc and Bcc addresses)
         */
        $toName = (isset($formdata['firstname']) && isset($formdata['lastname'])) ? $formdata['firstname'].' '.$formdata['lastname'] : '';
        if (empty($toName)) {
            $toName = isset($formdata['name']) ? $formdata['name'] : '';
        }

        /*
         * To address
         *
         * TODO: move to a method and write tests
         */
        if (!empty($notification['to_address'])) {
            $toAddress = (string) $notification['to_address'];
        } else {
            throw new Exception('A "to" email address must be set in the form notification.', 1);
        }

        if (!Swift_Validate::email($toAddress)) {
            if (!isset($formdata[$toAddress])) {
                throw new Exception("No email address provided or found in the form data.", 1);
            }

            $toAddress = $formdata[$toAddress];
            if (!Swift_Validate::email($toAddress)) {
                throw new Exception("No vaild email address provided or found in the form data.", 1);
            }
        }

        /*
         * To
         */
        $this->message->setTo(array($toAddress => $toName));


        /*
         * Cc
         *
         * TODO: move to a method and reuse for Bcc
         * TODO: Write tests
         */
        if (!empty($notification['cc_addresses'])) {
            $addresses = (string) $notification['cc_addresses'];
            $addresses = explode(',', $notification['cc_addresses']);

            foreach ($addresses as $address) {
                $address = trim($address);

                if (empty($address)) {
                    continue;
                }

                if (!Swift_Validate::email($address)) {
                    continue;
                }

                $this->message->addCc($address);
            }
        }

        /*
         * Bcc
         */
        if (!empty($notification['bcc_addresses'])) {
            $addresses = (string) $notification['bcc_addresses'];
            $addresses = explode(',', $notification['bcc_addresses']);

            foreach ($addresses as $address) {
                $address = trim($address);

                if (empty($address)) {
                    continue;
                }

                if (!Swift_Validate::email($address)) {
                    continue;
                }

                $this->message->addBcc($address);
            }
        }
    }

    /**
     * Send a notification
     *
     * @param array $emailconfig
     */
    private function doSend($emailconfig = null)
    {
        if ($this->app['mailer']->send($this->message)) {
            // $this->app['logger.system']->info("Sent Mahango Forms notification to {$emailconfig['to_name']} <{$emailconfig['to_email']}>", array('event' => 'extensions'));
        } else {
            // $this->app['logger.system']->info("Failed Mahango Forms notification to {$emailconfig['to_name']} <{$emailconfig['to_email']}>", array('event' => 'extensions'));
        }
    }

    private function addTwigPath()
    {
        // Email template layout
        $this->app['twig.loader.filesystem']->addPath(dirname(__DIR__) . '/twig/emails/');
    }
}
