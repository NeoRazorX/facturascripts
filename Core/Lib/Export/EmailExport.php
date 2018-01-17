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

namespace FacturaScripts\Core\Lib\Export;

use FacturaScripts\Core\Base;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class to export and send by email a page
 *
 * @author Ángel Guzmán Maeso <angel@guzmanmaeso.com>
 */
class EmailExport implements ExportInterface
{
    use Base\Utils;

    /**
     * EmailExport constructor.
     */
    public function __construct()
    {
    }

    /**
     * Return the full document.
     *
     * @return string
     */
    public function getDoc()
    {
    }

    /**
     * Set headers.
     *
     * @param Response $response
     */
    public function newDoc(&$response)
    {
        return new RedirectResponse('index.php?page=ExportEmail', 302);
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
        $tableData = [];
        foreach ((array) $model as $key => $value) {
            if (is_string($value)) {
                $tableData[] = [
                    'key' =>  $key ,
                    'value' =>  $value,
                ];
            }
        }

        $this->writeSheet($tableData, ['key' => 'string', 'value' => 'string']);
    }

    /**
     * Adds a new page with a table listing the models data.
     *
     * @param mixed                         $model
     * @param Base\DataBase\DataBaseWhere[] $where
     * @param array                         $order
     * @param int                           $offset
     * @param array                         $columns
     * @param string                        $title
     */
    public function generateListModelPage($model, $where, $order, $offset, $columns, $title = '')
    {
        $tableCols = [];
        $sheetHeaders = [];
        $tableData = [];

        /// Get the columns
        foreach ($columns as $col) {
            $tableCols[$col->widget->fieldName] = $col->widget->fieldName;
            $sheetHeaders[$col->widget->fieldName] = 'string';
        }

        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            $this->writeSheet($tableData, $sheetHeaders);
        }
        while (!empty($cursor)) {
            $tableData = $this->getTableData($cursor, $tableCols);
            $this->writeSheet($tableData, $sheetHeaders);

            /// Advance within the results
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }
    }

    /**
     * Adds a new page with the document data.
     *
     * @param mixed $model
     */
    public function generateDocumentPage($model)
    {
        /// TODO: Uncomplete
        $tableData = [];
        foreach ((array) $model as $key => $value) {
            if (is_string($value)) {
                $tableData[] = [
                    'key' =>  $key,
                    'value' => $value,
                ];
            }
        }

            var_dump($tableData);
        //$this->writeSheet($tableData, ['key' => 'string', 'value' => 'string']);
    }

    /**
     * Adds a new page with the table.
     *
     * @param array $headers
     * @param array $rows
     */
    public function generateTablePage($headers, $rows)
    {
        /// Generate the headers line
        $this->csv[] = \implode($this->separator, $headers);

        /// Generate the data lines
        $body = [];
        foreach ($rows as $row) {
            $body[] = \implode($this->separator, $row);
        }

        $this->csv[] = \implode(PHP_EOL, $body);
    }

    /**
     * Returns the table data
     *
     * @param array $cursor
     * @param array $tableCols
     *
     * @return array
     */
    private function getTableData($cursor, $tableCols)
    {
        $tableData = [];

        /// Get the data
        foreach ($cursor as $key => $row) {
            foreach ($tableCols as $col) {
                $value = '';
                if (isset($row->{$col})) {
                    $value = $row->{$col};
                    if (null === $value) {
                        $value = '';
                    }
                }

                $tableData[$key][$col] = $this->delimiter . $value . $this->delimiter;
            }
        }

        return $tableData;
    }

    /**
     * Fills an array with the CSV data
     *
     * @param $tableData
     * @param $sheetHeaders
     */
    public function writeSheet($tableData, $sheetHeaders)
    {
        $this->csv = [];
        $header = [];
        $body = [];

        foreach ($sheetHeaders as $key => $value) {
            $header[] = $key;
        }
        $this->csv[] = $header;

        $this->csv[] = \implode(PHP_EOL, $body);
    }
}
