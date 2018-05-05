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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Base\DataBase\DataBaseEngine;
use FacturaScripts\Core\Base\DataBase\Mysql;
use FacturaScripts\Core\Base\DataBase\Postgresql;

/**
 * Generic class of access to the database, either MySQL or PostgreSQL.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class DataBase
{
    /**
     * The link with de database.
     *
     * @var resource
     */
    private static $link;

    /**
     * Link to the database engine selected in the configuration.
     *
     * @var DataBaseEngine
     */
    private static $engine;

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
    private static $tables;

    /**
     * DataBase constructor and prepare the class to use it.
     */
    public function __construct()
    {
        if (self::$link === null) {
            self::$miniLog = new MiniLog();
            self::$tables = [];

            switch (strtolower(FS_DB_TYPE)) {
                case 'mysql':
                    self::$engine = new Mysql();
                    break;

                case 'postgresql':
                    self::$engine = new Postgresql();
                    break;

                default:
                    self::$engine = null;
                    $i18n = new Translator();
                    self::$miniLog->critical($i18n->trans('db-type-not-recognized'));
                    break;
            }
        }
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
        if (count(self::$tables) === 0) {
            self::$tables = self::$engine->listTables(self::$link);
        }

        return self::$tables;
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
        if (is_array($data) && !empty($data)) {
            foreach ($data as $dataCol) {
                $column = self::$engine->columnFromData($dataCol);
                $result[$column['name']] = $column;
            }
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
        if ($extended) {
            $sql = self::$engine->getSQL()->sqlConstraintsExtended($tableName);
        } else {
            $sql = self::$engine->getSQL()->sqlConstraints($tableName);
        }

        $data = $this->select($sql);

        return $data ? array_values($data) : [];
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
        if (is_array($data) && !empty($data)) {
            foreach ($data as $row) {
                $result[] = ['name' => $row['Key_name']];
            }
        }

        return $result;
    }

    /**
     * Returns True if it is connected to the database.
     *
     * @return bool
     */
    public function connected()
    {
        return (bool) self::$link;
    }

    /**
     * Connect to the database.
     *
     * @return bool
     */
    public function connect()
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
     * Disconnect from the database.
     *
     * @return bool
     */
    public function close()
    {
        if (!$this->connected()) {
            return true;
        }

        if (self::$engine->inTransaction(self::$link) && !$this->rollback()) {
            return false;
        }

        if (self::$engine->close(self::$link)) {
            self::$link = null;
        }

        return !$this->connected();
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
     * Start a transaction in the database.
     *
     * @return bool
     */
    public function beginTransaction()
    {
        $result = $this->inTransaction();
        if (!$result) {
            self::$miniLog->sql('Begin Transaction');
            $result = self::$engine->beginTransaction(self::$link);
        }

        return $result;
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
            self::$miniLog->sql('Commit Transaction');
        }

        return $result;
    }

    /**
     * Undo the statements executed in the database.
     *
     * @return bool
     */
    public function rollback()
    {
        self::$miniLog->error(self::$engine->errorMessage(self::$link));
        self::$miniLog->sql('Rollback Transaction');

        return self::$engine->rollback(self::$link);
    }

    /**
     * Execute a SQL statement of type select, and return
     * an array with the results, or false in case of failure.
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
     * and return an array with the results or empty array in case of failure.
     * Limit is the number of items you want to return. Offset is the result
     * number from which you want it to start.
     *
     * @param string $sql
     * @param int    $limit
     * @param int    $offset
     *
     * @return array
     */
    public function selectLimit($sql, $limit = FS_ITEM_LIMIT, $offset = 0)
    {
        if (!$this->connected()) {
            return [];
        }

        if ($limit > 0) {
            /// add limit and offset to sql query
            $sql .= ' LIMIT ' . $limit . ' OFFSET ' . $offset . ';';
        }

        /// add the sql query to the history
        self::$miniLog->sql($sql);
        $result = self::$engine->select(self::$link, $sql);
        if (empty($result)) {
            self::$miniLog->critical(self::$engine->errorMessage(self::$link));

            return [];
        }

        return $result;
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

            /// add the sql query to the history
            self::$miniLog->sql($sql);
            $result = self::$engine->exec(self::$link, $sql);
            if (!$inTransaction) {
                /// We only operate if the transaction has been initiated in this call
                if ($result) {
                    $result = $this->commit();
                } else {
                    $this->rollback();
                }
            }
        }

        return $result;
    }

    /**
     * Returns the last ID assigned when doing an INSERT in the database.
     *
     * @return integer|bool
     */
    public function lastval()
    {
        $aux = $this->select(self::$engine->getSQL()->sqlLastValue());

        return $aux ? $aux[0]['num'] : false;
    }

    /**
     * Returns the used database engine and the version.
     *
     * @return string
     */
    public function version()
    {
        if (!$this->connected()) {
            return '';
        }

        return self::$engine->version(self::$link);
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

        return in_array($tableName, $list, false);
    }

    /**
     * Make extra checks on the table.
     *
     * @param string $tableName
     *
     * @return bool
     */
    public function checkTableAux($tableName)
    {
        $error = '';
        $result = self::$engine->checkTableAux(self::$link, $tableName, $error);
        if (!$result) {
            self::$miniLog->critical($error);
        }

        return $result;
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

        if (is_bool($val)) {
            if ($val) {
                return 'TRUE';
            }

            return 'FALSE';
        }

        /// If its a date
        if (preg_match("/^([\d]{1,2})-([\d]{1,2})-([\d]{4})$/i", $val)) {
            return "'" . date($this->dateStyle(), strtotime($val)) . "'";
        }

        /// It its a date time
        if (preg_match("/^([\d]{1,2})-([\d]{1,2})-([\d]{4}) ([\d]{1,2}):([\d]{1,2}):([\d]{1,2})$/i", $val)) {
            return "'" . date($this->dateStyle() . ' H:i:s', strtotime($val)) . "'";
        }

        return "'" . $this->escapeString($val) . "'";
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
        if (self::$engine) {
            $str = self::$engine->escapeString(self::$link, $str);
        }

        return $str;
    }

    /**
     * Returns the date style of the database engine.
     *
     * @return string
     */
    public function dateStyle()
    {
        return self::$engine->dateStyle();
    }

    /**
     * Returns the SQL needed to convert the column to integer.
     *
     * @param string $colName
     *
     * @return string
     */
    public function sql2Int($colName)
    {
        return self::$engine->getSQL()->sql2Int($colName);
    }
}
