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
 * Class to export to CSV
 * Follow the XLSExport style to have a more uniform code
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CSVExport implements ExportInterface
{

    use \FacturaScripts\Core\Base\Utils;

    const LIST_LIMIT = 1000;

    /**
     * Contains the CSV data in array format
     *
     * @var array
     */
    private $csv;

    /**
     * Separator value
     *
     * @var string
     */
    private $separator;

    /**
     * Text delimiter value
     *
     * @var string
     */
    private $delimiter;

    /**
     * CSVExport constructor.
     */
    public function __construct()
    {
        $this->separator = ';';
        $this->delimiter = '"';
    }

    /**
     * Assigns the received separator.
     * By default it will use ';' semicolons.
     *
     * @param $sep
     */
    public function setSeparator($sep)
    {
        $this->separator = $sep;
    }

    /**
     * Assigns the received text delimiter
     * By default it will use '"' quotes.
     * @param $del
     */
    public function setDelimiter($del)
    {
        $this->delimiter = $del;
    }

    /**
     * Returns the assigned separator
     *
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * Returns the received text delimiter assigned
     *
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * New document
     *
     * @param $model
     *
     * @return string
     */
    public function newDoc($model)
    {
        $tableData = [];
        foreach ((array) $model as $key => $value) {
            if (is_string($value)) {
                $tableData[] = [
                    'key' => $this->delimiter . $key . $this->delimiter,
                    'value' => $this->delimiter . $this->fixHtml($value) . $this->delimiter
                ];
            }
        }

        $this->writeSheet($tableData, ['key' => 'string', 'value' => 'string']);
        return $this->writeToString();
    }

    /**
     * New document list
     *
     * @param $model
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param array $columns
     *
     * @return string
     */
    public function newListDoc($model, $where, $order, $offset, $columns)
    {
        /// get the columns
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

        return $this->writeToString();
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
                    if (is_string($value)) {
                        $value = $this->fixHtml($value);
                    } elseif (is_null($value)) {
                        $value = '';
                    }
                }

                $tableData[$key][$col] = $this->delimiter . $value . $this->delimiter;
            }
        }

        return $tableData;
    }

    /**
     * Assigns the header
     *
     * @param Response $response
     */
    public function setHeaders(&$response)
    {
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=doc.csv');
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
        $this->csv[] = \implode($this->separator, $header);

        foreach ($tableData as $line) {
            $body[] = \implode($this->separator, $line);
        }
        $this->csv[] = \implode(PHP_EOL, $body);
    }

    /**
     * Retrurns the CSV as plain text
     *
     * @return string
     */
    public function writeToString()
    {
        return \implode(PHP_EOL, $this->csv);
    }
}
