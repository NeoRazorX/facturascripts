<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\PDF\Dynamic;

use FacturaScripts\Core\Tools;

/**
 * Extracts titles, alignments and row values from the columns of an XMLView
 * (GroupItem/ColumnItem tree), like the core exporters do (ExportBase), so a
 * model list can be dumped into a generic table block.
 */
class ModelTableHelper
{
    /**
     * @param array $columns GroupItem[] or ColumnItem[]
     */
    public static function alignments(array $columns): array
    {
        $alignments = [];
        foreach ($columns as $col) {
            if (is_string($col)) {
                $alignments[$col] = 'left';
                continue;
            }

            if (isset($col->columns)) {
                foreach (static::alignments($col->columns) as $key => $value) {
                    $alignments[$key] = $value;
                }
                continue;
            }

            if (!$col->hidden()) {
                $alignments[$col->widget->fieldname] = static::mapAlignment($col->display);
            }
        }

        return $alignments;
    }

    /**
     * @param array $cursor ModelClass[]
     * @param array $columns GroupItem[] or ColumnItem[]
     */
    public static function rows(array $cursor, array $columns): array
    {
        $rows = [];
        $widgets = static::widgets($columns);
        foreach ($cursor as $num => $model) {
            foreach ($widgets as $key => $widget) {
                $rows[$num][$key] = $widget->plainText($model);
            }
        }

        return $rows;
    }

    /**
     * @param array $columns GroupItem[] or ColumnItem[]
     */
    public static function titles(array $columns): array
    {
        $titles = [];
        foreach ($columns as $col) {
            if (is_string($col)) {
                $titles[$col] = $col;
                continue;
            }

            if (isset($col->columns)) {
                foreach (static::titles($col->columns) as $key => $value) {
                    $titles[$key] = $value;
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
     * @param array $columns GroupItem[] or ColumnItem[]
     */
    public static function widgets(array $columns): array
    {
        $widgets = [];
        foreach ($columns as $col) {
            if (is_string($col)) {
                continue;
            }

            if (isset($col->columns)) {
                foreach (static::widgets($col->columns) as $key => $value) {
                    $widgets[$key] = $value;
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
     * Removes the columns where every row has an empty value, like the core
     * exporters do (PDFCore::removeEmptyCols). Rows, titles and alignments
     * must share the same keys.
     */
    public static function removeEmptyColumns(array &$rows, array &$titles, array &$alignments, array $emptyValues = []): void
    {
        $emptyValues = array_merge(['', '-', null], $emptyValues);
        foreach (array_keys($titles) as $key) {
            foreach ($rows as $row) {
                if (isset($row[$key]) && false === in_array($row[$key], $emptyValues, true)) {
                    continue 2;
                }
            }

            unset($titles[$key], $alignments[$key]);
            foreach ($rows as $num => $row) {
                unset($rows[$num][$key]);
            }
        }
    }

    protected static function mapAlignment(string $display): string
    {
        switch ($display) {
            case 'center':
                return 'center';

            case 'end':
            case 'right':
                return 'right';

            default:
                return 'left';
        }
    }
}
