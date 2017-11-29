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
 * Description of ExportManager
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ExportManager
{
    /**
     * Devuelve la opción por defecto
     *
     * @return string
     */
    public function defaultOption()
    {
        return 'PDF';
    }

    /**
     * Devuelve las opciones disponibles para exportar
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
     * Genera el documento
     *
     * @param Response $response
     * @param string $option
     * @param $model
     *
     * @return mixed
     */
    public function generateDoc(&$response, $option, $model)
    {
        /// llamar a la clase apropiada para generar el archivo en función de la opción elegida
        $className = $this->getExportClassName($option);
        $docClass = new $className();
        $docClass->setHeaders($response);

        return $docClass->newDoc($model);
    }

    /**
     * Genera una lista
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
        /// llamar a la clase apropiada para generar el archivo en función de la opción elegida
        $className = $this->getExportClassName($option);
        $docClass = new $className();
        $docClass->setHeaders($response);

        return $docClass->newListDoc($model, $where, $order, $offset, $columns);
    }
    
    private function getExportClassName($option)
    {
        $className = "FacturaScripts\\Dinamic\\Lib\\Export\\" . $option . 'Export';
        if(!class_exists($className)) {
            $className = "FacturaScripts\\Core\\Lib\\Export\\" . $option . 'Export';
        }
        
        return $className;
    }
}
