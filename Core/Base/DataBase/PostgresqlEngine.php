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

/**
 * Class to connect with PostgreSQL.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class PostgresqlEngine extends DataBaseEngine
{

    /**
     * Link to the SQL statements for the connected database
     *
     * @var DataBaseQueries
     */
    private $utilsSQL;

    /**
     * Postgresql constructor and initialization.
     */
    public function __construct()
    {
        parent::__construct();
        $this->utilsSQL = new PostgresqlQueries();
    }

    /**
     * Starts a SQL transaction
     *
     * @param resource $link
     *
     * @return bool
     */
    public function beginTransaction($link)
    {
        return $this->exec($link, 'BEGIN TRANSACTION;');
    }

    /**
     * Disconnect from the database
     *
     * @param resource $link
     *
     * @return bool
     */
    public function close($link)
    {
        return pg_close($link);
    }

    /**
     * Converts the sqlColumns return data to a working structure
     *
     * @param array $colData
     *
     * @return array
     */
    public function columnFromData($colData)
    {
        $colData['extra'] = null;

        if ($colData['character_maximum_length'] !== null) {
            $colData['type'] .= '(' . $colData['character_maximum_length'] . ')';
        }

        return $colData;
    }

    /**
     * Commits changes in a SQL transaction
     *
     * @param resource $link
     *
     * @return bool
     */
    public function commit($link)
    {
        return $this->exec($link, 'COMMIT;');
    }

    /**
     * Connects to the database
     *
     * @param string $error
     *
     * @return bool|null
     */
    public function connect(&$error)
    {
        if (!function_exists('pg_connect')) {
            $error = $this->i18n->trans('php-postgresql-not-found');
            return null;
        }

        $string = 'host=' . \FS_DB_HOST . ' dbname=' . \FS_DB_NAME . ' port=' . \FS_DB_PORT
            . ' user=' . \FS_DB_USER . ' password=' . \FS_DB_PASS;
        $result = pg_connect($string);
        if (!$result) {
            $error = pg_last_error();
            return null;
        }

        /// set datestyle
        $this->exec($result, 'SET DATESTYLE TO ISO, YMD;');
        return $result;
    }

    /**
     * Returns the last run statement error
     *
     * @param resource $link
     *
     * @return string
     */
    public function errorMessage($link)
    {
        $error = pg_last_error($link);
        return empty($error) ? $this->lastErrorMsg : $error;
    }

    /**
     * Escapes the column name.
     * 
     * @param resource $link
     * @param string   $name
     *
     * @return string
     */
    public function escapeColumn($link, $name)
    {
        return '"' . $name . '"';
    }

    /**
     * Escapes quotes from a text string
     *
     * @param resource $link
     * @param string   $str
     *
     * @return string
     */
    public function escapeString($link, $str)
    {
        return pg_escape_string($link, $str);
    }

    /**
     * Runs SQL statement in the database
     * (inserts, updates or deletes)
     *
     * @param resource $link
     * @param string   $sql
     *
     * @return bool
     */
    public function exec($link, $sql)
    {
        return $this->runSql($link, $sql, false) === true;
    }

    /**
     * Indicates the operator for the database engine
     *
     * @param string $operator
     */
    public function getOperator($operator)
    {
        switch ($operator) {
            case 'REGEXP':
                return '~';

            default:
                return $operator;
        }
    }

    /**
     * Returns the link to the SQL class from the engine
     *
     * @return DataBaseQueries
     */
    public function getSQL()
    {
        return $this->utilsSQL;
    }

    /**
     * Indicates if the connection has an active transaction
     *
     * @param resource $link
     *
     * @return bool
     */
    public function inTransaction($link)
    {
        $status = pg_transaction_status($link);
        switch ($status) {
            case PGSQL_TRANSACTION_ACTIVE:
            case PGSQL_TRANSACTION_INTRANS:
            case PGSQL_TRANSACTION_INERROR:
                return true;

            default:
                return false;
        }
    }

    /**
     * Returns an array with the database table names
     *
     * @param resource $link
     *
     * @return array
     */
    public function listTables($link)
    {
        $tables = [];
        $sql = 'SELECT tablename FROM pg_catalog.pg_tables'
            . " WHERE schemaname NOT IN ('pg_catalog','information_schema')"
            . ' ORDER BY tablename ASC;';

        foreach ($this->select($link, $sql) as $row) {
            $tables[] = $row['tablename'];
        }

        return $tables;
    }

    /**
     * Rolls back a transaction
     *
     * @param resource $link
     *
     * @return bool
     */
    public function rollback($link)
    {
        return $this->exec($link, 'ROLLBACK;');
    }

    /**
     * Runs a SELECT SQL statement
     *
     * @param resource $link
     * @param string   $sql
     *
     * @return array
     */
    public function select($link, $sql)
    {
        $results = $this->runSql($link, $sql);
        return is_array($results) ? $results : [];
    }

    /**
     * 
     * @param resource $link
     * @param string   $tableName
     * @param array    $fields
     */
    public function updateSequence($link, $tableName, $fields)
    {
        foreach ($fields as $colName => $field) {
            /// serial type
            if (stripos($field['default'], 'nextval(') !== false) {
                $sql = "SELECT setval('" . $tableName . "_" . $colName . "_seq', (SELECT MAX(" . $colName . ") from " . $tableName . "));";
                $this->exec($link, $sql);
            }
        }
    }

    /**
     * Return the used engine and the version.
     *
     * @param resource $link
     *
     * @return string
     */
    public function version($link)
    {
        return 'POSTGRESQL ' . pg_version($link)['server'];
    }

    /**
     * Runs a SELECT SQL statement, and returns an array with the results when $selectRows= true,
     * or an empty array if it fails.
     *
     * @param resource $link
     * @param string   $sql
     * @param bool     $selectRows
     *
     * @return array|bool
     */
    private function runSql($link, $sql, $selectRows = true)
    {
        $result = $selectRows ? [] : false;

        try {
            $aux = @pg_query($link, $sql);
            if ($aux) {
                $result = $selectRows ? pg_fetch_all($aux) : true;
                pg_free_result($aux);
            }
        } catch (Exception $err) {
            $this->lastErrorMsg = $err->getMessage();
            $result = $selectRows ? [] : false;
        }

        return $result;
    }
}
