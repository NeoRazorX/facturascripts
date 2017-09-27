<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

use FacturaScripts\Core\Base\ExportInterface;

/**
 * Description of XLSExport
 *
 * @author carlos
 */
class XLSExport implements ExportInterface
{
    use \FacturaScripts\Core\Base\Utils;
    
    const LIST_LIMIT = 1000;

    public function newDoc($model)
    {
        $writer = new \XLSXWriter();
        $writer->setAuthor('FacturaScripts');
        
        $tableData = [];
        foreach ((array) $model as $key => $value) {
            if (is_string($value)) {
                $tableData[] = ['key' => $key, 'value' => $this->fixHtml($value)];
            }
        }
        
        $writer->writeSheet($tableData);
        return $writer->writeToString();
    }

    public function newListDoc($model, $where, $order, $offset, $columns)
    {
        $writer = new \XLSXWriter();
        $writer->setAuthor('FacturaScripts');

        /// obtenemos las columnas
        $tableCols = [];
        foreach ($columns as $col) {
            if ($col->display != 'none') {
                $tableCols[$col->widget->fieldName] = $col->widget->fieldName;
            }
        }
        
        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        while (!empty($cursor)) {
            $tableData = $this->getTableData($cursor, $tableCols);
            $writer->writeSheet($tableData);

            /// avanzamos en los resultados
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }
        
        return $writer->writeToString();
    }

    private function getTableData($cursor, $tableCols)
    {
        $tableData = [];

        /// obtenemos los datos
        foreach ($cursor as $key => $row) {
            foreach ($tableCols as $col) {
                $value = $row->{$col};
                if (is_string($value)) {
                    $value = $this->fixHtml($value);
                }

                $tableData[$key][$col] = $value;
            }
        }

        return $tableData;
    }
    
    public function setHeaders(&$response)
    {
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=doc.xls');
    }
}
