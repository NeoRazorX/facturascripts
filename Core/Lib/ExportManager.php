<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\Export\ExportBase;
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
     * @var ExportBase
     */
    protected static $engine;

    /**
     * Option list.
     *
     * @var array
     */
    protected static $options = [];

    /**
     * Default document orientation.
     *
     * @var string
     */
    protected $orientation;

    /**
     * Tools list.
     *
     * @var array
     */
    protected static $tools = [];

    /**
     * ExportManager constructor.
     */
    public function __construct()
    {
        static::init();
    }

    /**
     * Adds a new page with the document data.
     *
     * @param mixed $model
     *
     * @return bool
     */
    public function addBusinessDocPage($model): bool
    {
        return empty(static::$engine) ? false : static::$engine->addBusinessDocPage($model);
    }

    /**
     * Adds a new page with a table listing the models data.
     *
     * @param mixed  $model
     * @param array  $where
     * @param array  $order
     * @param int    $offset
     * @param array  $columns
     * @param string $title
     *
     * @return bool
     */
    public function addListModelPage($model, $where, $order, $offset, $columns, $title = ''): bool
    {
        return empty(static::$engine) ? false : static::$engine->addListModelPage($model, $where, $order, $offset, $columns, $title);
    }

    /**
     * Adds a new page with the model data.
     *
     * @param mixed  $model
     * @param array  $columns
     * @param string $title
     *
     * @return bool
     */
    public function addModelPage($model, $columns, $title = ''): bool
    {
        return empty(static::$engine) ? false : static::$engine->addModelPage($model, $columns, $title);
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
     * Adds a new tool.
     *
     * @param string $key
     * @param string $link
     * @param string $description
     * @param string $icon
     */
    public static function addTool($key, $link, $description, $icon)
    {
        static::init();
        static::$tools[$key] = ['link' => $link, 'description' => $description, 'icon' => $icon];
    }

    /**
     * Adds a new page with the table data.
     *
     * @param array $headers
     * @param array $rows
     *
     * @return bool
     */
    public function addTablePage($headers, $rows): bool
    {
        /// We need headers key to be equal to value
        $fixedHeaders = [];
        foreach ($headers as $value) {
            $fixedHeaders[$value] = $value;
        }

        return empty(static::$engine) ? false : static::$engine->addTablePage($fixedHeaders, $rows);
    }

    /**
     * Returns default option.
     *
     * @return string
     */
    public static function defaultOption()
    {
        foreach (\array_keys(static::$options) as $key) {
            return $key;
        }

        return '';
    }

    /**
     * Create a new doc and set headers.
     *
     * @param string $option
     * @param string $title
     */
    public function newDoc(string $option, string $title = '')
    {
        /// calls to the appropiate engine to generate the doc
        $className = $this->getExportClassName($option);
        static::$engine = new $className();
        static::$engine->newDoc($title);
        if (!empty($this->orientation)) {
            static::$engine->setOrientation($this->orientation);
        }
    }

    /**
     * returns options to export.
     *
     * @return array
     */
    public static function options(): array
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
        $this->orientation = $orientation;
    }

    /**
     * Returns the formated data.
     *
     * @param Response $response
     */
    public function show(Response &$response)
    {
        if (!empty(static::$engine)) {
            static::$engine->show($response);
        }
    }

    /**
     * 
     * @return array
     */
    public static function tools(): array
    {
        return self::$tools;
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
        $dinClassName = '\\FacturaScripts\\Dinamic\\Lib\\Export\\' . $option . 'Export';
        if (\class_exists($dinClassName)) {
            return $dinClassName;
        }

        $className = '\\FacturaScripts\\Core\\Lib\\Export\\' . $option . 'Export';
        return \class_exists($className) ? $className : '\\FacturaScripts\\Core\\Lib\\Export\\PDFExport';
    }

    /**
     * Initialize options array
     */
    protected static function init()
    {
        if (empty(static::$options)) {
            static::$options = [
                'PDF' => ['description' => 'print', 'icon' => 'fas fa-print'],
                'XLS' => ['description' => 'spreadsheet-xls', 'icon' => 'fas fa-file-excel'],
                'CSV' => ['description' => 'structured-data-csv', 'icon' => 'fas fa-file-csv'],
                'MAIL' => ['description' => 'email', 'icon' => 'fas fa-envelope']
            ];
        }

        if (empty(static::$tools)) {
            static::$tools = [
                'main' => [
                    'link' => 'ListSecuenciaDocumento?activetab=ListFormatoDocumento',
                    'description' => 'printing-formats',
                    'icon' => 'fas fa-cog'
                ],
            ];
        }
    }
}
