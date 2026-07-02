<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
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

    /** @var array */
    protected $sheetNames = [];

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
        $this->writer->writeSheet($lineRows, $this->getSheetName(Tools::trans('lines')), $this->escapeSpreadsheetFormulaHeaders($lineHeaders));

        // modelo
        $headers = $this->getModelHeaders($model);
        $rows = $this->getCursorRawData([$model]);
        $this->writer->writeSheet($rows, $this->getSheetName($model->primaryDescription()), $this->escapeSpreadsheetFormulaHeaders($headers));

        // no continuamos con la exportación del resto de pestañas
        return false;
    }

    /**
     * Adds a new page with a table listing all models data.
     *
     * @param ModelClass $model
     * @param Where[] $where
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
        $this->numSheets++;
        $name = $this->getSheetName($title);

        $headers = $this->getModelHeaders($model);
        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            // no hay datos, añadimos solamente la cabecera
            $this->writer->writeSheet([], $name, $this->escapeSpreadsheetFormulaHeaders($headers));
            return true;
        }

        // hay datos, añadimos primero la cabecera
        $this->writer->writeSheetHeader($name, $this->escapeSpreadsheetFormulaHeaders($headers));

        // añadimos los datos
        while (!empty($cursor)) {
            $rows = $this->getCursorRawData($cursor);
            foreach ($rows as $row) {
                $this->writer->writeSheetRow($name, $row);
            }

            // si el bloque no está completo, no hay más datos
            if (count($cursor) < self::LIST_LIMIT) {
                break;
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
        $this->numSheets++;
        $headers = $this->getModelHeaders($model);
        $rows = $this->getCursorRawData([$model]);
        $this->writer->writeSheet($rows, $this->getSheetName($title), $this->escapeSpreadsheetFormulaHeaders($headers));
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
        $sheetName = $this->getSheetName($title);

        $this->writer->writeSheetRow($sheetName, $this->escapeSpreadsheetFormulaRow($headers));
        foreach ($rows as $row) {
            $this->writer->writeSheetRow($sheetName, $this->escapeSpreadsheetFormulaRow($row));
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
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $this->getFileName() . '.xlsx');
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
                // los valores no string (números, booleanos) se escriben tal cual
                if (is_string($value)) {
                    $data[$num][$key] = $this->escapeSpreadsheetFormula(Tools::fixHtml($value));
                }
            }
        }

        return $data;
    }

    private function escapeSpreadsheetFormulaHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            $key = is_string($key) ? $this->escapeSpreadsheetFormula($key) : $key;
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Devuelve un nombre de hoja único y válido para Excel,
     * que limita el nombre a 31 caracteres.
     *
     * @param string $title
     *
     * @return string
     */
    protected function getSheetName(string $title): string
    {
        $name = empty($title) ? 'sheet' . $this->numSheets : Tools::slug($title, '-', 31);

        // si ya existe, añadimos un sufijo para hacerlo único
        $num = 1;
        while (in_array($name, $this->sheetNames, true)) {
            $num++;
            $suffix = '-' . $num;
            $name = empty($title) ?
                'sheet' . $this->numSheets . $suffix :
                Tools::slug($title, '-', 31 - strlen($suffix)) . $suffix;
        }

        $this->sheetNames[] = $name;
        return $name;
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
            // extraemos el tipo base: int(10) unsigned -> int, numeric(10,2) -> numeric
            $type = $modelFields[$key]['type'];
            $pos = strpos($type, '(');
            if ($pos !== false) {
                $type = substr($type, 0, $pos);
            }
            $type = trim(str_replace(' unsigned', '', $type));

            switch ($type) {
                case 'bigint':
                case 'int':
                case 'integer':
                case 'mediumint':
                case 'serial':
                case 'smallint':
                    $headers[$key] = 'integer';
                    break;

                case 'decimal':
                case 'double':
                case 'double precision':
                case 'float':
                case 'numeric':
                case 'real':
                    $headers[$key] = 'price';
                    break;

                default:
                    $headers[$key] = 'string';
            }
        }

        return $headers;
    }
}
