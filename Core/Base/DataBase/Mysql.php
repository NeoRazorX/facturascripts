<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Base\Translator;
use mysqli;

/**
 * Class to connect with MySQL.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Mysql implements DataBaseEngine
{

    /**
     * Contains the translator.
     *
     * @var Translator
     */
    private $i18n;

    /**
     * Last error message.
     *
     * @var string
     */
    private $lastErrorMsg;

    /**
     * Open transaction list.
     *
     * @var array
     */
    private $transactions;

    /**
     * Link to the SQL statements for the connected database.
     *
     * @var DataBaseSQL;
     */
    private $utilsSQL;

    /**
     * Contructor and class initialization.
     */
    public function __construct()
    {
        $this->i18n = new Translator();
        $this->lastErrorMsg = '';
        $this->transactions = [];
        $this->utilsSQL = new MysqlSQL();
    }

    /**
     * Destructor class.
     */
    public function __destruct()
    {
        $this->rollbackTransactions();
    }

    /**
     * Starts an SQL transaction.
     *
     * @param \mysqli $link
     *
     * @return bool
     */
    public function beginTransaction($link)
    {
        $result = $this->exec($link, 'START TRANSACTION;');
        if ($result) {
            $this->transactions[] = $link;
        }

        return $result;
    }

    /**
     * With the default field in a table it checks whether it refers to a
     * sequence and if a sequence exists. If it can't find it, i will create one.
     *
     * @param \mysqli $link
     * @param string  $tableName
     * @param string  $default
     * @param string  $colname
     *
     * @return bool
     */
    public function checkSequence($link, $tableName, $default, $colname)
    {
        return true;
    }

    /**
     * Runs extra checks in the table.
     *
     * @param \mysqli $link
     * @param string  $tableName
     * @param string  $error
     *
     * @return bool
     */
    public function checkTableAux($link, $tableName, &$error)
    {
        $result = true;

        /// table uses InnoDB?
        $data = $this->select($link, 'SHOW TABLE STATUS FROM `' . FS_DB_NAME . "` LIKE '" . $tableName . "';");
        if (!empty($data) && $data[0]['Engine'] !== 'InnoDB') {
            $result = $this->exec($link, 'ALTER TABLE ' . $tableName . ' ENGINE=InnoDB;');
            if ($result) {
                $error = $this->i18n->trans('cant-convert-to-innodb', ['%tableName%' => $tableName]);
            }
        }

        return $result;
    }

    /**
     * Disconnect from the database.
     *
     * @param \mysqli $link
     *
     * @return bool
     */
    public function close($link)
    {
        $this->rollbackTransactions();
        return $link->close();
    }

    /**
     * Converts the sqlColumns return data to a working structure.
     *
     * @param array $colData
     *
     * @return array
     */
    public function columnFromData($colData)
    {
        $result = array_change_key_case($colData);
        $result['is_nullable'] = $result['null'];
        $result['name'] = $result['field'];
        unset($result['null'], $result['field']);
        return $result;
    }

    /**
     * Commits changes in a SQL transaction.
     *
     * @param \mysqli $link
     *
     * @return bool
     */
    public function commit($link)
    {
        $result = $this->exec($link, 'COMMIT;');
        if ($result && in_array($link, $this->transactions, false)) {
            $this->unsetTransaction($link);
        }

        return $result;
    }

    /**
     * Compares the data types from a column. Returns true if they are equal.
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    public function compareDataTypes($dbType, $xmlType)
    {
        $result = (
            ($dbType === $xmlType) ||
            ($dbType === 'tinyint(1)' && $xmlType === 'boolean') ||
            (substr($dbType, 8, -1) === substr($xmlType, 18, -1)) ||
            (substr($dbType, 5, -1) === substr($xmlType, 18, -1))
            );

        if (!$result) {
            $result = $this->compareDataTypeNumeric($dbType, $xmlType);
        }

        if (!$result) {
            $result = $this->compareDataTypeChar($dbType, $xmlType);
        }

        return $result;
    }

    /**
     * Connects to the database.
     *
     * @param string $error
     *
     * @return null|mysqli
     */
    public function connect(&$error)
    {
        if (!class_exists('mysqli')) {
            $error = $this->i18n->trans('php-mysql-not-found');
            return null;
        }

        $result = new \mysqli(FS_DB_HOST, FS_DB_USER, FS_DB_PASS, FS_DB_NAME, (int) FS_DB_PORT);
        if ($result->connect_errno) {
            $error = $result->connect_error;
            $this->lastErrorMsg = $error;
            return null;
        }

        $result->set_charset('utf8');
        $result->autocommit(false);

        /// disable foreign keys
        if (!FS_DB_FOREIGN_KEYS) {
            $this->exec($result, 'SET foreign_key_checks = 0;');
        }

        return $result;
    }

    /**
     * Returns the date format from the database engine.
     *
     * @return string
     */
    public function dateStyle()
    {
        return 'Y-m-d';
    }

    /**
     * Returns the last run statement error.
     *
     * @param \mysqli $link
     *
     * @return string
     */
    public function errorMessage($link)
    {
        return ($link->error !== '') ? $link->error : $this->lastErrorMsg;
    }

    /**
     * Escapes quotes from a text string.
     *
     * @param \mysqli $link
     * @param string  $str
     *
     * @return string
     */
    public function escapeString($link, $str)
    {
        return $link->escape_string($str);
    }

    /**
     * Runs SQL statement in the database (inserts, updates or deletes).
     *
     * @param \mysqli $link
     * @param string  $sql
     *
     * @return bool
     */
    public function exec($link, $sql)
    {
        try {
            if ($link->multi_query($sql)) {
                do {
                    $more = ($link->more_results() && $link->next_result());
                } while ($more);
            }
            $result = ($link->errno === 0);
        } catch (Exception $e) {
            $this->lastErrorMsg = $e->getMessage();
            $result = false;
        }

        return $result;
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
     * Returns the link to the SQL class from the engine.
     *
     * @return DataBaseSQL
     */
    public function getSQL()
    {
        return $this->utilsSQL;
    }

    /**
     * Indicates if the connection has an active transaction.
     *
     * @param \mysqli $link
     *
     * @return bool
     */
    public function inTransaction($link)
    {
        return in_array($link, $this->transactions, false);
    }

    /**
     * Returns an array with the database table names.
     *
     * @param \mysqli $link
     *
     * @return array
     */
    public function listTables($link)
    {
        $aux = $this->select($link, 'SHOW TABLES;');
        if (empty($aux)) {
            return [];
        }

        $tables = [];
        foreach ($aux as $a) {
            $key = 'Tables_in_' . FS_DB_NAME;
            if (isset($a[$key])) {
                $tables[] = $a[$key];
            }
        }

        return $tables;
    }

    /**
     * Rolls back a transaction.
     *
     * @param \mysqli $link
     *
     * @return bool
     */
    public function rollback($link)
    {
        $result = $this->exec($link, 'ROLLBACK;');
        if (in_array($link, $this->transactions, false)) {
            $this->unsetTransaction($link);
        }

        return $result;
    }

    /**
     * Runs a SELECT SQL statement, and returns an array with the results,
     * or an empty array when it fails.
     *
     * @param \mysqli $link
     * @param string  $sql
     *
     * @return array
     */
    public function select($link, $sql)
    {
        $result = [];
        try {
            $aux = $link->query($sql);
            if ($aux) {
                $result = [];
                while ($row = $aux->fetch_array(MYSQLI_ASSOC)) {
                    $result[] = $row;
                }
                $aux->free();
            }
        } catch (Exception $e) {
            $this->lastErrorMsg = $e->getMessage();
            $result = [];
        }

        return $result;
    }

    /**
     * Returns the database engine and its version.
     *
     * @param \mysqli $link
     *
     * @return string
     */
    public function version($link)
    {
        return 'MYSQL ' . $link->server_version;
    }

    /**
     * Compares the data types from an alphanumeric column.
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    private function compareDataTypeChar($dbType, $xmlType)
    {
        $result = 0 === strpos($xmlType, 'character varying(');
        if ($result) {
            $result = (0 === strpos($dbType, 'varchar(')) || (0 === strpos($dbType, 'char('));
        }

        return $result;
    }

    /**
     * Compares the data types from a numeric column.
     *
     * @param string $dbType
     * @param string $xmlType
     *
     * @return bool
     */
    private function compareDataTypeNumeric($dbType, $xmlType)
    {
        return (0 === strpos($dbType, 'int(') && $xmlType === 'INTEGER') ||
            (0 === strpos($dbType, 'double') && $xmlType === 'double precision');
    }

    /**
     * Rollback all active transactions.
     */
    private function rollbackTransactions()
    {
        foreach ($this->transactions as $link) {
            $this->rollback($link);
        }
    }

    /**
     * Delete from the list the specified transaction.
     *
     * @param \mysqli $link
     */
    private function unsetTransaction($link)
    {
        $count = 0;
        foreach ($this->transactions as $trans) {
            if ($trans === $link) {
                array_splice($this->transactions, $count, 1);
                break;
            }
            ++$count;
        }
    }
}
