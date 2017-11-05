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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\ExportInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of XLSExport
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class XLSExport implements ExportInterface
{

    use \FacturaScripts\Core\Base\Utils;

    const LIST_LIMIT = 1000;

    /**
     * Nuevo documento
     *
     * @param $model
     *
     * @return bool|string
     */
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

        $writer->writeSheet($tableData, '', ['key' => 'string', 'value' => 'string']);
        return $writer->writeToString();
    }

    /**
     * Nueva lista de documentos
     *
     * @param $model
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param array $columns
     *
     * @return bool|string
     */
    public function newListDoc($model, $where, $order, $offset, $columns)
    {
        $writer = new \XLSXWriter();
        $writer->setAuthor('FacturaScripts');

        /// obtenemos las columnas
        $tableCols = [];
        $sheetHeaders = [];
        $tableData = [];

        /// obtenemos las columnas
        foreach ($columns as $col) {
            $tableCols[$col->widget->fieldName] = $col->widget->fieldName;
            $sheetHeaders[$col->widget->fieldName] = 'string';
        }

        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            $writer->writeSheet($tableData, '', $sheetHeaders);
        }
        while (!empty($cursor)) {
            $tableData = $this->getTableData($cursor, $tableCols);
            $writer->writeSheet($tableData, '', $sheetHeaders);

            /// avanzamos en los resultados
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }

        return $writer->writeToString();
    }

    /**
     * Devuelvo los datos de la tabla
     *
     * @param array $cursor
     * @param array $tableCols
     *
     * @return array
     */
    private function getTableData($cursor, $tableCols)
    {
        $tableData = [];

        /// obtenemos los datos
        foreach ($cursor as $key => $row) {
            foreach ($tableCols as $col) {
                $value = '';
                if (isset($row->{$col})) {
                    $value = $row->{$col};
                    if (is_string($value)) {
                        $value = $this->fixHtml($value);
                    } elseif (is_null($value)) {
                        $value = '';
                    }
                }

                $tableData[$key][$col] = $value;
            }
        }

        return $tableData;
    }

    /**
     * Asigna la cabecera
     *
     * @param Response $response
     */
    public function setHeaders(&$response)
    {
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=doc.xls');
    }
}
