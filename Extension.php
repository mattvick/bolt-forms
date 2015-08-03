<?php

namespace Bolt\Extension\Mahango\Forms;

use Bolt\Application;
use Bolt\BaseExtension;
use Bolt\Extension\Mahango\Forms\Provider\MahangoFormsServiceProvider;
use Bolt\Extension\Mahango\Forms\Form\TypeExtension\SubmittedFormEventTypeExtension;
use Bolt\Extension\Mahango\Forms\Twig\FormsExtension;
use Bolt\Extension\Mahango\Forms\Field\FormSelectField;
use Bolt\Extension\Mahango\Forms\Field\FormTemplateSelectField;
use Bolt\Extension\Mahango\Forms\Field\EmailTemplateSelectField;
use Bolt\Extension\Mahango\Forms\Field\ContentTypeSelectField;
use Symfony\Component\Form\FormEvents;
use Silex\Provider\ValidatorServiceProvider;

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
class Extension extends BaseExtension
{
    /**
     * Extension name
     *
     * @var string
     */
    const NAME = 'MahangoForms';

    /**
     * Extension's service container
     *
     * @var string
     */
    const CONTAINER = 'extensions.MahangoForms';

    public function __construct(Application $app)
    {
        parent::__construct($app);

        /*
         * Custom fields
         */
        $this->app['config']->getFields()->addField(new FormSelectField());
        $this->app['config']->getFields()->addField(new FormTemplateSelectField());
        $this->app['config']->getFields()->addField(new EmailTemplateSelectField());
        $this->app['config']->getFields()->addField(new ContentTypeSelectField());

        /*
         * Twig templates
         */
        $this->app['twig.loader.filesystem']->prependPath(__DIR__."/twig/fields");
        $this->app['twig.loader.filesystem']->prependPath(__DIR__."/twig/forms");
        $this->app['twig.loader.filesystem']->prependPath(__DIR__."/twig/emails");
        $this->app['twig.loader.filesystem']->prependPath(__DIR__."/twig/form_themes");
    }

    public function getName()
    {
        return Extension::NAME;
    }

    public function initialize() 
    {
        // $this->addCss('assets/extension.css');
        // $this->addJavascript('assets/start.js', true);

        /*
         * Provider
         */
        $this->app->register(new MahangoFormsServiceProvider($this->app));
        $this->app->register(new ValidatorServiceProvider());

        /*
         * Twig extension
         */
        $this->app['twig']->addExtension(new FormsExtension($this->app));

        /*
         * Storage events
         */
        $this->app['dispatcher']->addListener(FormEvents::POST_SUBMIT, array($this->app['mahangoforms.submittedlistener'], 'postSubmit'));
    }
}






