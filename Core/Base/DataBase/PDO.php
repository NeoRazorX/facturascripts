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

use FacturaScripts\Core\Base\DataBase\PDO\PDOSqlite;

/**
 * Clase para utilizar PDO como conector.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
class PDO implements DatabaseEngine
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
     * Engine de PDO
     * @var PDO\PDOMysql|PDO\PDOPostgresql|PDO\PDOSqlite|null
     */
    private $engine;
    /**
     * Contructor e inicializador de la clase
     */
    public function __construct($type)
    {
        switch ($type) {
            case 'pdo_mysql':
                $this->engine = new PDO\PDOMysql();
                break;
            case 'pdo_pgsql':
                $this->engine = new PDO\PDOPostgresql();
                break;
            case 'pdo_sqlite':
                $this->engine = new PDO\PDOSqlite();
                break;
            default:
                $this->engine = null;
                break;
        }
        $this->utils = new DataBaseUtils($this);
        $this->utilsSQL = $this->engine->getSQL();
        $this->transactions = [];
        $this->lastErrorMsg = '';
    }


    /**
     * Devuelve el enlace a la clase de Utilidades del engine
     * @return DataBaseUtils
     */
    public function getUtils()
    {
        // TODO: Implement getUtils() method.
    }

    /**
     * Devuelve el enlace a la clase de SQL del engine
     * @return DatabaseSQL
     */
    public function getSQL()
    {
        // TODO: Implement getSQL() method.
    }

    /**
     * Convierte los datos leidos del sqlColumns a estructura de trabajo
     *
     * @param array $colData
     */
    public function columnFromData($colData)
    {
        // TODO: Implement columnFromData() method.
    }

    /**
     * Información sobre el motor de base de datos
     *
     * @param PDO $link
     *
     * @return string
     */
    public function version($link)
    {
        // TODO: Implement version() method.
    }

    /**
     * Conecta con la base de datos
     *
     * @param string $error
     */
    public function connect(&$error)
    {
        // TODO: Implement connect() method.
    }

    /**
     * Se intenta realizar la conexión a la base de datos PostgreSQL,
     * si se ha realizado se devuelve true, sino false.
     * En el caso que sea false, $errors contiene el error
     *
     * @param $errors
     * @param $dbData
     *
     * @return bool
     */
    public static function testConnect(&$errors, $dbData)
    {
        // TODO: Implement testConnect() method.
    }

    /**
     * Cierra la conexión con la base de datos
     *
     * @param PDO $link
     */
    public function close($link)
    {
        // TODO: Implement close() method.
    }

    /**
     * Último mensaje de error generado un operación con la BD
     *
     * @param PDO $link
     */
    public function errorMessage($link)
    {
        // TODO: Implement errorMessage() method.
    }

    /**
     * Inicia una transacción sobre la conexión
     *
     * @param PDO $link
     */
    public function beginTransaction($link)
    {
        // TODO: Implement beginTransaction() method.
    }

    /**
     * Confirma las operaciones realizadas sobre la conexión
     * desde el beginTransaction
     *
     * @param PDO $link
     */
    public function commit($link)
    {
        // TODO: Implement commit() method.
    }

    /**
     * Deshace las operaciones realizadas sobre la conexión
     * desde el beginTransaction
     *
     * @param PDO $link
     */
    public function rollback($link)
    {
        // TODO: Implement rollback() method.
    }

    /**
     * Indica si la conexión tiene una transacción abierta
     *
     * @param PDO $link
     */
    public function inTransaction($link)
    {
        // TODO: Implement inTransaction() method.
    }

    /**
     * Ejecuta una sentencia de datos sobre la conexión
     *
     * @param PDO $link
     * @param string $sql
     *
     * @return array
     */
    public function select($link, $sql)
    {
        // TODO: Implement select() method.
    }

    /**
     * Ejecuta una sentencia DDL sobre la conexión.
     * Si no hay transacción abierta crea una y la finaliza
     *
     * @param PDO $link
     * @param string $sql
     */
    public function exec($link, $sql)
    {
        // TODO: Implement exec() method.
    }

    /**
     * Compara las columnas indicadas en los arrays
     *
     * @param string $dbType
     * @param string $xmlType
     */
    public function compareDataTypes($dbType, $xmlType)
    {
        // TODO: Implement compareDataTypes() method.
    }

    /**
     * Lista de tablas existentes en la conexión
     *
     * @param PDO $link
     */
    public function listTables($link)
    {
        // TODO: Implement listTables() method.
    }

    /**
     * Escapa la cadena indicada
     *
     * @param PDO $link
     * @param string $str
     */
    public function escapeString($link, $str)
    {
        // TODO: Implement escapeString() method.
    }

    /**
     * Indica el formato de fecha que utiliza la BD
     */
    public function dateStyle()
    {
        // TODO: Implement dateStyle() method.
    }

    /**
     * Comprueba la existencia de una secuencia
     *
     * @param PDO $link
     * @param string $tableName
     * @param string $default
     * @param string $colname
     */
    public function checkSequence($link, $tableName, $default, $colname)
    {
        // TODO: Implement checkSequence() method.
    }

    /**
     * Comprobación adicional a la existencia de una tabla
     *
     * @param PDO $link
     * @param string $tableName
     * @param string $error
     */
    public function checkTableAux($link, $tableName, &$error)
    {
        // TODO: Implement checkTableAux() method.
    }

    /**
     * Devuelve el tipo de conexión que utiliza
     * @return string
     */
    public function getType()
    {
        return 'pdo';
    }

    /**
     * Devuelve el engine de PDO utilizado
     * @return PDO\PDOMysql|PDO\PDOPostgresql|PDOSqlite|null
     */
    public function getEngine()
    {
        return $this->engine;
    }
}
