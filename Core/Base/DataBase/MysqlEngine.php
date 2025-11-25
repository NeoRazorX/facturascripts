<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\KernelException;
use mysqli;

/**
 * Class to connect with MySQL.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class MysqlEngine extends DataBaseEngine
{
    /**
     * Open transaction list.
     *
     * @var array
     */
    private $transactions = [];

    /**
     * Link to the SQL statements for the connected database.
     *
     * @var DataBaseQueries
     */
    private $utilsSQL;

    /**
     * Constructor and class initialization.
     */
    public function __construct()
    {
        parent::__construct();
        $this->utilsSQL = new MysqlQueries();
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
     * @param mysqli $link
     *
     * @return bool
     */
    public function beginTransaction($link): bool
    {
        $result = $this->exec($link, 'START TRANSACTION;');
        if ($result) {
            $this->transactions[] = $link;
        }

        return $result;
    }

    public function castInteger($link, $column): string
    {
        return 'CAST(' . $this->escapeColumn($link, $column) . ' AS unsigned)';
    }

    /**
     * Disconnect from the database.
     *
     * @param mysqli $link
     *
     * @return bool
     */
    public function close($link): bool
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
    public function columnFromData($colData): array
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
     * @param mysqli $link
     *
     * @return bool
     */
    public function commit($link): bool
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
    public function compareDataTypes($dbType, $xmlType): bool
    {
        if (parent::compareDataTypes($dbType, $xmlType)) {
            return true;
        }

        // bool
        if ($dbType == 'tinyint(1)' && str_starts_with($xmlType, 'bool')) {
            return true;
        }

        // int
        if (str_starts_with($dbType, 'int') && str_starts_with($xmlType, 'int')) {
            return true;
        }

        // double
        if (str_starts_with($dbType, 'double') && $xmlType == 'double precision') {
            return true;
        }

        // varchar
        if (str_starts_with($dbType, 'varchar(') && str_starts_with($xmlType, 'character varying(')) {
            // check length
            return substr($dbType, 8, -1) == substr($xmlType, 18, -1);
        }

        // char
        if (str_starts_with($dbType, 'char(') && str_starts_with($xmlType, 'character varying(')) {
            // check length
            return substr($dbType, 5, -1) == substr($xmlType, 18, -1);
        }

        return false;
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
        if (false === class_exists('mysqli')) {
            $error = $this->i18n->trans('php-mysql-not-found');
            throw new KernelException('DatabaseError', $error);
        }

        $result = new mysqli(FS_DB_HOST, FS_DB_USER, FS_DB_PASS, FS_DB_NAME, (int)FS_DB_PORT);
        if ($result->connect_errno) {
            $error = $result->connect_error;
            $this->lastErrorMsg = $error;
            throw new KernelException('DatabaseError', $error);
        }

        $charset = defined('FS_MYSQL_CHARSET') ? FS_MYSQL_CHARSET : 'utf8';
        $result->set_charset($charset);
        $result->autocommit(false);

        // disable foreign keys
        if (defined('FS_DB_FOREIGN_KEYS') && false === FS_DB_FOREIGN_KEYS) {
            $this->exec($result, 'SET foreign_key_checks = 0;');
        }

        return $result;
    }

    /**
     * Returns the last run statement error.
     *
     * @param mysqli $link
     *
     * @return string
     */
    public function errorMessage($link): string
    {
        return empty($link->error) ? $this->lastErrorMsg : $link->error;
    }

    /**
     * Escapes the column name.
     *
     * @param mysqli $link
     * @param string $name
     *
     * @return string
     */
    public function escapeColumn($link, $name): string
    {
        // Si contiene un punto, escapar cada parte por separado (tabla.columna)
        if (strpos($name, '.') !== false) {
            $parts = explode('.', $name);
            return '`' . implode('`.`', $parts) . '`';
        }

        return '`' . $name . '`';
    }

    /**
     * Escapes quotes from a text string.
     *
     * @param mysqli $link
     * @param string $str
     *
     * @return string
     */
    public function escapeString($link, $str): string
    {
        return $link->escape_string($str);
    }

    /**
     * Runs SQL statement in the database (inserts, updates or deletes).
     *
     * @param mysqli $link
     * @param string $sql
     *
     * @return bool
     */
    public function exec($link, $sql): bool
    {
        try {
            if ($link->multi_query($sql)) {
                do {
                    $more = $link->more_results() && $link->next_result();
                } while ($more);
            }
            if ($link->errno !== 0) {
                $this->lastErrorMsg = $link->error;
                return false;
            }
            return true;
        } catch (Exception $err) {
            $this->lastErrorMsg = $err->getMessage();
        }

        return false;
    }

    /**
     * Returns the link to the SQL class from the engine.
     *
     * @return DataBaseQueries
     */
    public function getSQL()
    {
        return $this->utilsSQL;
    }

    /**
     * Indicates if the connection has an active transaction.
     *
     * @param mysqli $link
     *
     * @return bool
     */
    public function inTransaction($link): bool
    {
        return in_array($link, $this->transactions, false);
    }

    /**
     * Returns an array with the database table names.
     *
     * @param mysqli $link
     *
     * @return array
     */
    public function listTables($link): array
    {
        $tables = [];
        foreach ($this->select($link, 'SHOW TABLES;') as $row) {
            $key = 'Tables_in_' . FS_DB_NAME;
            if (isset($row[$key])) {
                $tables[] = $row[$key];
            }
        }

        return $tables;
    }

    /**
     * Rolls back a transaction.
     *
     * @param mysqli $link
     *
     * @return bool
     */
    public function rollback($link): bool
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
     * @param mysqli $link
     * @param string $sql
     *
     * @return array
     */
    public function select($link, $sql): array
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
            if ($link->errno !== 0) {
                $this->lastErrorMsg = $link->error;
            }
        } catch (Exception $err) {
            $this->lastErrorMsg = $err->getMessage();
            $result = [];
        }

        return $result;
    }

    /**
     * Returns the database engine and its version.
     *
     * @param mysqli $link
     *
     * @return string
     */
    public function version($link): string
    {
        return $link->server_info;
    }

    /**
     * Rollback all active transactions.
     */
    private function rollbackTransactions(): void
    {
        foreach ($this->transactions as $link) {
            $this->rollback($link);
        }
    }

    /**
     * Delete from the list the specified transaction.
     *
     * @param mysqli $link
     */
    private function unsetTransaction($link): void
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
