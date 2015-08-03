<?php
namespace Bolt\Extension\Mahango\Forms\EventListener;

use Silex\Application;
use Symfony\Component\Form\FormEvent;

/**
 * Core API functions for MahangoForms
 *
 * Copyright (C) 2014 Matthew Vickery
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
 * @author    Matthew Vickery <vickery.matthew@gmail.com>
 * @copyright Copyright (c) 2014, Matthew Vickery
 * @license   http://opensource.org/licenses/GPL-3.0 GNU Public License 3.0
 */
class SubmittedFormListener 
{
    /**
     * @var Application
     */
    private $app;

    private $flashes;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->flashes = array();
    }

    public function postSubmit(FormEvent $event)
    {
        // Only act on a root form
        if (!$event->getForm()->isRoot()) {
            return;
        }

        // Only act if the form is valid
        if (!$event->getForm()->isValid()) {
            return;
        }

        $data = $event->getForm()->getData();

        $this->sendNotifications($this->replaceOptionValueWithText($event->getForm(), $data));

        $this->setFlashes();

        $this->writeToContentype($data);

        foreach ($this->flashes as $flash) {
            $this->app['session']->getFlashBag()->set($flash['type'], $flash['message']);
        }
    }

    // Replace 
    public function replaceOptionValueWithText($form, $data)
    {
        $array = array();
        foreach ($data as $key => $value) {
            $config = $form->get($key)->getConfig();
            if ('choice' == $config->getType()->getName()) {
                $choices = $config->getOption('choices');
                $array[$key] = $choices[$value];
            } else {
                $array[$key] = $value;
            }            
        }
        return $array;
    }

    // Send notification emails
    public function sendNotifications($data)
    {
        if ($notifications = $this->app['formcontent']->related('notifications')) {
            foreach ($notifications as $notification) {
                // if ('submission' == $notification['type']) {
                    $this->app['mahangoforms.email']->doNotification($notification, $data);
                // }
            }
        }
    }

    // Set flash messages
    public function setFlashes()
    {
        // Build success flash message
        $this->flashes[] = array(
            'message' => empty((string) $this->app['formcontent']['success_flash_message']) ? 'Thank you for your submission.' : (string) $this->app['formcontent']['success_flash_message'],
            'type' => 'success',
        );

        // Handle any other flash messages
        $flashTypes = array(
            'warning',
            'alert',
        );

        foreach ($flashTypes as $flashType) {
            if (empty((string) $this->app['formcontent'][$flashType.'_flash_message'])) {
                continue;
            }
            $this->flashes[] = array(
                'message' => (string) $this->app['formcontent'][$flashType.'_flash_message'],
                'type' => $flashType,
            );
        }
    }

    // Write data to content type
    public function writeToContentype($data)
    {
        if (!empty($this->app['formcontent']['write_to'])) {
            try {
                $this->app['mahangoforms.database']->writeToContentype($this->app['formcontent']['write_to'], $data);
            } catch (StorageException $e) {
                // replace all the flash messages with an error message
                $this->flashes = array(
                    array(
                        'message' => 'There was an error saving your enquiry.',
                        'type' => 'error',
                    )
                );
                // TODO: log StorageException $e
            }
        }
    }
}