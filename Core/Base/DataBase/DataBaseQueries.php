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

/**
 * Interface to manage the SQL statements needed by the database
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
interface DataBaseQueries
{

    /**
     * Statement needed to convert a column to integer
     *
     * @param string $colName
     *
     * @return string
     */
    public function sql2Int($colName);

    /**
     * SQL statement to add a constraint to a given table
     *
     * @param string $tableName
     * @param string $constraintName
     * @param string $sql
     *
     * @return string
     */
    public function sqlAddConstraint($tableName, $constraintName, $sql);

    /**
     * SQL statement to add a given table column
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterAddColumn($tableName, $colData);

    /**
     * SQL statement to alter a given table column's default value
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterColumnDefault($tableName, $colData);

    /**
     * SQL statement to alter a given table column's null constraint
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterColumnNull($tableName, $colData);

    /**
     * SQL statement to alter a given table column's definition
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlAlterModifyColumn($tableName, $colData);

    /**
     * SQL statement to get the columns in a table
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlColumns($tableName);

    /**
     * SQL statement to get the table constraints
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraints($tableName);

    /**
     * SQL statement to get the table extended constraints
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraintsExtended($tableName);

    /**
     * SQL statement to create a table
     *
     * @param string $tableName
     * @param array  $columns
     * @param array  $constraints
     *
     * @return string
     */
    public function sqlCreateTable($tableName, $columns, $constraints);

    /**
     * SQL statement to delete a given table column's constraint
     *
     * @param string $tableName
     * @param array  $colData
     *
     * @return string
     */
    public function sqlDropConstraint($tableName, $colData);

    /**
     * SQL statement to drop a given table
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlDropTable($tableName);

    /**
     * SQL statement to get a given table's indexes
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlIndexes($tableName);

    /**
     * SQL statement to get the last value of a sequence or ID
     *
     * @return string
     */
    public function sqlLastValue();

    /**
     * Generates the SQL to establish the given restrictions.
     *
     * @param array $xmlCons
     *
     * @return string
     */
    public function sqlTableConstraints($xmlCons);
}
