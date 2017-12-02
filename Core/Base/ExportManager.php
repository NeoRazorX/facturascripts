<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base;

use Symfony\Component\HttpFoundation\Response;

/**
 * ExportManager is the class we interact to generate an exported file of a supported type.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ExportManager
{
    /**
     * Returns the default option.
     *
     * @return string
     */
    public function defaultOption()
    {
        return 'PDF';
    }

    /**
     * Returns the options available for export.
     *
     * @return array
     */
    public function options()
    {
        return [
            'PDF' => ['description' => 'print', 'icon' => 'fa-print'],
            'XLS' => ['description' => 'spreadsheet-xls', 'icon' => 'fa-file-excel-o'],
            'CSV' => ['description' => 'structured-data-csv', 'icon' => 'fa-file-archive-o']
        ];
    }

    /**
     * Generate the document.
     *
     * @param Response $response
     * @param string $option
     * @param $model
     *
     * @return mixed
     */
    public function generateDoc(&$response, $option, $model)
    {
        /// call the appropriate class to generate the file based on the chosen option
        $className = $this->getExportClassName($option);
        $docClass = new $className();
        $docClass->setHeaders($response);

        return $docClass->newDoc($model);
    }

    /**
     * Generate a list
     *
     * @param Response $response
     * @param string $option
     * @param string $model
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param array $columns
     *
     * @return mixed
     */
    public function generateList(&$response, $option, $model, $where, $order, $offset, $columns)
    {
        /// call the appropriate class to generate the file based on the chosen option
        $className = $this->getExportClassName($option);
        $docClass = new $className();
        $docClass->setHeaders($response);

        return $docClass->newListDoc($model, $where, $order, $offset, $columns);
    }

    /**
     * Return the complete classname for export
     *
     * @param string $option
     *
     * @return string
     */
    private function getExportClassName($option)
    {
        $className = "FacturaScripts\\Dinamic\\Lib\\Export\\" . $option . 'Export';
        if (!class_exists($className)) {
            $className = "FacturaScripts\\Core\\Lib\\Export\\" . $option . 'Export';
        }

        return $className;
    }
}
