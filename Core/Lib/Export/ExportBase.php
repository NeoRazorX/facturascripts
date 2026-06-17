<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\FormatoDocumento;

/**
 * Export interface.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class ExportBase
{
    private const SPREADSHEET_FORMULA_TRIGGERS = ['=', '+', '-', '@', "\t", "\r"];

    /** @var string */
    private $fileName;

    /**
     * Adds a new page with the document data.
     */
    abstract public function addBusinessDocPage($model): bool;

    /**
     * Adds a new page with a table listing the model's data.
     */
    abstract public function addListModelPage($model, $where, $order, $offset, $columns, $title = ''): bool;

    /**
     * Adds a new page with the model data.
     */
    abstract public function addModelPage($model, $columns, $title = ''): bool;

    /**
     * Adds a new page with the table.
     */
    abstract public function addTablePage($headers, $rows, $options = [], $title = ''): bool;

    /**
     * Return the full document.
     */
    abstract public function getDoc();

    /**
     * Blank document.
     */
    abstract public function newDoc(string $title, int $idformat, string $langcode);

    /**
     * Sets default orientation.
     */
    abstract public function setOrientation(string $orientation);

    /**
     * Set headers and output document content to response.
     */
    abstract public function show(Response &$response);

    /**
     * @param array $columns
     *
     * @return array
     */
    protected function getColumnAlignments(array $columns): array
    {
        $alignments = [];
        foreach ($columns as $col) {
            if (is_string($col)) {
                $alignments[$col] = 'left';
                continue;
            }

            if (isset($col->columns)) {
                foreach ($this->getColumnAlignments($col->columns) as $key2 => $col2) {
                    $alignments[$key2] = $col2;
                }
                continue;
            }

            if (!$col->hidden()) {
                $alignments[$col->widget->fieldname] = $col->display;
            }
        }

        return $alignments;
    }

    /**
     * @param array $columns
     *
     * @return array
     */
    protected function getColumnTitles(array $columns): array
    {
        $titles = [];
        foreach ($columns as $col) {
            if (is_string($col)) {
                $titles[$col] = $col;
                continue;
            }

            if (isset($col->columns)) {
                foreach ($this->getColumnTitles($col->columns) as $key2 => $col2) {
                    $titles[$key2] = $col2;
                }
                continue;
            }

            if (!$col->hidden()) {
                $titles[$col->widget->fieldname] = Tools::trans($col->title);
            }
        }

        return $titles;
    }

    /**
     * @param array $columns
     *
     * @return array
     */
    protected function getColumnWidgets(array $columns): array
    {
        $widgets = [];
        foreach ($columns as $col) {
            if (is_string($col)) {
                continue;
            }

            if (isset($col->columns)) {
                foreach ($this->getColumnWidgets($col->columns) as $key2 => $col2) {
                    $widgets[$key2] = $col2;
                }
                continue;
            }

            if (!$col->hidden()) {
                $widgets[$col->widget->fieldname] = $col->widget;
            }
        }

        return $widgets;
    }

    /**
     * @param ModelClass[] $cursor
     * @param array $columns
     *
     * @return array
     */
    protected function getCursorData(array $cursor, array $columns): array
    {
        $data = [];
        $widgets = $this->getColumnWidgets($columns);
        foreach ($cursor as $num => $row) {
            foreach ($widgets as $key => $widget) {
                $data[$num][$key] = $widget->plainText($row);
            }
        }

        return $data;
    }

    /**
     * @param ModelClass[] $cursor
     * @param array $fields
     *
     * @return array
     */
    protected function getCursorRawData(array $cursor, array $fields = []): array
    {
        $data = [];
        foreach ($cursor as $num => $row) {
            if (empty($fields)) {
                $fields = array_keys($row->getModelFields());
            }

            foreach ($fields as $key) {
                $value = (isset($row->{$key}) && null !== $row->{$key}) ? $row->{$key} : '';
                $data[$num][$key] = $value;
            }
        }

        return $data;
    }

    protected function escapeSpreadsheetFormula(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        return in_array($value[0], self::SPREADSHEET_FORMULA_TRIGGERS, true) ? "'" . $value : $value;
    }

    protected function escapeSpreadsheetFormulaRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = $this->escapeSpreadsheetFormula($value);
            }
        }

        return $row;
    }

    /**
     * @param BusinessDocument $model
     *
     * @return FormatoDocumento
     */
    protected function getDocumentFormat($model)
    {
        $documentFormat = new FormatoDocumento();
        $where = [
            Where::eq('autoaplicar', true),
            Where::eq('idempresa', $model->idempresa)
        ];

        // Buscamos el formato más específico. No dependemos del ORDER BY porque
        // el orden de los NULL difiere entre MySQL (NULL al final) y PostgreSQL
        // (NULL al principio), así que puntuamos cada coincidencia y nos quedamos
        // con la de mayor prioridad.
        $best = null;
        $bestScore = -1;
        foreach ($documentFormat->all($where) as $format) {
            $score = -1;
            if ($format->tipodoc === $model->modelClassName() && $format->codserie === $model->codserie) {
                $score = 3;
            } elseif ($format->tipodoc === $model->modelClassName() && $format->codserie === null) {
                $score = 2;
            } elseif ($format->tipodoc === null && $format->codserie === $model->codserie) {
                $score = 1;
            } elseif ($format->tipodoc === null && $format->codserie === null) {
                $score = 0;
            }

            if ($score > $bestScore) {
                $best = $format;
                $bestScore = $score;
            }
        }

        return $best ?? $documentFormat;
    }

    /**
     * @param mixed $model
     * @param array $columns
     *
     * @return array
     */
    protected function getModelColumnsData(mixed $model, array $columns): array
    {
        $data = [];
        foreach ($columns as $col) {
            if (is_string($col)) {
                continue;
            }

            if (isset($col->columns)) {
                foreach ($this->getModelColumnsData($model, $col->columns) as $key2 => $col2) {
                    $data[$key2] = $col2;
                }
                continue;
            }

            if (!$col->hidden()) {
                $data[$col->widget->fieldname] = [
                    'title' => Tools::trans($col->title),
                    'value' => $col->widget->plainText($model)
                ];
            }
        }

        return $data;
    }

    /**
     * @param ModelClass $model
     *
     * @return array
     */
    protected function getModelFields($model): array
    {
        $fields = [];
        foreach (array_keys($model->getModelFields()) as $key) {
            $fields[$key] = $key;
        }

        return $fields;
    }

    /**
     * @return string
     */
    protected function getFileName(): string
    {
        return empty($this->fileName) ? 'file_' . mt_rand(1, 9999) : $this->fileName;
    }

    /**
     * @param string $name
     */
    protected function setFileName(string $name)
    {
        if (empty($this->fileName)) {
            $this->fileName = str_replace([' ', '"', "'", '/', '\\', ','], '_', Tools::fixHtml($name));
        }
    }
}
