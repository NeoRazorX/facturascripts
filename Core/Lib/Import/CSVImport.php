<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Import;

use FacturaScripts\Core\Base\DataBase;

/**
 * Common CSV import actions.
 *
 * @author Carlos García Gómez
 */
class CSVImport
{

    /**
     * Return the insert SQL reading a CSV file for the specific table
     *
     * @param string $table
     *
     * @return string
     */
    public static function importTableSQL($table)
    {
        $filePath = static::getTableFilePath($table);
        if ($filePath === '') {
            return '';
        }

        $csv = new \parseCSV();
        $csv->auto($filePath);
        $dataBase = new DataBase();

        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $csv->titles) . ') VALUES ';
        $sep = '';
        foreach ($csv->data as $key => $row) {
            $sql .= $sep . '(';
            $sep2 = '';
            foreach ($row as $value) {
                $sql .= $sep2 . self::valueToSql($dataBase, $value);
                $sep2 = ', ';
            }

            $sql .= ')';
            $sep = ', ';
        }
        $sql .= ';';

        return $sql;
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
        if ($value === 'false' || $value === 'true') {
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
    protected static function getTableFilePath($table)
    {
        if (!defined('FS_CODPAIS')) {
            define('FS_CODPAIS', 'ES');
        }

        $filePath = FS_FOLDER . '/Core/Data/Codpais/' . FS_CODPAIS . '/' . $table . '.csv';
        if (file_exists($filePath)) {
            return $filePath;
        }

        $lang = strtoupper(substr(FS_LANG, 0, 2));
        $filePath = FS_FOLDER . '/Core/Data/Lang/' . $lang . '/' . $table . '.csv';
        if (file_exists($filePath)) {
            return $filePath;
        }

        /// If everything else fails
        $filePath = FS_FOLDER . '/Core/Data/Lang/ES/' . $table . '.csv';
        if (file_exists($filePath)) {
            return $filePath;
        }

        return '';
    }
}
