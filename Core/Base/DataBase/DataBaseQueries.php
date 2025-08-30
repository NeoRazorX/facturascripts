<?php
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

namespace FacturaScripts\Core\Base\DataBase;

/**
 * Interface to manage the SQL statements needed by the database
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
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
    public function sql2Int(string $colName): string;

    /**
     * SQL statement to add a constraint to a given table
     *
     * @param string $tableName
     * @param string $constraintName
     * @param string $sql
     *
     * @return string
     */
    public function sqlAddConstraint(string $tableName, string $constraintName, string $sql): string;

    /**
     * SQL statement to add a given table column
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterAddColumn(string $tableName, array $colData): string;

    /**
     * SQL statement to alter a given table column's default value
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterColumnDefault(string $tableName, array $colData): string;

    /**
     * SQL statement to alter a given table column's null constraint
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterColumnNull(string $tableName, array $colData): string;

    /**
     * SQL statement to alter a given table column's definition
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlAlterModifyColumn(string $tableName, array $colData): string;

    /**
     * SQL statement to get the columns in a table
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlColumns(string $tableName): string;

    /**
     * SQL statement to get the table constraints
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraints(string $tableName): string;

    /**
     * SQL statement to get the table extended constraints
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlConstraintsExtended(string $tableName): string;

    /**
     * SQL statement to create a table
     *
     * @param string $tableName
     * @param array $columns
     * @param array $constraints
     * @param array $indexes
     *
     * @return string
     */
    public function sqlCreateTable(string $tableName, array $columns, array $constraints, array $indexes): string;

    /**
     * SQL statement to delete a given table column's constraint
     *
     * @param string $tableName
     * @param array $colData
     *
     * @return string
     */
    public function sqlDropConstraint(string $tableName, array $colData): string;

    /**
     * SQL statement to drop a given table
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlDropTable(string $tableName): string;

    /**
     * SQL statement to get a given table's indexes
     *
     * @param string $tableName
     *
     * @return string
     */
    public function sqlIndexes(string $tableName): string;

    /**
     * SQL statement to get the last value of a sequence or ID
     *
     * @return string
     */
    public function sqlLastValue(): string;

    /**
     * SQL statement to rename a column.
     *
     * @param string $tableName
     * @param string $old_column
     * @param string $new_column
     *
     * @return string
     */
    public function sqlRenameColumn(string $tableName, string $old_column, string $new_column): string;

    /**
     * Generates the SQL to establish the given restrictions.
     *
     * @param array $xmlCons
     *
     * @return string
     */
    public function sqlTableConstraints(array $xmlCons): string;
}
