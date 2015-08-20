<?php

namespace Bolt\Extension\Mahango\Forms;

use Symfony\Component\Finder\Finder as BaseFinder;
use Symfony\Component\Finder\Glob;

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
class Finder
{
    /**
     * lists form files, optionally filtered by $filter.
     *
     * @param string $filter
     *
     * @return array Sorted and possibly filtered templates
     */
    public function listForms($filter = "")
    {
        // No need to list templates in safe mode.
        if ($this->safe) {
            return null;
        }
        if ($filter) {
            $name = Glob::toRegex($filter, false, false);
        } else {
            $name = '/^[a-zA-Z0-9]\V+Type\.php$/';
        }
        $finder = new BaseFinder();
        $finder->files()
               ->in(__DIR__.'/Form/Type/')
               ->notname('/^_/')
               ->depth('<2')
               ->path($name)
               ->sortByName();
        $files = array();
        foreach ($finder as $file) {
            $name = $file->getRelativePathname();
            $files[$name] = $name;
        }
        return $files;
    }

    /**
     * lists form files, optionally filtered by $filter.
     *
     * @param string $filter
     *
     * @return array Sorted and possibly filtered templates
     */
    public function listFormTemplates($filter = "")
    {
        // No need to list templates in safe mode.
        if ($this->safe) {
            return null;
        }

        if ($filter) {
            $name = Glob::toRegex($filter, false, false);
        } else {
            $name = '/^[a-zA-Z0-9]\V+\.twig$/';
        }

        $finder = new BaseFinder();
        $finder->files()
               ->in(__DIR__.'/../twig/forms/')
               ->depth('<2')
               ->path($name)
               ->sortByName();

        $files = array();
        foreach ($finder as $file) {
            $name = $file->getRelativePathname();
            $files[$name] = $name;
        }

        return $files;
    }

    /**
     * lists form files, optionally filtered by $filter.
     *
     * @param string $filter
     *
     * @return array Sorted and possibly filtered templates
     */
    public function listEmailTemplates($filter = "")
    {
        // No need to list templates in safe mode.
        if ($this->safe) {
            return null;
        }

        if ($filter) {
            $name = Glob::toRegex($filter, false, false);
        } else {
            $name = '/^[a-zA-Z0-9]\V+\.twig$/';
        }

        $finder = new BaseFinder();
        $finder->files()
               ->in(__DIR__.'/../twig/emails/')
               ->depth('<2')
               ->path($name)
               ->sortByName();

        $files = array();
        foreach ($finder as $file) {
            $name = $file->getRelativePathname();
            $files[$name] = $name;
        }

        return $files;
    }

}