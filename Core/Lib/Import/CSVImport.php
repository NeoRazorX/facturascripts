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

namespace FacturaScripts\Core\Lib\Import;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;
use ParseCsv\Csv;

/**
 * Common CSV import actions.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class CSVImport
{
    /**
     * Return the correct filepath for the table
     *
     * @param string $table
     *
     * @return string
     */
    public static function getTableFilePath(string $table): string
    {
        if ($table === 'settings') {
            return '';
        }

        $codpais = Tools::settings('default', 'codpais', 'ESP');
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/' . $codpais . '/' . $table . '.csv';
        if (file_exists($filePath)) {
            return $filePath;
        }

        $config_lang = Tools::config('lang');
        $lang = strtoupper(substr($config_lang, 0, 2));
        $filePath = FS_FOLDER . '/Dinamic/Data/Lang/' . $lang . '/' . $table . '.csv';
        if (file_exists($filePath)) {
            return $filePath;
        }

        // If everything else fails
        $filePath = FS_FOLDER . '/Dinamic/Data/Lang/ES/' . $table . '.csv';
        if (file_exists($filePath)) {
            return $filePath;
        }

        return '';
    }

    /**
     * Return the insert SQL reading a CSV file for the specific file
     *
     * @param string $table
     * @param string $filePath
     * @param bool $update
     *
     * @return string
     */
    public static function importFileSQL(string $table, string $filePath, bool $update = false): string
    {
        $csv = static::readFile($filePath);

        // si no se ha detectado el delimitador, forzamos ;
        if (empty($csv->delimiter)) {
            $csv->delimiter = ';';
            $csv->loadDataString(static::fileContentUtf8($filePath));
            $csv->parseFile();
        }

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

            $insertRows[] = '(' . implode(',', $insertRow) . ')';
        }
        if (empty($insertRows)) {
            return '';
        }

        $sql = 'INSERT INTO ' . $table . ' (' . implode(',', $insertFields) . ') VALUES ' . implode(',', $insertRows);
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
     * Lee un fichero CSV, detectando el delimitador, y devuelve el lector con los datos
     * en UTF-8. ParseCsv convierte a ISO-8859-1 los ficheros con BOM UTF-8, como los que
     * genera CSVExport, así que la codificación se normaliza antes de pasarle el contenido.
     *
     * @param string $filePath
     *
     * @return Csv
     */
    public static function readFile(string $filePath): Csv
    {
        $csv = new Csv();
        if ($csv->loadDataString(static::fileContentUtf8($filePath))) {
            $csv->auto();
        }

        return $csv;
    }

    /**
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
     * Devuelve el contenido del fichero en UTF-8 y sin BOM. Si no tiene BOM y no es UTF-8
     * válido, se interpreta como Windows-1252 (el ANSI de Excel, que incluye ISO-8859-1).
     * Igual que ParseCsv al leer el fichero, se quitan los saltos de línea finales, porque
     * una línea vacía al final impide detectar el delimitador.
     *
     * @param string $filePath
     *
     * @return string
     */
    private static function fileContentUtf8(string $filePath): string
    {
        $content = is_file($filePath) ? (string)file_get_contents($filePath) : '';
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        } elseif (str_starts_with($content, "\xFF\xFE")) {
            $content = (string)mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($content, "\xFE\xFF")) {
            $content = (string)mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        } elseif (false === mb_check_encoding($content, 'UTF-8')) {
            $content = (string)mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        return rtrim($content, "\r\n");
    }

    /**
     * Returns a value to SQL format.
     *
     * @param DataBase $dataBase
     * @param string $value
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
     * @param string $sql
     * @param Csv $csv
     *
     * @return string
     */
    private static function insertOnDuplicateSql($sql, $csv): string
    {
        switch (Tools::config('db_type')) {
            case 'mysql':
                $sql .= ' ON DUPLICATE KEY UPDATE '
                    . implode(', ', array_map(function ($value) {
                        return "{$value} = VALUES({$value})";
                    }, $csv->titles, array_keys($csv->titles)));
                break;

            case 'postgresql':
            case 'sqlite':
                $sql .= ' ON CONFLICT ('
                    . $csv->titles[0]
                    . ') DO UPDATE SET '
                    . implode(', ', array_map(function ($value) {
                        return "{$value} = EXCLUDED.{$value}";
                    }, $csv->titles, array_keys($csv->titles)));
                break;
        }

        return $sql . ';';
    }
}
