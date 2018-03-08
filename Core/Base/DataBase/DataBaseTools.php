<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base\DataBase;

use FacturaScripts\Core\Base\DataBase as db;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;

/**
 * This class group all method for DataBase, tools like check/generate table, compare constraints/columns, ...
 *
 * @author Carlos García Gómez
 */
class DataBaseTools
{
    /**
     * DataBase object.
     *
     * @var db
     */
    private static $dataBase;

    /**
     * System translator.
     *
     * @var Translator
     */
    private static $i18n;

    /**
     * Manage the log of the entire application.
     *
     * @var MiniLog
     */
    private static $miniLog;

    /**
     * The DataBaseSQL object.
     *
     * @var DataBaseSQL
     */
    private static $sql;

    /**
     * DataBaseTools constructor.
     */
    public function __construct()
    {
        if (!isset(self::$dataBase)) {
            self::$dataBase = new db();
            self::$i18n = new Translator();
            self::$miniLog = new MiniLog();
            self::$sql = self::$dataBase->getEngine()->getSQL();
        }
    }

    /**
     * Checks to the database table
     *
     * @param $tableName
     * @param $xmlCols
     * @param $xmlCons
     *
     * @return string
     */
    public function checkTable($tableName, $xmlCols, $xmlCons)
    {
        if (!self::$dataBase->checkTableAux($tableName)) {
            self::$miniLog->critical(self::$i18n->trans('error-to-innodb'));
        }

        /**
         * Si hay que hacer cambios en las restricciones, eliminamos todas las restricciones,
         * luego añadiremos las correctas. Lo hacemos así porque evita problemas en MySQL.
         */
        $dbCons = self::$dataBase->getConstraints($tableName);
        $sql2 = $this->compareConstraints($tableName, $xmlCons, $dbCons, true);
        if ($sql2 !== '') {
            if (!self::$dataBase->exec($sql2)) {
                self::$miniLog->critical(self::$i18n->trans('check-table', ['%tableName%' => $tableName]));
            }

            /// leemos de nuevo las restricciones
            $dbCons = self::$dataBase->getConstraints($tableName);
        }

        /// comparamos las columnas
        $dbCols = self::$dataBase->getColumns($tableName);
        $sql = $this->compareColumns($tableName, $xmlCols, $dbCols);

        /// comparamos las restricciones
        $sql .= $this->compareConstraints($tableName, $xmlCons, $dbCons);

        return $sql;
    }

    /**
     * Create the table with the structure received.
     *
     * @param string $tableName
     * @param array  $xmlCols
     * @param array  $xmlCons
     *
     * @return bool
     */
    public function generateTable($tableName, $xmlCols, $xmlCons)
    {
        return self::$sql->sqlCreateTable($tableName, $xmlCols, $xmlCons);
    }

    /**
     * Compare two arrays with restrictions, return a SQL statement if founded differencies.
     *
     * @param string $tableName
     * @param array  $xmlCons
     * @param array  $dbCons
     * @param bool   $deleteOnly
     *
     * @return string
     */
    private function compareConstraints($tableName, $xmlCons, $dbCons, $deleteOnly = false)
    {
        $result = '';

        foreach ($dbCons as $db_con) {
            if (strpos('PRIMARY;UNIQUE', $db_con['name']) === false) {
                $column = $this->searchInArray($xmlCons, 'name', $db_con['name']);
                if (empty($column)) {
                    $result .= self::$sql->sqlDropConstraint($tableName, $db_con);
                }
            }
        }

        if (!empty($xmlCons) && !$deleteOnly && FS_DB_FOREIGN_KEYS) {
            foreach ($xmlCons as $xml_con) {
                if (strpos($xml_con['constraint'], 'PRIMARY') === 0) {
                    continue;
                }

                $column = $this->searchInArray($dbCons, 'name', $xml_con['name']);
                if (empty($column)) {
                    $result .= self::$sql->sqlAddConstraint($tableName, $xml_con['name'], $xml_con['constraint']);
                }
            }
        }

        return $result;
    }

    /**
     * Look for a column with a value by his name in array.
     *
     * @param array  $items
     * @param string $index
     * @param string $value
     *
     * @return array
     */
    private function searchInArray($items, $index, $value)
    {
        $result = [];
        foreach ($items as $column) {
            if ($column[$index] === $value) {
                $result = $column;
                break;
            }
        }

        return $result;
    }

    /**
     * Compare two arrays of columns, return a SQL statement if founded differencies.
     *
     * @param string $tableName
     * @param array  $xmlCols
     * @param array  $dbCols
     *
     * @return string
     */
    private function compareColumns($tableName, $xmlCols, $dbCols)
    {
        $result = '';
        foreach ($xmlCols as $xml_col) {
            if (strtolower($xml_col['type']) === 'integer') {
                /**
                 * The integer type used in columns can be changed in the control panel tab
                 */
                $xml_col['type'] = FS_DB_INTEGER;
            }

            $column = $this->searchInArray($dbCols, 'name', $xml_col['name']);
            if (empty($column)) {
                $result .= self::$sql->sqlAlterAddColumn($tableName, $xml_col);
                continue;
            }

            if (!$this->compareDataTypes($column['type'], $xml_col['type'])) {
                $result .= self::$sql->sqlAlterModifyColumn($tableName, $xml_col);
            }

            if ($column['default'] === null && $xml_col['default'] !== '') {
                $result .= self::$sql->sqlAlterConstraintDefault($tableName, $xml_col);
            }

            if ($column['is_nullable'] !== $xml_col['null']) {
                $result .= self::$sql->sqlAlterConstraintNull($tableName, $xml_col);
            }
        }

        return $result;
    }

    /**
     * Compares data types from a column.
     * Returns True if they are the same.
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    private function compareDataTypes($dbType, $xmlType)
    {
        $db0 = strtolower($dbType);
        $xml = strtolower($xmlType);

        $result = (
            FS_DB_TYPE_CHECK ||
            self::$dataBase->getEngine()->compareDataTypes($db0, $xml) ||
            ($xml === 'serial') ||
            (
            strpos($db0, 'time') === 0 &&
            strpos($xml, 'time') === 0
            )
            );

        return $result;
    }

    /**
     * Extract columns and restrictions form the XML definition file of a Table.
     *
     * @param string $tableName
     * @param array  $columns
     * @param array  $constraints
     *
     * @return bool
     */
    public function getXmlTable($tableName, &$columns, &$constraints)
    {
        $return = false;
        $filename = $this->getXmlTableLocation($tableName);

        if (file_exists($filename)) {
            $xml = simplexml_load_string(file_get_contents($filename, true));
            if ($xml) {
                if ($xml->column) {
                    $this->checkXmlColumns($columns, $xml);

                    /// columns must exists or function must return false
                    $return = true;
                }

                if ($xml->constraint) {
                    $this->checkXmlConstraints($constraints, $xml);
                }
            } else {
                self::$miniLog->critical(self::$i18n->trans('error-reading-file', ['%fileName%' => $filename]));
            }
        } else {
            self::$miniLog->critical(self::$i18n->trans('file-not-found', ['%fileName%' => $filename]));
        }

        return $return;
    }

    /**
     * Return the full file path for table XML file.
     *
     * @param string $tableName
     *
     * @return string
     */
    private function getXmlTableLocation($tableName)
    {
        $fileName = FS_FOLDER . '/Dinamic/Table/' . $tableName . '.xml';
        if (FS_DEBUG && !file_exists($fileName)) {
            $fileName = FS_FOLDER . '/Core/Table/' . $tableName . '.xml';
        }

        return $fileName;
    }

    /**
     * Update the name and type foreach column from the XML
     *
     * @param $columns
     * @param $xml
     */
    private function checkXmlColumns(&$columns, $xml)
    {
        $key = 0;
        foreach ($xml->column as $col) {
            $columns[$key]['name'] = (string) $col->name;
            $columns[$key]['type'] = (string) $col->type;

            $columns[$key]['null'] = 'YES';
            if ($col->null && strtolower($col->null) === 'no') {
                $columns[$key]['null'] = 'NO';
            }

            if ($col->default === '') {
                $columns[$key]['default'] = null;
            } else {
                $columns[$key]['default'] = (string) $col->default;
            }

            ++$key;
        }
    }

    /**
     * Update the name and constraint foreach constraint from the XML
     *
     * @param array             $constraints
     * @param \SimpleXMLElement $xml
     */
    private function checkXmlConstraints(&$constraints, $xml)
    {
        $key = 0;
        foreach ($xml->constraint as $col) {
            $constraints[$key]['name'] = (string) $col->name;
            $constraints[$key]['constraint'] = (string) $col->type;
            ++$key;
        }
    }
}
