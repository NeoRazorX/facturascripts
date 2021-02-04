<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseEngine;
use FacturaScripts\Core\Base\DataBase\MysqlEngine;
use FacturaScripts\Core\Base\DataBase\PostgresqlEngine;

/**
 * Generic class of access to the database, either MySQL or PostgreSQL.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
final class DataBase
{

    const CHANNEL = 'database';

    /**
     * Link to the database engine selected in the configuration.
     *
     * @var DataBaseEngine
     */
    private static $engine;

    /**
     * The link with de database.
     *
     * @var resource
     */
    private static $link;

    /**
     * Manage the log of all controllers, models and database.
     *
     * @var MiniLog
     */
    private static $miniLog;

    /**
     * List of tables in the database.
     *
     * @var array
     */
    private static $tables = [];

    /**
     * DataBase constructor and prepare the class to use it.
     */
    public function __construct()
    {
        if (self::$link === null) {
            self::$miniLog = new MiniLog(self::CHANNEL);

            switch (strtolower(\FS_DB_TYPE)) {
                case 'postgresql':
                    self::$engine = new PostgresqlEngine();
                    break;

                default:
                    self::$engine = new MysqlEngine();
                    break;
            }
        }
    }

    /**
     * Start a transaction in the database.
     *
     * @return bool
     */
    public function beginTransaction()
    {
        if ($this->inTransaction()) {
            return true;
        }

        self::$miniLog->debug('Begin Transaction');
        return self::$engine->beginTransaction(self::$link);
    }

    /**
     * Disconnect from the database.
     *
     * @return bool
     */
    public function close(): bool
    {
        if (false === $this->connected()) {
            return true;
        }

        if (self::$engine->inTransaction(self::$link) && !$this->rollback()) {
            return false;
        }

        if (self::$engine->close(self::$link)) {
            self::$link = null;
        }

        return false === $this->connected();
    }

    /**
     * Record the statements executed in the database.
     *
     * @return bool
     */
    public function commit()
    {
        $result = self::$engine->commit(self::$link);
        if ($result) {
            self::$miniLog->debug('Commit Transaction');
        }

        return $result;
    }

    /**
     * Connect to the database.
     *
     * @return bool
     */
    public function connect(): bool
    {
        if ($this->connected()) {
            return true;
        }

        $error = '';
        self::$link = self::$engine->connect($error);

        if ($error !== '') {
            self::$miniLog->critical($error);
        }

        return $this->connected();
    }

    /**
     * Returns True if it is connected to the database.
     *
     * @return bool
     */
    public function connected(): bool
    {
        return (bool) self::$link;
    }

    /**
     * Escape the quotes from the column name.
     * 
     * @param string $name
     *
     * @return string
     */
    public function escapeColumn($name)
    {
        return self::$engine->escapeColumn(self::$link, $name);
    }

    /**
     * Escape the quotes from the text string.
     *
     * @param string $str
     *
     * @return string
     */
    public function escapeString($str)
    {
        return self::$engine->escapeString(self::$link, $str);
    }

    /**
     * Execute SQL statements on the database (inserts, updates or deletes).
     * To make selects, it is better to use select () or selecLimit ().
     * If there is no open transaction, one starts, queries are executed
     * If the transaction has opened it in the call, it closes it confirming
     * or discarding according to whether it has gone well or has given an error
     *
     * @param string $sql
     *
     * @return bool
     */
    public function exec($sql)
    {
        $result = $this->connected();
        if ($result) {
            /// clean the list of tables, since there could be changes when executing this sql.
            self::$tables = [];

            $inTransaction = $this->inTransaction();
            $this->beginTransaction();

            /// adds the sql query to the history
            self::$miniLog->debug($sql);

            /// execute sql
            $result = self::$engine->exec(self::$link, $sql);
            if (!$result) {
                self::$miniLog->error(self::$engine->errorMessage(self::$link));
            }

            if ($inTransaction) {
                return $result;
            }

            /// We only operate if the transaction has been initiated in this call
            if ($result) {
                return $this->commit();
            }

            $this->rollback();
        }

        return $result;
    }

    /**
     * Returns an array with the columns of a given table.
     *
     * @param string $tableName
     *
     * @return array
     */
    public function getColumns($tableName)
    {
        $result = [];
        $data = $this->select(self::$engine->getSQL()->sqlColumns($tableName));
        foreach ($data as $row) {
            $column = self::$engine->columnFromData($row);
            $result[$column['name']] = $column;
        }

        return $result;
    }

    /**
     * Returns an array with the constraints of a table.
     *
     * @param string $tableName
     * @param bool   $extended
     *
     * @return array
     */
    public function getConstraints($tableName, $extended = false)
    {
        $sql = $extended ? self::$engine->getSQL()->sqlConstraintsExtended($tableName) : self::$engine->getSQL()->sqlConstraints($tableName);
        $data = $this->select($sql);
        return $data ? \array_values($data) : [];
    }

    /**
     * Return the database engine used
     *
     * @return DataBaseEngine
     */
    public function getEngine()
    {
        return self::$engine;
    }

    /**
     * Returns an array with the indices of a given table.
     *
     * @param string $tableName
     *
     * @return array
     */
    public function getIndexes($tableName)
    {
        $result = [];
        $data = $this->select(self::$engine->getSQL()->sqlIndexes($tableName));
        foreach ($data as $row) {
            $result[] = ['name' => $row['Key_name']];
        }

        return $result;
    }

    /**
     * Gets the operator for the database engine
     *
     * @param string $operator
     *
     * @return string
     */
    public function getOperator($operator)
    {
        return self::$engine->getOperator($operator);
    }

    /**
     * Returns an array with the names of the tables in the database.
     *
     * @return array
     */
    public function getTables()
    {
        if (false === $this->connected()) {
            return [];
        } elseif (empty(self::$tables)) {
            self::$tables = self::$engine->listTables(self::$link);
        }

        return self::$tables;
    }

    /**
     * Indicates if there is an open transaction.
     *
     * @return bool
     */
    public function inTransaction()
    {
        return self::$engine->inTransaction(self::$link);
    }

    /**
     * Returns the last ID assigned when doing an INSERT in the database.
     *
     * @return int|bool
     */
    public function lastval()
    {
        $aux = $this->select(self::$engine->getSQL()->sqlLastValue());
        return empty($aux) ? false : $aux[0]['num'];
    }

    /**
     * Undo the statements executed in the database.
     *
     * @return bool
     */
    public function rollback()
    {
        self::$miniLog->debug('Rollback Transaction');
        return self::$engine->rollback(self::$link);
    }

    /**
     * Execute a SQL statement of type select, and return
     * an array with the results, or an empty array in case of failure.
     *
     * @param string $sql
     *
     * @return array
     */
    public function select($sql)
    {
        return $this->selectLimit($sql, 0);
    }

    /**
     * Execute a SQL statement of type select, but with pagination,
     * and return an array with the results or an empty array in case of failure.
     * Limit is the number of items you want to return. Offset is the result
     * number from which you want it to start.
     *
     * @param string $sql
     * @param int    $limit
     * @param int    $offset
     *
     * @return array
     */
    public function selectLimit($sql, $limit = \FS_ITEM_LIMIT, $offset = 0)
    {
        if (false === $this->connected()) {
            return [];
        }

        if ($limit > 0) {
            /// add limit and offset to sql query
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';';
        }

        /// add the sql query to the history
        self::$miniLog->debug($sql);
        $result = self::$engine->select(self::$link, $sql);
        if (!empty($result)) {
            return $result;
        }

        /// some error?
        $error = self::$engine->errorMessage(self::$link);
        if (!empty($error)) {
            self::$miniLog->critical($error);
        }

        return [];
    }

    /**
     * Returns True if the table exists, False otherwise.
     *
     * @param string $tableName
     * @param array  $list
     *
     * @return bool
     */
    public function tableExists($tableName, array $list = [])
    {
        if (empty($list)) {
            $list = $this->getTables();
        }

        return \in_array($tableName, $list, false);
    }

    /**
     * 
     * @param string $tableName
     * @param array  $fields
     */
    public function updateSequence($tableName, $fields)
    {
        self::$engine->updateSequence(self::$link, $tableName, $fields);
    }

    /**
     * Transforms a variable into a valid text string to be used in a SQL query.
     *
     * @param mixed $val
     *
     * @return string
     */
    public function var2str($val)
    {
        if ($val === null) {
            return 'NULL';
        }

        if (\is_bool($val)) {
            return $val ? 'TRUE' : 'FALSE';
        }

        /// If its a date
        if (\preg_match("/^([\d]{1,2})-([\d]{1,2})-([\d]{4})$/i", $val)) {
            return "'" . \date(self::$engine->dateStyle(), \strtotime($val)) . "'";
        }

        /// It its a date time
        if (\preg_match("/^([\d]{1,2})-([\d]{1,2})-([\d]{4}) ([\d]{1,2}):([\d]{1,2}):([\d]{1,2})$/i", $val)) {
            return "'" . \date(self::$engine->dateStyle() . ' H:i:s', \strtotime($val)) . "'";
        }

        return "'" . $this->escapeString($val) . "'";
    }

    /**
     * Returns the used database engine and the version.
     *
     * @return string
     */
    public function version()
    {
        return $this->connected() ? self::$engine->version(self::$link) : '';
    }
}
