<?php

namespace Bolt\Extension\Mahango\Forms\Provider;

use Bolt\Extension\Mahango\Forms\MahangoForms;
use Bolt\Extension\Mahango\Forms\Finder;
use Bolt\Extension\Mahango\Forms\Database;
use Bolt\Extension\Mahango\Forms\EventListener\SubmittedFormListener;
use Bolt\Extension\Mahango\Forms\Form\TypeExtension\SubmittedFormEventTypeExtension;
use Bolt\Extension\Mahango\Forms\Email;
use Silex\Application;
use Silex\ServiceProviderInterface;

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
class MahangoFormsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['mahangoforms'] = $app->share(
            function ($app) {
                $forms = new MahangoForms($app);

                return $forms;
            }
        );

        $app['mahangoforms.finder'] = $app->share(
            function () {
                $finder = new Finder();

                return $finder;
            }
        );

        $app['mahangoforms.database'] = $app->share(
            function ($app) {
                $database = new Database($app);

                return $database;
            }
        );

        $app['mahangoforms.email'] = $app->share(
            function ($app) {
                $email = new Email($app);

                return $email;
            }
        );

        $app['form.type.extensions'] = $app->share(
            $app->extend('form.type.extensions',
                function($extensions) use ($app) {
                    $extensions[] = new SubmittedFormEventTypeExtension($app);
                    return $extensions;
        }));

        $app['mahangoforms.submittedlistener'] = $app->share(
            function ($app) {
                $submittedlistener = new SubmittedFormListener($app);

                return $submittedlistener;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
