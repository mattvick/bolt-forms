<?php

namespace Bolt\Extension\Mahango\Forms;

use Silex\Application;
use Doctrine\DBAL\Connection;
use DateTime;

/**
 * Database functions for MahangoForms
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
class Database
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var array
     */
    private $config;

    public function __construct(Application $app)
    {
        $this->app = $this->config = $app;
        $this->config = $app[Extension::CONTAINER]->config;
    }

    /**
     * Write out form data to a specified database table
     *
     * @param string $tablename
     * @param array  $data
     *
     * @return boolean
     */
    public function writeToTable($tablename, array $data)
    {
        $savedata = array();

        // Don't try to write to a non-existant table
        $sm = $this->app['db']->getSchemaManager();
        if (! $sm->tablesExist(array($tablename))) {
            return false;
        }

        // Build a new array with only keys that match the database table
        $columns = $sm->listTableColumns($tablename);
        foreach ($columns as $column) {
            $colname = $column->getName();
            $savedata[$colname] = $data[$colname];
        }

        foreach ($savedata as $key => $value) {
            // Don't try to insert NULLs
            if ($value === null) {
                $savedata[$key] = '';
            }

            // JSON encode arrays
            if (is_array($value)) {
                $savedata[$key] = json_encode($value);
            }
        }

        $this->app['db']->insert($tablename, $savedata);
    }

    /**
     * Write out form data to a specified contenttype table
     *
     * @param string $contenttype
     * @param array  $data
     */
    public function writeToContentype($contenttype, array $data)
    {
        // Get an empty record for out contenttype
        $record = $this->app['storage']->getEmptyContent($contenttype);

        foreach ($data as $key => $value) {
            // Symfony makes empty fields NULL, PostgreSQL gets mad.
            if (is_null($value)) {
                $data[$key] = '';
            }

            // JSON encode arrays
            if (is_array($value)) {
                $data[$key] = json_encode($value);
            }
        }

        // Set a published date
        if (empty($data['datepublish'])) {
            $data['datepublish'] = date('Y-m-d H:i:s');
        }

        // Store the data array into the record
        $record->setValues($data);

        return $this->app['storage']->saveContent($record);
    }

    /**
     * Fetch pages with a form
     *
     * @deprecated deprecated - now using core $content->related()
     *
     * @return array
     */
    public function fetchFormPages()
    {
        $prefix = $this->getDatabasePrefix();

        $formsTablename = $prefix.'forms';
        $pagesTablename = $prefix.'pages';

        // Don't try to read from non-existant tables
        if (!$this->tablesExist(array($formsTablename)) || !$this->tablesExist(array($pagesTablename))) {
            // TODO: throw exception
            return false;
        }

        $date = new DateTime();

        // TODO: select fields
        $sql = "SELECT $pagesTablename.slug, 
                       $pagesTablename.title, 
                       $pagesTablename.body, 
                       $pagesTablename.template, 
                       $formsTablename.title AS form_title,
                       $formsTablename.type AS form_type, 
                       $formsTablename.redirect AS form_redirect,  
                       $formsTablename.template AS form_template
                FROM $formsTablename, $pagesTablename
                WHERE $formsTablename.page IS NOT NULL 
                AND $formsTablename.page = $pagesTablename.id
                AND $pagesTablename.status = 'published'
                AND $formsTablename.status = 'published'
                AND (
                    $pagesTablename.datedepublish IS NULL
                    OR $pagesTablename.datedepublish < :datedepublish
                )
                AND (
                    $formsTablename.datedepublish IS NULL
                    OR $formsTablename.datedepublish < :datedepublish
                )";

        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue('datedepublish', $date, 'datetime');
        // $stmt->bindValue('to_contenttype', $toContenttype);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Fetch a form
     *
     * @deprecated deprecated - now using core $content->related()
     *
     * @param string $pageSlug
     *
     * @return array
     */
    public function fetchForm($pageSlug)
    {
        $prefix = $this->getDatabasePrefix();

        $formsTablename = $prefix.'forms';
        $pagesTablename = $prefix.'pages';

        // Don't try to read from non-existant tables
        if (!$this->tablesExist(array($formsTablename)) || !$this->tablesExist(array($pagesTablename))) {
            // TODO: throw exception
            return false;
        }

        $date = new DateTime();

        $sql = "SELECT $formsTablename.*
                FROM $formsTablename, $pagesTablename
                WHERE $pagesTablename.slug = :slug
                AND $formsTablename.page IS NOT NULL 
                AND $formsTablename.page = $pagesTablename.id
                AND $pagesTablename.status = 'published'
                AND $formsTablename.status = 'published'
                AND (
                    $pagesTablename.datedepublish IS NULL
                    OR $pagesTablename.datedepublish < :datedepublish
                )
                AND (
                    $formsTablename.datedepublish IS NULL
                    OR $formsTablename.datedepublish < :datedepublish
                )";

        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue('datedepublish', $date, 'datetime');
        $stmt->bindValue('slug', $pageSlug);
        $stmt->execute();

        return $stmt->fetch();
    }

    /**
     * Get pages that have forms
     *
     * @deprecated deprecated - now using core $content->related()
     * 
     * @return array
     */
    public function getPagesJoinedToForms()
    {
        $relations = $this->fetchRelated('forms', 'pages');

        $ids = $this->getRelatedIds($relations);

        $forms = $this->fetchContentByIds('forms', $ids['from']);
        $pages = $this->fetchContentByIds('pages', $ids['to']);

        $pages = $this->reverseJoinRelated($relations, $forms, $pages);

        return $pages;
    }

    /**
     * Join related contenttypes
     *
     * @deprecated deprecated - now using core $content->related()
     *
     * @param array $relations
     * @param array $fromContenttypes
     * @param array $toContenttypes
     *
     * @return array
     */
    public function reverseJoinRelated(array $relations, array $fromContenttypes, array $toContenttypes)
    {
        // All arrays are populated 
        if (!count($relations) && !count($fromContenttypes) && !count($toContenttypes)) {
            return false;
        }

        // All arrays are the same size
        if (
            count($relations) != count($fromContenttypes)
            || 
            count($relations) != count($toContenttypes)
            ||
            count($fromContenttypes) != count($toContenttypes)
        ) {
            return false;
        }

        $related = array();

        foreach ($relations as $key => $relation) {

            $from = false;
            foreach ($fromContenttypes as $key => $fromContenttype) {
                if ($fromContenttype['id'] == $relation['from_id']) {
                    $from = $fromContenttype;
                }
            }

            $to = false;
            foreach ($toContenttypes as $key => $toContenttype) {
                if ($toContenttype['id'] == $relation['to_id']) {
                    $to = $toContenttype;
                }
            }

            if (false === $from || false === $to) {
                return false;
            }

            $to['related'] = $from;
            $related[] = $to;
        }

        return empty($related) ? false : $related;
    }

    /**
     * Get the contenttype IDs from a relations array
     *
     * @deprecated deprecated - now using core $content->related()
     *
     * @param array $relations
     * @param string $side
     *
     * @return array
     */
    public function getRelatedIds(array $relations)
    {
        // build an array of page IDs from the result
        $toIds = array();
        $fromIds = array();
        foreach ($relations as $relation) {
            $fromIds[] = (integer) $relation['from_id'];
            $toIds[] = (integer) $relation['to_id'];
        }

        if (!count($toIds) && !count($fromIds) && (count($toIds) != count($fromIds))) {
            return false;
        }

        return array(
            'from' => $fromIds,
            'to' => $toIds,
        );
    }

    /**
     * Fetch related content types
     *
     * @deprecated deprecated - now using core $content->related()
     *
     * @param string $fromContenttype
     * @param string $toContenttype
     *
     * @return array
     */
    public function fetchRelated($fromContenttype, $toContenttype)
    {
        $prefix = $this->getDatabasePrefix();
        $tablename = $prefix.'relations';

        // Don't try to read from a non-existant table
        if (!$this->tablesExist(array($tablename))) {
            // TODO: throw exception
            return false;
        }

        $sql = "SELECT * FROM $tablename WHERE from_contenttype = :from_contenttype AND to_contenttype = :to_contenttype";
        $stmt = $this->app['db']->prepare($sql);
        $stmt->bindValue('from_contenttype', $fromContenttype);
        $stmt->bindValue('to_contenttype', $toContenttype);
        $stmt->execute();
        $relations = $stmt->fetchAll();

        return count($relations) ? $relations : false;
    }

    /**
     * Fetch an content from the database by IDs
     *
     * @deprecated deprecated - now using core $content->related()
     *
     * @param string $contenttype
     * @param array $ids
     *
     * @return array
     */
    public function fetchContentByIds($contenttype, array $ids)
    {
        $prefix = $this->getDatabasePrefix();
        $tablename = $prefix.$contenttype;

        // Don't try to read from a non-existant table
        if (!$this->tablesExist(array($tablename))) {
            // TODO: throw exception
            return false;
        }

        // TODO: add select fields
        // id, slug, title

        // TODO: add filters
        // 'datepublish' => string '2015-04-08 12:15:43' (length=19)
        // 'status' => string 'published' (length=9)

        $sql = "SELECT * FROM $tablename WHERE id IN (?)";


        $stmt = $this->app['db']->executeQuery($sql,
            array($ids),
            array(Connection::PARAM_INT_ARRAY)
        );
        $stmt->execute();

        // TODO: return Bolt\Content() objects

        return $stmt->fetchAll();
    }

    /**
     * Get the database prefix
     *
     * @return string
     */
    public function getDatabasePrefix()
    {
        return $this->app['config']->get('general/database/prefix', 'bolt_');
    }

    /**
     * Check the existence of a table
     *
     * @param array $tablenames
     *
     * @return boolean
     */
    public function tablesExist(array $tablenames)
    {
        $sm = $this->app['db']->getSchemaManager();
        if ($sm->tablesExist($tablenames)) {
            return true;
        }
        return false;
    }

}
