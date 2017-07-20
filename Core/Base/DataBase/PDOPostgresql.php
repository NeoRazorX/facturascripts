<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

use PDO;
use PDOException;
use PDOStatement;

/**
 * Clase para conectar a PostgreSQL utilizando pdo_pgsql.
 * Puede considerarse en estado beta, falta probarlo a fondo.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class PDOPostgresql implements DatabaseEngine
{
    /**
     * El enlace con las utilidades comunes entre motores de base de datos.
     * @var DataBaseUtils
     */
    private $utils;

    /**
     * Enlace al conjunto de sentencias SQL de la base de datos conectada
     * @var DatabaseSQL;
     */
    private $utilsSQL;

    /**
     * Database Handler
     * @var PDO
     */
    private $dbh;

    /**
     * Errores devueltos
     * @var string
     */
    private $error;

    /**
     * Contiene la declaración
     * @var PDOStatement|PDOException
     */
    private $stmt;

    /**
     * Relacion de Transacciones abiertas.
     * @var array
     */
    private $transactions;

    /**
     * Ultimo mensaje de error
     * @var string
     */
    private $lastErrorMsg;

    /**
     * Contructor e inicializador de la clase
     */
    public function __construct()
    {
        $this->utils = new DataBaseUtils($this);
        $this->utilsSQL = new PDOPostgresqlSQL();
        $this->transactions = [];
        $this->lastErrorMsg = '';
    }

    /**
     * Destructor de la clase
     */
    public function __destruct()
    {
        $this->rollbackTransactions();
    }

    /**
     * Devuelve el motor de base de datos y la versión.
     * @param PDO $link
     * @return string
     */
    public function version($link)
    {
        return 'POSTGRESQL ' . $this->dbh->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Convierte los datos leidos del sqlColumns a estructura de trabajo
     * @param array $colData
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
     * Conecta a la base de datos.
     * @param string $error
     * @return null|PDO
     */
    public function connect(&$error)
    {
        if (!extension_loaded('pdo')) {
            $this->error = 'No tienes instalada la extensión de PHP para PDO.';
            $error = $this->error;
            return null;
        }
        if (!extension_loaded('pdo_pgsql')) {
            $this->error = 'No tienes instalada la extensión de PHP para PDO PostgreSQL.';
            $error = $this->error;
            return null;
        }

        $dsn = 'pgsql:host=' . FS_DB_HOST . ';port=' . FS_DB_PORT . ';dbname=' . FS_DB_NAME;
        $options = [
            PDO::ATTR_EMULATE_PREPARES => 1,
            //            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        // Creamos una nueva instancia PDO
        try {
            $this->dbh = new PDO($dsn, FS_DB_USER, FS_DB_PASS, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            $error = $this->error;
            $this->lastErrorMsg = $error;
            return null;
        }

        // Force PostgreSQL to use the UTF-8 character set by default.
        $this->dbh->exec("SET NAMES 'UTF8'");

        /// Desactivamos las claves ajenas
        if (FS_FOREIGN_KEYS !== '1') {
            $this->dbh->exec('SET foreign_key_checks = 0;');
        }

        return $this->dbh;
    }

    /**
     * Se intenta realizar la conexión a la base de datos PostgreSQL,
     * si se ha realizado se devuelve true, sino false.
     * En el caso que sea false, $errors contiene el error
     * @param $errors
     * @param $dbData
     * @return bool
     */
    public static function testConnect(&$errors, $dbData)
    {
        $dsnHost = 'pgsql:host=' . $dbData['host'] . ';port=' . $dbData['port'];
        $dsnDb = $dsnHost . ';dbname=' . $dbData['name'];
        $options = [
            PDO::ATTR_EMULATE_PREPARES => 1,
            //            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        // Creamos una nueva instancia PDO
        $connection = null;
        try {
            $connection = new PDO($dsnHost, $dbData['user'], $dbData['pass'], $options);
        } catch (PDOException $e) {
            $errors[] = $e->getMessage();
        }

        if ($connection !== null && $connection->errorCode() === '00000') {
            // Comprobamos que la BD exista, de lo contrario la creamos
            $connection2 = null;
            try {
                $connection2 = new PDO($dsnDb, $dbData['user'], $dbData['pass'], $options);
            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
            }

            if ($connection2 !== null && $connection2->errorCode() === '00000') {
                $errors = [];
                return true;
            }

            $sqlCrearBD = 'CREATE DATABASE ' . $dbData['name'] . ';';
            if (is_int($connection->exec($sqlCrearBD))) {
                $errors = [];
                return true;
            }

            $array = $connection->errorInfo();
            $error = '';
            $separator = '';
            foreach ($array as $err) {
                if ($err !== '00000') {
                    $error .= $separator . $err;
                    $separator = ' ';
                }
            }
            if ($error !== '') {
                $errors[] = $error;
            }
        }

        return false;
    }

    /**
     * Desconecta de la base de datos.
     * @param PDO $link
     * @return bool
     */
    public function close($link)
    {
        if ($this->dbh) {
            $this->dbh->rollBack();
            $this->dbh = null;
            return $this->stmt->closeCursor();
        }
        return false;
    }

    /**
     * Devuelve el error de la ultima sentencia ejecutada
     * @param PDO $link
     * @return string
     */
    public function errorMessage($link)
    {
        return ($this->error !== '') ? $this->error : $this->lastErrorMsg;
    }

    /**
     * Inicia una transacción SQL.
     * @param PDO $link
     * @return bool
     */
    public function beginTransaction($link)
    {
        $result = $this->dbh->beginTransaction();
        if ($result) {
            $this->transactions[] = $link;
        }
        return $result;
    }

    /**
     * Guarda los cambios de una transacción SQL.
     * @param PDO $link
     * @return bool
     */
    public function commit($link)
    {
        $result = $this->dbh->commit();
        if ($result && in_array($link, $this->transactions, false)) {
            $this->unsetTransaction($link);
        }
        return $result;
    }

    /**
     * Deshace las operaciones realizadas sobre la conexión
     * desde el beginTransaction
     * @param PDO $link
     * @return bool
     */
    public function rollback($link)
    {
        $result = $this->dbh->rollBack();
        if (in_array($link, $this->transactions, false)) {
            $this->unsetTransaction($link);
        }
        return $result;
    }

    /**
     * Indica si la conexión está en transacción
     * @param PDO $link
     * @return bool
     */
    public function inTransaction($link)
    {
        return in_array($link, $this->transactions, false);
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, y devuelve un array con los resultados,
     * o array vacío en caso de fallo.
     * @param PDO $link
     * @param string $sql
     * @return array
     */
    public function select($link, $sql)
    {
        $result = [];
        $this->query($sql);
        $aux = $this->resultSet();
        if (!empty($aux)) {
            foreach ($aux as $row) {
                $result[] = $row;
            }
        }
        unset($aux);
        return $result;
    }

    /**
     * Ejecuta sentencias SQL sobre la base de datos
     * (inserts, updates o deletes).
     * @param PDO $link
     * @param string $sql
     * @return bool
     */
    public function exec($link, $sql)
    {
        $this->stmt = $this->dbh->prepare($sql);
        return $this->stmt->execute();
    }

    /**
     * Escapa las comillas de la cadena de texto.
     * @param PDO $link
     * @param string $str
     * @return string
     */
    public function escapeString($link, $str)
    {
        //return $this->dbh->quote($str);
        return $str;
    }

    /**
     * Devuelve el estilo de fecha del motor de base de datos.
     * @return string
     */
    public function dateStyle()
    {
        return 'd-m-Y';
    }

    /**
     * Compara los tipos de datos de una columna numerica. Devuelve TRUE si son iguales.
     * @param string $dbType
     * @param string $xmlType
     * @return bool
     */
    public function compareDataTypes($dbType, $xmlType)
    {
        return ($dbType === $xmlType);
    }

    /**
     * Devuelve un array con los nombres de las tablas de la base de datos.
     * @param PDO $link
     * @return array
     */
    public function listTables($link)
    {
        $tables = [];
        $sql = 'SELECT tablename'
            . ' FROM pg_catalog.pg_tables'
            . " WHERE schemaname NOT IN ('pg_catalog','information_schema')"
            . ' ORDER BY tablename ASC;';

        $aux = $this->select($link, $sql);
        if (!empty($aux)) {
            foreach ($aux as $a) {
                $tables[] = $a['tablename'];
            }
        }
        return $tables;
    }

    /**
     * A partir del campo default de una tabla
     * comprueba si se refiere a una secuencia, y si es así
     * comprueba la existencia de la secuencia. Si no la encuentra
     * la crea.
     * @param PDO $link
     * @param string $tableName
     * @param string $default
     * @param string $colname
     * @return bool
     */
    public function checkSequence($link, $tableName, $default, $colname)
    {
        $aux = explode("'", $default);
        if (count($aux) === 3) {
            $data = $this->dbh->query($this->utilsSQL->sqlSequenceExists($aux[1]));
            if ($data) {             /// ¿Existe esa secuencia?
                $data = $this->dbh->query('SELECT MAX(' . $colname . ')+1 as num FROM ' . $tableName . ';');
                $this->dbh->exec('CREATE SEQUENCE ' . $aux[1] . ' START ' . $data[0]['num'] . ';');
            }
        }
        return true;
    }

    /**
     * Realiza comprobaciones extra a la tabla.
     * @param PDO $link
     * @param string $tableName
     * @param string $error
     * @return bool
     */
    public function checkTableAux($link, $tableName, &$error)
    {
        return true;
    }

    /**
     * Devuelve el enlace a la clase de Utilidades del engine
     * @return DataBaseUtils
     */
    public function getUtils()
    {
        return $this->utils;
    }

    /**
     * Devuelve el enlace a la clase de SQL del engine
     * @return DatabaseSQL
     */
    public function getSQL()
    {
        return $this->utilsSQL;
    }

    /**
     * Devuelve el tipo de conexión que utiliza
     * @return string
     */
    public function getType()
    {
        return 'pdo_pgsql';
    }

    /**
     * Prepares a statement for execution and returns a statement object
     * @param string $queryString <p>This must be a valid SQL statement for the target database server.</p>
     */
    public function query($queryString)
    {
        $this->stmt = $this->dbh->prepare($queryString);
    }

    /**
     * Binds a value to a parameter
     * @param mixed $param <p>Parameter identifier. For a prepared statement using named
     * placeholders, this will be a parameter name of the form
     * :name. For a prepared statement using
     * question mark placeholders, this will be the 1-indexed position of
     * the parameter.</p>
     * @param mixed $value <p>The value to bind to the parameter.</p>
     * @param int $type [optional] <p>Explicit data type for the parameter using the PDO::PARAM_*
     * constants.
     */
    public function bind($param, $value, $type = PDO::PARAM_STR)
    {
        if ($type === null) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case ($value === null):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    /**
     * Executes a prepared statement
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function execute()
    {
        return $this->stmt->execute();
    }

    /**
     * Returns an array containing all of the result set rows
     * @return array <b>PDOStatement::fetchAll</b> returns an array containing
     * all of the remaining rows in the result set. The array represents each
     * row as either an array of column values or an object with properties
     * corresponding to each column name.
     */
    public function resultSet()
    {
        $this->execute();
        return $this->stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetches the next row from a result set
     * @return mixed The return value of this function on success depends on the fetch type. In
     * all cases, <b>FALSE</b> is returned on failure.
     */
    public function single()
    {
        $this->execute();
        return $this->stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Returns the number of rows affected by the last SQL statement
     * @return int the number of rows.
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Dump an SQL prepared command
     * @return bool No value is returned.
     */
    public function debugDumpParams()
    {
        return $this->stmt->debugDumpParams();
    }

    /**
     * Deshace todas las transacciones activas
     */
    private function rollbackTransactions()
    {
        foreach ($this->transactions as $link) {
            $this->rollback($link);
        }
    }

    /**
     * Borra de la lista la transaccion indicada
     * @param PDO $link
     */
    private function unsetTransaction($link)
    {
        $count = 0;
        foreach ($this->transactions as $trans) {
            if ($trans === $link) {
                array_splice($this->transactions, $count, 1);
                break;
            }
            $count++;
        }
    }
}
