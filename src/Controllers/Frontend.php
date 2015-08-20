<?php

namespace Bolt\Extension\Mahango\Forms\Controllers;

use Bolt\Library as Lib;
use Silex;
use Bolt\Controllers\Frontend as BaseFrontend;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Bolt\Exception\StorageException;
use Twig_Error_Loader;

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
class Frontend extends BaseFrontend
{
    /**
     * Get this page's form \Bolt\Content object
     * 
     * @param \Bolt\Content         $content         The content
     *
     * @return mixed                \Bolt\Content|false
     */
    public function getRelatedForm($content)
    {
        $forms = $content->related('forms');

        if (1 != count($forms)) {
            // $app->abort(404, "Page $contenttypeslug/$slug not found.");
            return false;
        }

        return $forms[0];
    }

    /**
     * Build a fully qualified PSR-0 class name from a FormType's file name
     * 
     * @param string         $formTypeFileName         The file name of the FormType
     *
     * @return string
     */
    public function getFormClassName($formTypeFileName)
    {
        $namespace = "Bolt\\Extension\\Mahango\\Forms\\Form\\Type\\";
        $formType = str_replace('.php', '', $formTypeFileName);
        $className = $namespace.$formType;

        // TODO: test class is callabale
        // if (!$className...) {
        //     # code...
        // }

        return $className;
    }

    /**
     * Basic absolute url validation
     *
     * Test is a string is an absolute URL (i.e. it begins with 'http://' or 'https://')
     * 
     * TODO: improve
     *
     * @param string         $string         The string to test
     *
     * @return boolean
     */
    public function isAbsoluteUrl($string)
    {
        $absolute = false;
        $queries = array('http://', 'https://');
        foreach ($queries as $query) {
            if (substr($string, 0, strlen($query)) === $query) {
                $absolute = true;
                break;
            }
        }
        return $absolute;
    }

    /**
     * Controller for a single record page, like '/page/about/' or '/entry/lorum'.
     *
     * Added the Request object
     *
     * TODO: Move this and the render methods to a Controller class
     *
     * @param Request            $request         The request
     * @param \Silex\Application $app             The application/container
     * @param string             $contenttypeslug The content type slug
     * @param string             $slug            The content slug
     *
     * @return mixed
     */
    public function formPage(Request $request, Silex\Application $app, $contenttypeslug, $slug = '')
    {
        $contenttype = $app['storage']->getContentType($contenttypeslug);

        $content = $app['storage']->getContent(
            $contenttype['slug'], 
            array(
                'slug'          => $slug, 
                'returnsingle'  => true, 
                'log_not_found' => !is_numeric($slug)
            )
        );

        if (!$formContent = $this->getRelatedForm($content)) {
            $app->abort(404, "Page $contenttypeslug/$slug not found.");
        }

        // Make the form content object available to listners via the app object
        // TODO: find out if this is an okay thing to do
        $app['formcontent'] = $formContent;

        $className = $this->getFormClassName($formContent['type']);
        $form = $app['form.factory']->create(new $className());

        $form->handleRequest($request);

        if ($form->isValid()) {

            // redirect
            $redirect = (string) $formContent['redirect'];

            if (!$this->isAbsoluteUrl($redirect)) {
                $redirect = $app['url_generator']->generate($redirect);
            }

            return $app->redirect($redirect);
        }

        $app['twig']->addGlobal('form', $form->createView());
        $app['twig']->addGlobal('form_template', $formContent['template']);

        return parent::record($app, $contenttypeslug, $slug);
    }
}