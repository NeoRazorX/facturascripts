<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Import;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase;
use ParseCsv\Csv;

/**
 * Common CSV import actions.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CSVImport
{

    /**
     * Return the insert SQL reading a CSV file for the specific file
     *
     * @param string  $table
     * @param string  $filePath
     * @param bool    $update
     *
     * @return string
     */
    public static function importFileSQL(string $table, string $filePath, bool $update = false): string
    {
        $csv = new Csv();
        $csv->auto($filePath);
        $dataBase = new DataBase();

        $insertFields = [];
        foreach ($csv->titles as $title) {
            $insertFields[] = $dataBase->escapeColumn($title);
        }

        $insertRows = [];
        foreach ($csv->data as $row) {
            $insertRow = [];
            foreach ($row as $value) {
                $insertRow[] = static::valueToSql($dataBase, $value);
            }

            $insertRows[] = '(' . \implode(',', $insertRow) . ')';
        }

        $sql = 'INSERT INTO ' . $table . ' (' . \implode(',', $insertFields) . ') VALUES ' . \implode(',', $insertRows);
        return $update ? static::insertOnDuplicateSql($sql, $csv) : $sql . ';';
    }

    /**
     * Return the insert SQL reading a CSV file for the specific table
     *
     * @param string $table
     *
     * @return string
     */
    public static function importTableSQL(string $table): string
    {
        $filePath = static::getTableFilePath($table);
        return empty($filePath) ? '' : static::importFileSQL($table, $filePath);
    }

    /**
     *
     * @param string $table
     *
     * @return string
     */
    public static function updateTableSQL(string $table): string
    {
        $filePath = static::getTableFilePath($table);
        return empty($filePath) ? '' : static::importFileSQL($table, $filePath, true);
    }

    /**
     * Returns a value to SQL format.
     *
     * @param DataBase $dataBase
     * @param string   $value
     *
     * @return string
     */
    private static function valueToSql(DataBase &$dataBase, string $value): string
    {
        if ($value === 'false' || $value === 'true' || $value === 'NULL') {
            return $value;
        }

        return $dataBase->var2str($value);
    }

    /**
     * Return the correct filepath for the table
     *
     * @param string $table
     *
     * @return string
     */
    protected static function getTableFilePath(string $table): string
    {
        $codpais = AppSettings::get('default', 'codpais', 'ESP');
        $filePath = \FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/' . $table . '.csv';
        if (\file_exists($filePath)) {
            return $filePath;
        }

        $lang = \strtoupper(\substr(\FS_LANG, 0, 2));
        $filePath = \FS_FOLDER . '/Dinamic/Data/Lang/' . $lang . '/' . $table . '.csv';
        if (\file_exists($filePath)) {
            return $filePath;
        }

        /// If everything else fails
        $filePath = \FS_FOLDER . '/Dinamic/Data/Lang/ES/' . $table . '.csv';
        if (\file_exists($filePath)) {
            return $filePath;
        }

        return '';
    }

    /**
     *
     * @param string $sql
     * @param Csv    $csv
     *
     * @return string
     */
    private static function insertOnDuplicateSql($sql, $csv)
    {
        switch (\FS_DB_TYPE) {
            case 'mysql':
                $sql .= ' ON DUPLICATE KEY UPDATE '
                    . \implode(', ', \array_map(function ($value) {
                            return "{$value} = VALUES({$value})";
                        }, $csv->titles, \array_keys($csv->titles)));
                break;

            case 'postgresql':
                $sql .= ' ON CONFLICT ('
                    . $csv->titles[0]
                    . ') DO UPDATE SET '
                    . \implode(', ', \array_map(function ($value) {
                            return "{$value} = EXCLUDED.{$value}";
                        }, $csv->titles, \array_keys($csv->titles)));
                break;
        }

        return $sql . ';';
    }
}
