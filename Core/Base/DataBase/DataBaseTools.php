<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base\DataBase;

use FacturaScripts\Core\Base\DataBase as db;
use FacturaScripts\Core\Base\ToolBox;

/**
 * This class group all method for DataBase, tools like check/generate table, compare constraints/columns, ...
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * The DataBaseQueries object.
     *
     * @var DataBaseQueries
     */
    private static $sql;

    /**
     * DataBaseTools constructor.
     */
    public function __construct()
    {
        if (!isset(self::$dataBase)) {
            self::$dataBase = new db();
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
        /**
         * If we have to make changes to the restrictions, we first eliminate them all.
         * Then we will add the correct ones. We do it like this because it avoids problems in MySQL.
         */
        $dbCons = self::$dataBase->getConstraints($tableName);
        $sql2 = $this->compareConstraints($tableName, $xmlCons, $dbCons, true);
        if ($sql2 !== '') {
            if (!self::$dataBase->exec($sql2)) {
                $this->toolBox()->i18nLog()->critical('check-table', ['%tableName%' => $tableName]);
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
     * @return string
     */
    public function generateTable($tableName, $xmlCols, $xmlCons)
    {
        return self::$sql->sqlCreateTable($tableName, $xmlCols, $xmlCons);
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
        $filename = $this->getXmlTableLocation($tableName);
        if (!file_exists($filename)) {
            $this->toolBox()->i18nLog()->critical('file-not-found', ['%fileName%' => $filename]);
            return false;
        }

        $xml = simplexml_load_string(file_get_contents($filename, true));
        if (false === $xml) {
            $this->toolBox()->i18nLog()->critical('error-reading-file', ['%fileName%' => $filename]);
            return false;
        }

        /// columns must exists or function must return false
        if (!isset($xml->column)) {
            return false;
        }

        $this->checkXmlColumns($columns, $xml);
        if ($xml->constraint) {
            $this->checkXmlConstraints($constraints, $xml);
        }

        return true;
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
        foreach ($xmlCols as $xmlCol) {
            $column = $this->searchInArray($dbCols, 'name', $xmlCol['name']);
            if (empty($column)) {
                $result .= self::$sql->sqlAlterAddColumn($tableName, $xmlCol);
                continue;
            }

            if (!$this->compareDataTypes($column['type'], $xmlCol['type'])) {
                $result .= self::$sql->sqlAlterModifyColumn($tableName, $xmlCol);
            }

            if ($column['default'] === null && $xmlCol['default'] !== '') {
                $result .= self::$sql->sqlAlterColumnDefault($tableName, $xmlCol);
            }

            if ($column['is_nullable'] !== $xmlCol['null']) {
                $result .= self::$sql->sqlAlterColumnNull($tableName, $xmlCol);
            }
        }

        return $result;
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

        foreach ($dbCons as $dbCon) {
            if (strpos('PRIMARY;UNIQUE', $dbCon['name']) === false) {
                $column = $this->searchInArray($xmlCons, 'name', $dbCon['name']);
                if (empty($column)) {
                    $result .= self::$sql->sqlDropConstraint($tableName, $dbCon);
                }
            }
        }

        if (!empty($xmlCons) && !$deleteOnly && \FS_DB_FOREIGN_KEYS) {
            foreach ($xmlCons as $xmlCon) {
                /// exclude primary keys on mysql because of fail
                if (strpos($xmlCon['constraint'], 'PRIMARY') === 0 && strtolower(\FS_DB_TYPE) === 'mysql') {
                    continue;
                }

                $column = $this->searchInArray($dbCons, 'name', $xmlCon['name']);
                if (empty($column)) {
                    $result .= self::$sql->sqlAddConstraint($tableName, $xmlCon['name'], $xmlCon['constraint']);
                }
            }
        }

        return $result;
    }

    /**
     * Compares data types from a column. Returns True if they are the same.
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    private function compareDataTypes($dbType, $xmlType)
    {
        return self::$dataBase->getEngine()->compareDataTypes($dbType, $xmlType);
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
        $fileName = \FS_FOLDER . '/Dinamic/Table/' . $tableName . '.xml';
        if (\FS_DEBUG && !file_exists($fileName)) {
            return \FS_FOLDER . '/Core/Table/' . $tableName . '.xml';
        }

        return $fileName;
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
     * 
     * @return ToolBox
     */
    private function toolBox()
    {
        return new ToolBox();
    }
}
