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

use FacturaScripts\Core\Base\Translator;

/**
 * Interface for each of the compatible database engines
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
abstract class DataBaseEngine
{

    /**
     * Contains the translator.
     *
     * @var Translator
     */
    protected $i18n;

    /**
     * Last error message.
     *
     * @var string
     */
    protected $lastErrorMsg = '';

    /**
     * Starts a transaction with the $link connection
     *
     * @param mixed $link
     */
    abstract public function beginTransaction($link);

    /**
     * Closes the connection to the database
     *
     * @param mixed $link
     */
    abstract public function close($link);

    /**
     * Converts the sqlColumns returned data to a working structure
     *
     * @param array $colData
     */
    abstract public function columnFromData($colData);

    /**
     * Commits operations done in the connection since beginTransaction
     *
     * @param mixed $link
     */
    abstract public function commit($link);

    /**
     * Connects to the database
     *
     * @param string $error
     */
    abstract public function connect(&$error);

    /**
     * Last generated error message in a database operation
     *
     * @param mixed $link
     */
    abstract public function errorMessage($link);

    /**
     * Escape the given column name
     *
     * @param mixed  $link
     * @param string $name
     */
    abstract public function escapeColumn($link, $name);

    /**
     * Escape the given string
     *
     * @param mixed  $link
     * @param string $str
     */
    abstract public function escapeString($link, $str);

    /**
     * Runs a DDL statement on the connection.
     * If there is no open transaction, it will create one and end it after the DDL
     *
     * @param mixed  $link
     * @param string $sql
     *
     * @return bool
     */
    abstract public function exec($link, $sql);

    /**
     * Returns the link to the engine's SQL class
     *
     * @return DataBaseQueries
     */
    abstract public function getSQL();

    /**
     * Indicates if the connection has an open transaction
     *
     * @param mixed $link
     */
    abstract public function inTransaction($link);

    /**
     * List the existing tables in the connection
     *
     * @param mixed $link
     */
    abstract public function listTables($link);

    /**
     * Rolls back operations done in the connection since beginTransaction
     *
     * @param mixed $link
     */
    abstract public function rollback($link);

    /**
     * Runs a database statement on the connection
     *
     * @param mixed  $link
     * @param string $sql
     *
     * @return array
     */
    abstract public function select($link, $sql);

    /**
     * Database engine information
     *
     * @param mixed $link
     *
     * @return string
     */
    abstract public function version($link);

    public function __construct()
    {
        $this->i18n = new Translator();
    }

    /**
     * Compares the data types from a numeric column. Returns true if they are equal
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    public function compareDataTypes($dbType, $xmlType)
    {
        return \FS_DB_TYPE_CHECK === false || $dbType === $xmlType || strtolower($xmlType) == 'serial' || substr($dbType, 0, 4) == 'time' && substr($xmlType, 0, 4) == 'time';
    }

    /**
     * Returns the date format from the database engine
     *
     * @return string
     */
    public function dateStyle()
    {
        return 'Y-m-d';
    }

    /**
     * Indicates the operator for the database engine.
     *
     * @param string $operator
     *
     * @return string
     */
    public function getOperator($operator)
    {
        return $operator;
    }

    /**
     * 
     * @param mixed  $link
     * @param string $tableName
     * @param array  $fields
     */
    public function updateSequence($link, $tableName, $fields)
    {
        ;
    }
}
