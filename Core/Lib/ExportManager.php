<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Export\ExportInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ExportManager
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ExportManager
{

    /**
     * The selected engine/class to export.
     *
     * @var ExportInterface
     */
    protected static $engine;

    /**
     * Option list.
     *
     * @var array
     */
    protected static $options;

    /**
     * ExportManager constructor.
     */
    public function __construct()
    {
        static::init();
    }

    /**
     * Adds a new option.
     *
     * @param string $key
     * @param string $description
     * @param string $icon
     */
    public static function addOption($key, $description, $icon)
    {
        static::init();
        static::$options[$key] = ['description' => $description, 'icon' => $icon];
    }

    /**
     * Returns default option.
     *
     * @return string
     */
    public function defaultOption()
    {
        foreach (array_keys(static::$options) as $key) {
            return $key;
        }

        return '';
    }

    /**
     * Adds a new page with the document data.
     *
     * @param mixed $model
     */
    public function generateBusinessDocPage($model)
    {
        static::$engine->generateBusinessDocPage($model);
    }

    /**
     * Adds a new page with a table listing the models data.
     *
     * @param mixed           $model
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param array           $columns
     * @param string          $title
     */
    public function generateListModelPage($model, $where, $order, $offset, $columns, $title = '')
    {
        static::$engine->generateListModelPage($model, $where, $order, $offset, $columns, $title);
    }

    /**
     * Adds a new page with the model data.
     *
     * @param mixed  $model
     * @param array  $columns
     * @param string $title
     */
    public function generateModelPage($model, $columns, $title = '')
    {
        static::$engine->generateModelPage($model, $columns, $title);
    }

    /**
     * Adds a new page with the table data.
     *
     * @param array $headers
     * @param array $rows
     */
    public function generateTablePage($headers, $rows)
    {
        /// We need headers key to be equal to value
        $fixedHeaders = [];
        foreach ($headers as $value) {
            $fixedHeaders[$value] = $value;
        }

        static::$engine->generateTablePage($fixedHeaders, $rows);
    }

    /**
     * Create a new doc and set headers.
     *
     * @param string $option
     */
    public function newDoc($option)
    {
        /// calls to the appropiate engine to generate the doc
        $className = $this->getExportClassName($option);
        static::$engine = new $className();
        static::$engine->newDoc();
    }

    /**
     * returns options to export.
     *
     * @return array
     */
    public function options()
    {
        return static::$options;
    }

    /**
     * Sets default orientation.
     * 
     * @param string $orientation
     */
    public function setOrientation(string $orientation)
    {
        static::$engine->setOrientation($orientation);
    }

    /**
     * Returns the formated data.
     *
     * @param Response $response
     */
    public function show(Response &$response)
    {
        static::$engine->show($response);
    }

    /**
     * Returns the full class name.
     *
     * @param string $option
     *
     * @return string
     */
    private function getExportClassName($option)
    {
        $className = '\\FacturaScripts\\Dinamic\\Lib\\Export\\' . $option . 'Export';
        return class_exists($className) ? $className : '\\FacturaScripts\\Core\\Lib\\Export\\' . $option . 'Export';
    }

    /**
     * Initialize options array
     */
    protected static function init()
    {
        if (static::$options === null) {
            static::$options = [
                'PDF' => ['description' => 'print', 'icon' => 'fas fa-print'],
                'XLS' => ['description' => 'spreadsheet-xls', 'icon' => 'fas fa-file-excel'],
                'CSV' => ['description' => 'structured-data-csv', 'icon' => 'fas fa-file-csv'],
                'MAIL' => ['description' => 'email', 'icon' => 'fas fa-envelope'],
            ];
        }
    }
}
