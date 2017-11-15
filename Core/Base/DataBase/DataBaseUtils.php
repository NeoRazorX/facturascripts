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

/**
 * This class has utilities for the control and management of objects in the
 * database. It needs the link to to the database with its type (MYSQL or
 * PostreSQL) used used when the DataBase class was created. (DataBase::$engine)
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class DataBaseUtils
{

    /**
     * Link to the database engine selected in the configuration
     *
     * @var DataBaseEngine
     */
    private $engine;

    /**
     * Builds and prepares the class to use it
     *
     * @param DataBaseEngine $engine
     */
    public function __construct($engine)
    {
        $this->engine = &$engine;
    }

    /**
     * Busca una columna con un valor por su nombre en un array
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
     * Compares data types from a column.
     * Returns True if they are the same.
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    public function compareDataTypes($dbType, $xmlType)
    {
        $db0 = strtolower($dbType);
        $xml = strtolower($xmlType);

        $result = (
            (FS_DB_TYPE_CHECK) ||
            $this->engine->compareDataTypes($db0, $xml) ||
            ($xml === 'serial') ||
            (
            strpos($db0, 'time') === 0 &&
            strpos($xml, 'time') === 0
            )
            );

        return $result;
    }

    /**
     * Compares two column arrays, returns a SQL statement if there are any differences.
     *
     * @param string $tableName
     * @param array  $xmlCols
     * @param array  $dbCols
     *
     * @return string
     */
    public function compareColumns($tableName, $xmlCols, $dbCols)
    {
        $result = '';
        foreach ($xmlCols as $xml_col) {
            if (strtolower($xml_col['type']) === 'integer') {
                /**
                 *
                 * The integer type used in columns can be changed in the control panel tab
                 */
                $xml_col['type'] = FS_DB_INTEGER;
            }

            $column = $this->searchInArray($dbCols, 'name', $xml_col['name']);
            if (empty($column)) {
                $result .= $this->engine->getSQL()->sqlAlterAddColumn($tableName, $xml_col);
                continue;
            }

            if (!$this->compareDataTypes($column['type'], $xml_col['type'])) {
                $result .= $this->engine->getSQL()->sqlAlterModifyColumn($tableName, $xml_col);
            }

            if ($column['default'] === null && $xml_col['default'] !== '') {
                $result .= $this->engine->getSQL()->sqlAlterConstraintDefault($tableName, $xml_col);
            }

            if ($column['is_nullable'] !== $xml_col['null']) {
                $result .= $this->engine->getSQL()->sqlAlterConstraintNull($tableName, $xml_col);
            }
        }

        return $result;
    }

    /**
     * Compares two constraint arrays, returns a SQL statement if there are any differences.
     *
     * @param string $tableName
     * @param array  $xmlCons
     * @param array  $dbCons
     * @param bool   $deleteOnly
     *
     * @return string
     */
    public function compareConstraints($tableName, $xmlCons, $dbCons, $deleteOnly = false)
    {
        $result = '';

        foreach ($dbCons as $db_con) {
            if (strpos('PRIMARY;UNIQUE', $db_con['name']) === false) {
                $column = $this->searchInArray($xmlCons, 'name', $db_con['name']);
                if (empty($column)) {
                    $result .= $this->engine->getSQL()->sqlDropConstraint($tableName, $db_con);
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
                    $result .= $this->engine->getSQL()->sqlAddConstraint($tableName, $xml_con['name'], $xml_con['constraint']);
                }
            }
        }

        return $result;
    }

    /**
     * Returns the needed SQL statement to create a table with the given structure.
     *
     * @param string $tableName
     * @param array  $xmlCols
     * @param array  $xmlCons
     *
     * @return string
     */
    public function generateTable($tableName, $xmlCols, $xmlCons)
    {
        return $this->engine->getSQL()->sqlCreateTable($tableName, $xmlCols, $xmlCons);
    }

    /**
     * Carga registros desde un archivo CSV a una tabla
     *
     * @param string $filePath
     * @param string $tableName
     * @param string $delimiter
     * @param string $enclosed
     * @param string $escaped
     * @param string $lineEnd
     *
     * @return string
     */
    public function loadFromCSV($filePath, $tableName, $delimiter = ';', $enclosed = '"', $escaped = '\\', $lineEnd = PHP_EOL)
    {
        return $this->engine->getSQL()->sqlLoadFromCSV($filePath, $tableName, $delimiter, $enclosed, $escaped, $lineEnd);
    }
}
