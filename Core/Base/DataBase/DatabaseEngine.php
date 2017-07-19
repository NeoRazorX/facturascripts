<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

use mysqli;
use PDO;

/**
 * Interface para cada uno de los motores de base de datos compatibles
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
interface DatabaseEngine
{
    /**
     * Devuelve el enlace a la clase de Utilidades del engine
     * @return DataBaseUtils
     */
    public function getUtils();    

    /**
     * Devuelve el enlace a la clase de SQL del engine
     * @return DatabaseSQL
     */
    public function getSQL();    
    
    /**
     * Convierte los datos leidos del sqlColumns a estructura de trabajo
     * @param array $colData
     */
    public function columnFromData($colData);

    /**
     * Información sobre el motor de base de datos
     * @param mysqli|resource|PDO $link
     * @return string
     */
    public function version($link);

    /**
     * Conecta con la base de datos
     * @param string $error
     */
    public function connect(&$error);

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
    public static function testConnect(&$errors, $dbData);

    /**
     * Cierra la conexión con la base de datos
     * @param mysqli|resource|PDO $link
     */
    public function close($link);

    /**
     * Último mensaje de error generado un operación con la BD
     * @param mysqli|resource|PDO $link
     */
    public function errorMessage($link);

    /**
     * Inicia una transacción sobre la conexión
     * @param mysqli|resource|PDO $link
     */
    public function beginTransaction($link);

    /**
     * Confirma las operaciones realizadas sobre la conexión
     * desde el beginTransaction
     * @param mysqli|resource|PDO $link
     */
    public function commit($link);

    /**
     * Deshace las operaciones realizadas sobre la conexión
     * desde el beginTransaction
     * @param mysqli|resource|PDO $link
     */
    public function rollback($link);

    /**
     * Indica si la conexión tiene una transacción abierta
     * @param mysqli|resource|PDO $link
     */
    public function inTransaction($link);

    /**
     * Ejecuta una sentencia de datos sobre la conexión
     * @param mysqli|resource|PDO $link
     * @param string $sql
     * @return array
     */
    public function select($link, $sql);

    /**
     * Ejecuta una sentencia DDL sobre la conexión.
     * Si no hay transacción abierta crea una y la finaliza
     * @param mysqli|resource|PDO $link
     * @param string $sql
     */
    public function exec($link, $sql);

    /**
     * Compara las columnas indicadas en los arrays
     * @param string $dbType
     * @param string $xmlType
     */
    public function compareDataTypes($dbType, $xmlType);

    /**
     * Lista de tablas existentes en la conexión
     * @param mysqli|resource|PDO $link
     */
    public function listTables($link);

    /**
     * Escapa la cadena indicada
     * @param mysqli|resource|PDO $link
     * @param string $str
     */
    public function escapeString($link, $str);

    /**
     * Indica el formato de fecha que utiliza la BD
     */
    public function dateStyle();

    /**
     * Comprueba la existencia de una secuencia
     * @param mysqli|resource|PDO $link
     * @param string $tableName
     * @param string $default
     * @param string $colname
     */
    public function checkSequence($link, $tableName, $default, $colname);

    /**
     * Comprobación adicional a la existencia de una tabla
     * @param mysqli|resource|PDO $link
     * @param string $tableName
     * @param string $error
     */
    public function checkTableAux($link, $tableName, &$error);

    /**
     * Devuelve el tipo de conexión que utiliza
     * @return string
     */
    public function getType();
}
