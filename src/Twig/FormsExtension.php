<?php

namespace Bolt\Extension\Mahango\Forms\Twig;

use Silex\Application;
use Twig_Extension;
use Twig_Environment;
use Twig_SimpleFunction;

/**
 * Twig functions for MahangoForms
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
class FormsExtension extends Twig_Extension
{
    /** @var Application */
    private $app;

    /** @var \Twig_Environment */
    private $twig = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function initRuntime(Twig_Environment $environment)
    {
        $this->twig = $environment;
    }

    /**
     * Return the name of the extension
     */
    public function getName()
    {
        return 'mahangoforms.extension';
    }

    /**
     * The functions we add
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('listforms', array($this, 'listForms')),
            new Twig_SimpleFunction('listformtemplates', array($this, 'listFormTemplates')),
            new Twig_SimpleFunction('listemailtemplates', array($this, 'listEmailTemplates')),
            new Twig_SimpleFunction('listcontenttypes', array($this, 'listContentTypes')),
        );
    }

    /**
     * lists form, optionally filtered by $filter.
     *
     * @param string $filter
     *
     * @return array Sorted and possibly filtered templates
     */
    public function listForms($filter = '')
    {
        return $this->app['mahangoforms.finder']->listForms($filter);
    }

    /**
     * lists form, optionally filtered by $filter.
     *
     * @param string $filter
     *
     * @return array Sorted and possibly filtered templates
     */
    public function listFormTemplates($filter = '')
    {
        return $this->app['mahangoforms.finder']->listFormTemplates($filter);
    }

    /**
     * lists form, optionally filtered by $filter.
     *
     * @param string $filter
     *
     * @return array Sorted and possibly filtered templates
     */
    public function listEmailTemplates($filter = '')
    {
        return $this->app['mahangoforms.finder']->listEmailTemplates($filter);
    }

    /**
     * lists contenttypes, optionally filtered by $filter.
     *
     * @param string $filter
     *
     * @return array Sorted and possibly filtered templates
     */
    public function listContentTypes($filter = '')
    {
        $contenttypes = array();

        foreach ($this->app['config']->get('contenttypes') as $name => $contenttype) {
            $contenttypes[$name] = $name;
        }

        return $contenttypes;
    }
}
