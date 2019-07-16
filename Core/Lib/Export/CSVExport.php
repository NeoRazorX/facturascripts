<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Export;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\ModelClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to export to CSV
 * Follow the XLSExport style to have a more uniform code
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CSVExport implements ExportInterface
{

    const LIST_LIMIT = 1000;

    /**
     * Contains the CSV data in array format
     *
     * @var array
     */
    private $csv = [];

    /**
     * Text delimiter value
     *
     * @var string
     */
    private $delimiter = '"';

    /**
     * Separator value
     *
     * @var string
     */
    private $separator = ';';

    /**
     * Adds a new page with the document data.
     *
     * @param BusinessDocument $model
     */
    public function generateBusinessDocPage($model)
    {
        $tableData = [];
        foreach ((array) $model as $key => $value) {
            if (is_string($value)) {
                $tableData[] = [
                    'key' => $this->delimiter . $key . $this->delimiter,
                    'value' => $this->delimiter . $value . $this->delimiter,
                ];
            }
        }

        $this->writeSheet($tableData, ['key' => 'string', 'value' => 'string']);
    }

    /**
     * Adds a new page with a table listing the models data.
     *
     * @param ModelClass      $model
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param array           $columns
     * @param string          $title
     */
    public function generateListModelPage($model, $where, $order, $offset, $columns, $title = '')
    {
        $tableCols = [];
        $sheetHeaders = [];
        $tableData = [];

        /// Get the columns
        foreach ($columns as $col) {
            if ($col->hidden()) {
                continue;
            }

            $tableCols[$col->widget->fieldname] = $col->widget->fieldname;
            $sheetHeaders[$col->widget->fieldname] = 'string';
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
     * Adds a new page with the model data.
     *
     * @param ModelClass $model
     * @param array      $columns
     * @param string     $title
     */
    public function generateModelPage($model, $columns, $title = '')
    {
        $tableData = [];
        foreach ((array) $model as $key => $value) {
            if (is_string($value)) {
                $tableData[] = [
                    'key' => $this->delimiter . $key . $this->delimiter,
                    'value' => $this->delimiter . $value . $this->delimiter,
                ];
            }
        }

        $this->writeSheet($tableData, ['key' => 'string', 'value' => 'string']);
    }

    /**
     * Adds a new page with the table.
     *
     * @param array $headers
     * @param array $rows
     */
    public function generateTablePage($headers, $rows)
    {
        /// fix headers
        foreach ($headers as $key => $value) {
            $headers[$key] = $this->delimiter . $value . $this->delimiter;
        }
        /// generate the headers line
        $this->csv[] = \implode($this->separator, $headers);

        /// generate the data lines
        $body = [];
        foreach ($rows as $row) {
            /// fix row
            foreach ($row as $key => $value) {
                $row[$key] = $this->delimiter . $value . $this->delimiter;
            }

            $body[] = \implode($this->separator, $row);
        }
        $this->csv[] = \implode(PHP_EOL, $body);
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
     * Return the full document.
     *
     * @return string
     */
    public function getDoc()
    {
        return \implode(PHP_EOL, $this->csv);
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
     * Blank document.
     */
    public function newDoc()
    {
        $this->csv = [];
    }

    /**
     * Assigns the received text delimiter
     * By default it will use '"' quotes.
     *
     * @param string $del
     */
    public function setDelimiter($del)
    {
        $this->delimiter = $del;
    }

    /**
     * Assigns the received separator.
     * By default it will use ';' semicolons.
     *
     * @param string $sep
     */
    public function setSeparator($sep)
    {
        $this->separator = $sep;
    }

    /**
     * Set headers and output document content to response.
     *
     * @param Response $response
     */
    public function show(Response &$response)
    {
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=doc.csv');
        $response->setContent($this->getDoc());
    }

    /**
     * Fills an array with the CSV data.
     * 
     * @param array $tableData
     * @param array $sheetHeaders
     */
    public function writeSheet($tableData, $sheetHeaders)
    {
        $header = [];
        foreach (\array_keys($sheetHeaders) as $key) {
            $header[] = $key;
        }
        $this->csv[] = \implode($this->separator, $header);

        $body = [];
        foreach ($tableData as $line) {
            $body[] = \implode($this->separator, $line);
        }
        $this->csv[] = \implode(PHP_EOL, $body);
    }

    /**
     * Returns the table data.
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
                $value = (isset($row->{$col}) && null !== $row->{$col}) ? $row->{$col} : '';
                $tableData[$key][$col] = $this->delimiter . $value . $this->delimiter;
            }
        }

        return $tableData;
    }
}
