<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use XLSXWriter;

/**
 * XLS export data.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class XLSExport extends ExportBase
{
    const LIST_LIMIT = 5000;

    /** @var int */
    protected $numSheets = 0;

    /** @var XLSXWriter */
    protected $writer;

    /**
     * Adds a new page with the document data.
     *
     * @param BusinessDocument $model
     *
     * @return bool
     */
    public function addBusinessDocPage($model): bool
    {
        // líneas
        $cursor = [];
        $lineHeaders = [];
        foreach ($model->getLines() as $line) {
            if (empty($lineHeaders)) {
                $lineHeaders = $this->getModelHeaders($line);
            }

            $cursor[] = $line;
        }

        $lineRows = $this->getCursorRawData($cursor);
        $this->writer->writeSheet($lineRows, Tools::lang()->trans('lines'), $lineHeaders);

        // modelo
        $headers = $this->getModelHeaders($model);
        $rows = $this->getCursorRawData([$model]);
        $this->writer->writeSheet($rows, $model->primaryDescription(), $headers);

        // no continuamos con la exportación del resto de pestañas
        return false;
    }

    /**
     * Adds a new page with a table listing all models data.
     *
     * @param ModelClass $model
     * @param DataBaseWhere[] $where
     * @param array $order
     * @param int $offset
     * @param array $columns
     * @param string $title
     *
     * @return bool
     */
    public function addListModelPage($model, $where, $order, $offset, $columns, $title = ''): bool
    {
        $this->setFileName($title);
        $name = empty($title) ? 'sheet' . $this->numSheets : Tools::slug($title);

        $headers = $this->getModelHeaders($model);
        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            // no hay datos, añadimos solamente la cabecera
            $this->writer->writeSheet([], $name, $headers);
            return true;
        }

        // hay datos, añadimos primero la cabecera
        $this->writer->writeSheetHeader($name, $headers);

        // añadimos los datos
        while (!empty($cursor)) {
            $rows = $this->getCursorRawData($cursor);
            foreach ($rows as $row) {
                $this->writer->writeSheetRow($name, $row);
            }

            // obtenemos el siguiente bloque de datos
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }

        return true;
    }

    /**
     * Adds a new page with the model data.
     *
     * @param ModelClass $model
     * @param array $columns
     * @param string $title
     *
     * @return bool
     */
    public function addModelPage($model, $columns, $title = ''): bool
    {
        $headers = $this->getModelHeaders($model);
        $rows = $this->getCursorRawData([$model]);
        $this->writer->writeSheet($rows, $title, $headers);
        return true;
    }

    /**
     * Adds a new page with the table.
     *
     * @param array $headers
     * @param array $rows
     * @param array $options
     * @param string $title
     *
     * @return bool
     */
    public function addTablePage($headers, $rows, $options = [], $title = ''): bool
    {
        $this->numSheets++;
        $sheetName = 'sheet' . $this->numSheets;

        $this->writer->writeSheetRow($sheetName, $headers);
        foreach ($rows as $row) {
            $this->writer->writeSheetRow($sheetName, $row);
        }

        return true;
    }

    /**
     * Return the full document.
     *
     * @return string
     */
    public function getDoc()
    {
        return (string)$this->writer->writeToString();
    }

    /**
     * Blank document.
     *
     * @param string $title
     * @param int $idformat
     * @param string $langcode
     */
    public function newDoc(string $title, int $idformat, string $langcode)
    {
        $this->setFileName($title);

        $this->writer = new XLSXWriter();
        $this->writer->setAuthor('FacturaScripts');
        $this->writer->setTitle($title);
    }

    /**
     * @param string $orientation
     */
    public function setOrientation(string $orientation)
    {
        // Not implemented
    }

    /**
     * Set headers and output document content to response.
     *
     * @param Response $response
     */
    public function show(Response &$response)
    {
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $this->getFileName() . '.xlsx');
        $response->setContent($this->getDoc());
    }

    /**
     * @param array $columns
     *
     * @return array
     */
    protected function getColumnHeaders(array $columns): array
    {
        $headers = [];
        foreach ($this->getColumnTitles($columns) as $col) {
            $headers[$col] = 'string';
        }

        return $headers;
    }

    /**
     * @param array $cursor
     * @param array $fields
     *
     * @return array
     */
    protected function getCursorRawData(array $cursor, array $fields = []): array
    {
        $data = parent::getCursorRawData($cursor, $fields);
        foreach ($data as $num => $row) {
            foreach ($row as $key => $value) {
                $data[$num][$key] = Tools::fixHtml($value);
            }
        }

        return $data;
    }

    /**
     * @param ModelClass $model
     *
     * @return array
     */
    protected function getModelHeaders($model): array
    {
        $headers = [];
        $modelFields = $model->getModelFields();
        foreach ($this->getModelFields($model) as $key) {
            switch ($modelFields[$key]['type']) {
                case 'int':
                    $headers[$key] = 'integer';
                    break;

                case 'double':
                    $headers[$key] = 'price';
                    break;

                default:
                    $headers[$key] = 'string';
            }
        }

        return $headers;
    }
}
