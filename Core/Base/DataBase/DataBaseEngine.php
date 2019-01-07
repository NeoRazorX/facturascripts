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
 * Interface for each of the compatible database engines
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
interface DataBaseEngine
{

    /**
     * Starts a transaction with the $link connection
     *
     * @param \mysqli|resource $link
     */
    public function beginTransaction($link);

    /**
     * Checks if a sequence exists
     *
     * @param \mysqli|resource $link
     * @param string           $tableName
     * @param string           $default
     * @param string           $colname
     */
    public function checkSequence($link, $tableName, $default, $colname);

    /**
     * Additional check to see if a table exists
     *
     * @param \mysqli|resource $link
     * @param string           $tableName
     * @param string           $error
     */
    public function checkTableAux($link, $tableName, &$error);

    /**
     * Closes the connection to the database
     *
     * @param \mysqli|resource $link
     */
    public function close($link);

    /**
     * Converts the sqlColumns returned data to a working structure
     *
     * @param array $colData
     */
    public function columnFromData($colData);

    /**
     * Commits operations done in the connection since beginTransaction
     *
     * @param \mysqli|resource $link
     */
    public function commit($link);

    /**
     * Compares the columns set in the arrays
     *
     * @param string $dbType
     * @param string $xmlType
     */
    public function compareDataTypes($dbType, $xmlType);

    /**
     * Connects to the database
     *
     * @param string $error
     */
    public function connect(&$error);

    /**
     * Returns the database date format
     */
    public function dateStyle();

    /**
     * Last generated error message in a database operation
     *
     * @param \mysqli|resource $link
     */
    public function errorMessage($link);

    /**
     * Escape the given string
     *
     * @param \mysqli|resource $link
     * @param string           $str
     */
    public function escapeString($link, $str);

    /**
     * Runs a DDL statement on the connection.
     * If there is no open transaction, it will create one and end it after the DDL
     *
     * @param \mysqli|resource $link
     * @param string           $sql
     *
     * @return bool
     */
    public function exec($link, $sql);

    /**
     * Indicates the operator for the database engine
     *
     * @param string $operator
     */
    public function getOperator($operator);

    /**
     * Returns the link to the engine's SQL class
     *
     * @return DataBaseSQL
     */
    public function getSQL();

    /**
     * Indicates if the connection has an open transaction
     *
     * @param \mysqli|resource $link
     */
    public function inTransaction($link);

    /**
     * List the existing tables in the connection
     *
     * @param \mysqli|resource $link
     */
    public function listTables($link);

    /**
     * Rolls back operations done in the connection since beginTransaction
     *
     * @param \mysqli|resource $link
     */
    public function rollback($link);

    /**
     * Runs a database statement on the connection
     *
     * @param \mysqli|resource $link
     * @param string           $sql
     *
     * @return array
     */
    public function select($link, $sql);

    /**
     * Database engine information
     *
     * @param \mysqli|resource $link
     *
     * @return string
     */
    public function version($link);
}
