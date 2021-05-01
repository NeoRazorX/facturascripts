<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Class to export data to CSV format.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CSVExport extends ExportBase
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
     * Adds the fields form the business document, merging model and line data.
     *
     * @param BusinessDocument $model
     *
     * @return bool
     */
    public function addBusinessDocPage($model): bool
    {
        $data = [];
        $fields = [];

        $data1 = $this->getCursorRawData([$model]);
        foreach ($model->getLines() as $line) {
            if (empty($fields)) {
                $fields1 = $this->getModelFields($model);
                $fields2 = $this->getModelFields($line);
                $fields = array_merge($fields2, $fields1);
            }

            /// merge
            $data2 = $this->getCursorRawData([$line]);
            $data[] = array_merge($data2[0], $data1[0]);
        }

        $this->writeData($data, $fields);

        /// do not continue with export
        return false;
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
     *
     * @return bool
     */
    public function addListModelPage($model, $where, $order, $offset, $columns, $title = ''): bool
    {
        $this->setFileName($title);

        $fields = $this->getModelFields($model);
        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            $this->writeData([], $fields);
        }

        while (!empty($cursor)) {
            $data = $this->getCursorRawData($cursor);
            $this->writeData($data, $fields);

            /// Advance within the results
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }

        /// do not continue with export
        return false;
    }

    /**
     * Adds a new page with the model data.
     *
     * @param ModelClass $model
     * @param array      $columns
     * @param string     $title
     *
     * @return bool
     */
    public function addModelPage($model, $columns, $title = ''): bool
    {
        $fields = $this->getModelFields($model);
        $data = $this->getCursorRawData([$model]);
        $this->writeData($data, $fields);

        /// do not continue with export
        return false;
    }

    /**
     * Adds a new page with the table.
     *
     * @param array $headers
     * @param array $rows
     *
     * @return bool
     */
    public function addTablePage($headers, $rows): bool
    {
        $this->writeData($rows, $headers);

        /// do not continue with export
        return false;
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
     * 
     * @param string $title
     * @param int    $idformat
     * @param string $langcode
     */
    public function newDoc(string $title, int $idformat, string $langcode)
    {
        $this->csv = [];
        $this->setFileName($title);
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
     * 
     * @param string $orientation
     */
    public function setOrientation(string $orientation)
    {
        /// not implemented
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
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $this->getFileName() . '.csv');
        $response->setContent($this->getDoc());
    }

    /**
     * Fills an array with the CSV data.
     * 
     * @param array $data
     * @param array $fields
     */
    public function writeData($data, $fields)
    {
        $headers = [];
        foreach ($fields as $field) {
            $headers[] = $this->getDelimiter() . $field . $this->getDelimiter();
        }
        $this->csv[] = \implode($this->separator, $headers);

        foreach ($data as $row) {
            $line = [];
            foreach ($row as $cell) {
                $line[] = is_string($cell) ? $this->getDelimiter() . $cell . $this->getDelimiter() : $cell;
            }

            $this->csv[] = \implode($this->separator, $line);
        }
    }
}
