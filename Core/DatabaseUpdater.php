<?php
declare(strict_types=1);
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseQueries;
use FacturaScripts\Core\Base\ToolBox;
use SimpleXMLElement;

/**
 * This class allows to read and check the required structure for the database tables
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class DatabaseUpdater
{
    public const CHECKED_TABLES_FILE_PATH = FS_FOLDER . '/MyFiles/DatabaseUpdater.json';

    private static DataBase $dataBase;
    private static array $checked_tables;

    /**
     * Checks and compares the database table structure with the xml definition.
     * Returns a SQL statement if there are differences.
     *
     * @param string $tableName
     * @param array $xmlCols
     * @param array $xmlCons
     *
     * @return string
     */
    public static function checkTable(string $tableName, array $xmlCols, array $xmlCons): string
    {
        // compare table columns and constraints against xml definition
        $dbCols = static::dataBase()->getColumns($tableName);
        $dbCons = static::dataBase()->getConstraints($tableName);
        return static::compareColumns($tableName, $xmlCols, $dbCols) .
            static::compareConstraints($tableName, $xmlCons, $dbCons);
    }

    /**
     * Creates the database table with the provided structure.
     *
     * @param string $tableName
     * @param array $xmlCols
     * @param array $xmlCons
     *
     * @return string
     */
    public static function generateTable(string $tableName, array $xmlCols, array $xmlCons): string
    {
        return static::sql()->sqlCreateTable($tableName, $xmlCols, $xmlCons);
    }

    /**
     * Extracts columns and constraints form the XML definition.
     *
     * @param string $tableName
     * @param array $columns
     * @param array $constraints
     *
     * @return bool
     */
    public static function getXmlTable(string $tableName, array &$columns, array &$constraints): bool
    {
        $filename = static::getXmlTableLocation($tableName);
        if (false === file_exists($filename)) {
            ToolBox::i18nLog()->critical('file-not-found', ['%fileName%' => $filename]);
            return false;
        }

        $xml = simplexml_load_string(file_get_contents($filename, true));
        if (false === $xml) {
            ToolBox::i18nLog()->critical('error-reading-file', ['%fileName%' => $filename]);
            return false;
        }

        if ($xml->column) {
            static::checkXmlColumns($columns, $xml);
            if ($xml->constraint) {
                static::checkXmlConstraints($constraints, $xml);
            }

            return true;
        }

        return false;
    }

    /**
     * Returns the full file path for table XML file.
     *
     * @param string $tableName
     *
     * @return string
     */
    public static function getXmlTableLocation(string $tableName): string
    {
        $fileName = FS_FOLDER . '/Dinamic/Table/' . $tableName . '.xml';
        if (FS_DEBUG && false === file_exists($fileName)) {
            return FS_FOLDER . '/Core/Table/' . $tableName . '.xml';
        }

        return $fileName;
    }

    /**
     * Updates names and types for each column.
     *
     * @param array $columns
     * @param SimpleXMLElement $xml
     */
    private static function checkXmlColumns(array &$columns, SimpleXMLElement $xml)
    {
        $key = 0;
        foreach ($xml->column as $col) {
            $columns[$key]['name'] = (string)$col->name;
            $columns[$key]['type'] = (string)$col->type;
            $columns[$key]['null'] = (string)$col->null && strtolower((string)$col->null) === 'no' ? 'NO' : 'YES';
            $columns[$key]['default'] = $col->default === '' ? null : (string)$col->default;
            ++$key;
        }
    }

    /**
     * Updates names and constraints for each constraint.
     *
     * @param array $constraints
     * @param SimpleXMLElement $xml
     */
    private static function checkXmlConstraints(array &$constraints, SimpleXMLElement $xml)
    {
        $key = 0;
        foreach ($xml->constraint as $col) {
            $constraints[$key]['name'] = (string)$col->name;
            $constraints[$key]['constraint'] = (string)$col->type;
            ++$key;
        }
    }

    /**
     * Compares two arrays of columns, returns a SQL statement if there are differences.
     *
     * @param string $tableName
     * @param array $xmlCols
     * @param array $dbCols
     *
     * @return string
     */
    private static function compareColumns(string $tableName, array $xmlCols, array $dbCols): string
    {
        $sql = '';
        foreach ($xmlCols as $xmlCol) {
            $column = static::searchInArray($dbCols, 'name', $xmlCol['name']);
            if (empty($column)) {
                $sql .= static::sql()->sqlAlterAddColumn($tableName, $xmlCol);
                continue;
            }

            if (false === static::compareDataTypes($column['type'], $xmlCol['type'])) {
                $sql .= static::sql()->sqlAlterModifyColumn($tableName, $xmlCol);
            }

            if ($column['default'] === null && $xmlCol['default'] !== '') {
                $sql .= static::sql()->sqlAlterColumnDefault($tableName, $xmlCol);
            }

            if ($column['is_nullable'] !== $xmlCol['null']) {
                $sql .= static::sql()->sqlAlterColumnNull($tableName, $xmlCol);
            }
        }

        return $sql;
    }

    /**
     * Compares two arrays of constraints, returns a SQL statement if there are differences.
     *
     * @param string $tableName
     * @param array $xmlCons
     * @param array $dbCons
     *
     * @return string
     */
    private static function compareConstraints(string $tableName, array $xmlCons, array $dbCons): string
    {
        if (empty($xmlCons) || false === FS_DB_FOREIGN_KEYS) {
            return '';
        }

        // if you have to delete a constraint, it is better to delete them all
        $deleteCons = false;
        $sqlDelete = '';
        $sqlDeleteFK = '';
        foreach ($dbCons as $dbCon) {
            if ($dbCon['type'] === 'PRIMARY KEY') {
                // exclude primary key
                continue;
            } elseif ($dbCon['type'] === 'FOREIGN KEY') {
                // it is better to delete the foreign keys before the rest
                $sqlDeleteFK .= static::sql()->sqlDropConstraint($tableName, $dbCon);
            } else {
                $sqlDelete .= static::sql()->sqlDropConstraint($tableName, $dbCon);
            }

            $column = static::searchInArray($xmlCons, 'name', $dbCon['name']);
            if (empty($column)) {
                $deleteCons = true;
            }
        }

        // add new constraints
        $sql = '';
        foreach ($xmlCons as $xmlCon) {
            // exclude primary keys because they have no name
            if (0 === strpos($xmlCon['constraint'], 'PRIMARY')) {
                continue;
            }

            $column = static::searchInArray($dbCons, 'name', $xmlCon['name']);
            if (empty($column)) {
                $sql .= static::sql()->sqlAddConstraint($tableName, $xmlCon['name'], $xmlCon['constraint']);
            }
        }

        return $deleteCons ? $sqlDeleteFK . $sqlDelete . $sql : $sql;
    }

    /**
     * Compares data types from a column. Returns True if they are the same.
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    private static function compareDataTypes(string $dbType, string $xmlType): bool
    {
        return static::dataBase()->getEngine()->compareDataTypes($dbType, $xmlType);
    }

    private static function dataBase(): DataBase
    {
        if (!isset(self::$dataBase)) {
            self::$dataBase = new DataBase();
        }

        return self::$dataBase;
    }

    /**
     * Look for a column with a value by his name in array.
     *
     * @param array $items
     * @param string $index
     * @param string $value
     *
     * @return array
     */
    private static function searchInArray(array $items, string $index, string $value): array
    {
        foreach ($items as $column) {
            if ($column[$index] === $value) {
                return $column;
            }
        }

        return [];
    }

    private static function sql(): DataBaseQueries
    {
        return static::dataBase()->getEngine()->getSQL();
    }

    /**
     * Elimina el archivo DatabaseUpdater.json donde se encuentran
     * almacenadas las tablas comprobadas. Igualmente con la
     * variable estatica $checked_tables
     * @return bool
     */
    public static function removeCheckedTablesFile(): bool
    {
        $file_path = self::CHECKED_TABLES_FILE_PATH;
        $response = true;

        // Eliminamos el archivo que continen las tablas comprobadas
        if (file_exists($file_path)) {
            $response = unlink($file_path);
        }

        // Eliminamos las tablas comprobadas del array
        // que continen las tablas comprobadas
        self::$checked_tables = [];

        return $response;
    }


    /**
     * Comprueba si la tabla se ha comprobado anteriormente
     * @param string $table_name
     * @return bool
     */
    public static function tableChecked(string $table_name): bool
    {
        if ([] === self::$checked_tables) {
            self::$checked_tables = self::loadCheckedTablesFromFile(self::CHECKED_TABLES_FILE_PATH);
        }

        return in_array($table_name, self::$checked_tables);
    }

    /**
     * Devuelve un array con las tablas comprobadas
     * almacenadas en el archivo DatabaseUpdater.json
     * @param string $file_path
     * @return array
     */
    private static function loadCheckedTablesFromFile(string $file_path): array
    {
        // Si no existe el archivo lo creamos con un array vacío
        if (!file_exists($file_path)) {
            // Si no existe el directorio, lo creamos
            $folder = dirname($file_path);
            if (false === file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
            // Creamos el archivo
            file_put_contents($file_path, json_encode([]));
        }

        return json_decode(file_get_contents($file_path));
    }

    /**
     * Agregamos la tabla al array de tablas comprobadas
     * @param string $table_name
     */
    public static function addCheckedTable(string $table_name)
    {
        // Agregamos al array la tabla comprobada
        array_push(self::$checked_tables, $table_name);

        // Guardamos las tablas checkeadas en el archivo
        file_put_contents(self::CHECKED_TABLES_FILE_PATH, json_encode(self::$checked_tables));

        ToolBox::i18nLog()->debug('table-checked', ['%tableName%' => $table_name]);
    }
}
