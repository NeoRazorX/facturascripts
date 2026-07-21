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
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

/**
 * Clase para exportar datos al formato CSV.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CSVExport extends ExportBase
{
    const LIST_LIMIT = 1000;

    /**
     * Contiene los datos del CSV en formato array
     *
     * @var array
     */
    private $csv = [];

    /**
     * Delimitador de texto
     *
     * @var string
     */
    private $delimiter = '"';

    /**
     * Separador de campos
     *
     * @var string
     */
    private $separator = ';';

    /**
     * Añade los campos del documento de negocio, combinando los datos del modelo y de las líneas.
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

            // combinamos los datos de la línea con los del documento
            $data2 = $this->getCursorRawData([$line]);
            $data[] = array_merge($data2[0], $data1[0]);
        }

        // sin líneas, exportamos solamente los datos del documento
        if (empty($data)) {
            $fields = $this->getModelFields($model);
            $data = $data1;
        }

        $this->writeData($data, $fields);

        // no continuamos con la exportación
        return false;
    }

    /**
     * Añade una nueva página con una tabla listando los datos del modelo.
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

        $fields = $this->getModelFields($model);
        $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        if (empty($cursor)) {
            $this->writeData([], $fields);
        }

        while (!empty($cursor)) {
            $data = $this->getCursorRawData($cursor);
            $this->writeData($data, $fields);
            $fields = [];

            // si el bloque no está completo, no hay más datos
            if (count($cursor) < self::LIST_LIMIT) {
                break;
            }

            // avanzamos en los resultados
            $offset += self::LIST_LIMIT;
            $cursor = $model->all($where, $order, $offset, self::LIST_LIMIT);
        }

        // no continuamos con la exportación
        return false;
    }

    /**
     * Añade una nueva página con los datos del modelo.
     *
     * @param ModelClass $model
     * @param array $columns
     * @param string $title
     *
     * @return bool
     */
    public function addModelPage($model, $columns, $title = ''): bool
    {
        $fields = $this->getModelFields($model);
        $data = $this->getCursorRawData([$model]);
        $this->writeData($data, $fields);

        // no continuamos con la exportación
        return false;
    }

    /**
     * Añade una nueva página con la tabla.
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
        $this->writeData($rows, $headers);

        // no continuamos con la exportación
        return false;
    }

    /**
     * Devuelve el delimitador de texto asignado
     *
     * @return string
     */
    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    /**
     * Devuelve el documento completo.
     *
     * @return string
     */
    public function getDoc()
    {
        // BOM para que Excel abra correctamente los caracteres UTF-8
        return "\xEF\xBB\xBF" . implode(PHP_EOL, $this->csv);
    }

    /**
     * Devuelve el separador asignado
     *
     * @return string
     */
    public function getSeparator(): string
    {
        return $this->separator;
    }

    /**
     * Documento en blanco.
     *
     * @param string $title
     * @param int $idformat
     * @param string $langcode
     */
    public function newDoc(string $title, int $idformat, string $langcode)
    {
        $this->csv = [];
        $this->setFileName($title);
    }

    /**
     * Asigna el delimitador de texto recibido.
     * Por defecto utiliza comillas dobles '"'.
     *
     * @param string $del
     */
    public function setDelimiter(string $del)
    {
        $this->delimiter = $del;
    }

    /**
     *
     * @param string $orientation
     */
    public function setOrientation(string $orientation)
    {
        // no implementado
    }

    /**
     * Asigna el separador recibido.
     * Por defecto utiliza el punto y coma ';'.
     *
     * @param string $sep
     */
    public function setSeparator(string $sep)
    {
        $this->separator = $sep;
    }

    /**
     * Asigna las cabeceras y vuelca el contenido del documento a la respuesta.
     *
     * @param Response $response
     */
    public function show(Response &$response)
    {
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $this->getFileName() . '.csv');
        $response->setContent($this->getDoc());
    }

    /**
     * Rellena un array con los datos del CSV.
     *
     * @param array $data
     * @param array $fields
     */
    public function writeData(array $data, array $fields = [])
    {
        if (!empty($fields)) {
            $this->writeHeader($fields);
        }

        foreach ($data as $row) {
            $line = [];
            foreach ($row as $cell) {
                $line[] = is_string($cell) ? $this->formatCell(Tools::fixHtml($cell)) : $cell;
            }

            $this->csv[] = implode($this->separator, $line);
        }
    }

    private function formatCell(string $cell): string
    {
        $cell = $this->escapeSpreadsheetFormula($cell);
        $delimiter = $this->getDelimiter();
        if ($delimiter === '') {
            return $cell;
        }

        return $delimiter . str_replace($delimiter, $delimiter . $delimiter, $cell) . $delimiter;
    }

    /**
     *
     * @param array $fields
     */
    private function writeHeader(array $fields)
    {
        $headers = [];
        foreach ($fields as $field) {
            $headers[] = $this->formatCell((string)$field);
        }
        $this->csv[] = implode($this->separator, $headers);
    }
}
