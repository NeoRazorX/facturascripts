<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
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
     * @var mixed
     */
    private static $engine;

    /**
     * Option list.
     *
     * @var array
     */
    private static $options;

    /**
     * ExportManager constructor.
     */
    public function __construct()
    {
        if (self::$options === null) {
            self::$options = [
                'PDF' => ['description' => 'print', 'icon' => 'fa-print'],
                'XLS' => ['description' => 'spreadsheet-xls', 'icon' => 'fa-file-excel-o'],
                'CSV' => ['description' => 'structured-data-csv', 'icon' => 'fa-file-archive-o'],
                'Email' => ['description' => 'send-via-email', 'icon' => 'fa-envelope-o'],
            ];
        }
    }

    /**
     * Returns default option.
     *
     * @return string
     */
    public function defaultOption()
    {
        $keys = array_keys(self::$options);

        return $keys[0];
    }

    /**
     * returns options to export.
     *
     * @return array
     */
    public function options()
    {
        return self::$options;
    }

    /**
     * Create a new doc and set headers.
     *
     * @param string   $option
     */
    public function newDoc($option)
    {
        /// llamar a la clase apropiada para generar el archivo en función de la opción elegida
        $className = $this->getExportClassName($option);
        self::$engine = new $className();
        self::$engine->newDoc();
    }

    /**
     * Returns the formated data.
     *
     * @param Response $response
     */
    public function show(&$response)
    {
        self::$engine->show($response);
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
        self::$engine->generateModelPage($model, $columns, $title);
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
        /// disable 30 seconds PHP limit
        set_time_limit(0);

        self::$engine->generateListModelPage($model, $where, $order, $offset, $columns, $title);
    }

    /**
     * Adds a new page with the document data.
     *
     * @param mixed $model
     */
    public function generateDocumentPage($model)
    {
        self::$engine->generateDocumentPage($model);
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

        self::$engine->generateTablePage($fixedHeaders, $rows);
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
        $className = 'FacturaScripts\\Dinamic\\Lib\\Export\\' . $option . 'Export';
        if (!class_exists($className)) {
            $className = 'FacturaScripts\\Core\\Lib\\Export\\' . $option . 'Export';
        }

        return $className;
    }
}
