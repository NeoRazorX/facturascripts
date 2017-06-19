<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

namespace FacturaScripts\Core\Base;

/**
 * Clase genérica de acceso a la base de datos, ya sea MySQL o PostgreSQL.
 * Esta clase se utiliza únicamente para tener los métodos visibles en netbeans.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class DataBase {

    private static $autoTransactions;
    private static $engine;
    private static $tableList;

    public function __construct() {
        if (!isset(self::$engine)) {
            
            if (strtolower(FS_DB_TYPE) == 'mysql') {
                self::$engine = new Mysql();
            } else {
                self::$engine = new Postgresql();
            }

            self::$autoTransactions = TRUE;
            self::$tableList = FALSE;
        }
    }

    /**
     * Devuelve el valor de autoTransacions, para saber si las transacciones
     * automáticas están activadas o no.
     * @return boolean
     */
    public function getAutoTransactions() {
        return self::$autoTransactions;
    }

    /**
     * Activa/desactiva las transacciones automáticas en la función exec()
     * @param boolean $value
     */
    public function setAutoTransactions($value) {
        self::$autoTransactions = $value;
    }

    /**
     * Conecta a la base de datos.
     * @return boolean
     */
    public function connect() {
        return self::$engine->connect();
    }

    /**
     * Devuelve TRUE si se está conestado a la base de datos.
     * @return boolean
     */
    public function connected() {
        return self::$engine->connected();
    }

    /**
     * Desconecta de la base de datos.
     * @return boolean
     */
    public function close() {
        return self::$engine->close();
    }

    /**
     * Devuelve el motor de base de datos usado y la versión.
     * @return string
     */
    public function version() {
        return self::$engine->version();
    }

    /**
     * Devuelve el nº de selects a la base de datos.
     * @return integer
     */
    public function getSelects() {
        return self::$engine->getSelects();
    }

    /**
     * Devuelve el nº de transacciones con la base de datos.
     * @return integer
     */
    public function getTransactions() {
        return self::$engine->getTransactions();
    }

    /**
     * Devuelve un array con las columnas de una tabla dada.
     * @param string $tableName
     * @return mixed
     */
    public function getColumns($tableName) {
        return self::$engine->getColumns($tableName);
    }

    /**
     * Devuelve una array con las restricciones de una tabla dada.
     * @param string $tableName
     * @param boolean $extended
     * @return mixed
     */
    public function getConstraints($tableName, $extended = FALSE) {
        if ($extended) {
            return self::$engine->getConstraintsExtended($tableName);
        } else {
            return self::$engine->getConstraints($tableName);
        }
    }

    /**
     * Devuelve una array con los indices de una tabla dada.
     * @param string $tableName
     * @return mixed
     */
    public function getIndexes($tableName) {
        return self::$engine->getIndexes($tableName);
    }

    /**
     * Devuelve un array con los nombres de las tablas de la base de datos.
     * @return mixed
     */
    public function listTables() {
        if (self::$tableList === FALSE) {
            self::$tableList = self::$engine->listTables();
        }

        return self::$tableList;
    }

    /**
     * Devuelve TRUE si la tabla existe, FALSE en caso contrario.
     * @param string $name
     * @param mixed $list
     * @return boolean
     */
    public function tableExists($name, $list = FALSE) {
        $result = FALSE;

        if ($list === FALSE) {
            $list = $this->listTables();
        }

        foreach ($list as $table) {
            if ($table['name'] == $name) {
                $result = TRUE;
                break;
            }
        }

        return $result;
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, y devuelve un array con los resultados,
     * o false en caso de fallo.
     * @param string $sql
     * @return mixed
     */
    public function select($sql) {
        return self::$engine->select($sql);
    }

    /**
     * Ejecuta una sentencia SQL de tipo select, pero con paginación,
     * y devuelve un array con los resultados o false en caso de fallo.
     * Limit es el número de elementos que quieres que devuelva.
     * Offset es el número de resultado desde el que quieres que empiece.
     * @param string $sql
     * @param integer $limit
     * @param integer $offset
     * @return mixed
     */
    public function selectLimit($sql, $limit = FS_ITEM_LIMIT, $offset = 0) {
        return self::$engine->selectLimit($sql, $limit, $offset);
    }

    /**
     * Ejecuta sentencias SQL sobre la base de datos (inserts, updates o deletes).
     * Para hacer selects, mejor usar select() o selecLimit().
     * Por defecto se inicia una transacción, se ejecutan las consultas, y si todo
     * sale bien, se guarda, sino se deshace.
     * Se puede evitar este modo de transacción si se pone false
     * en el parametro transaction, o con la función setAutoTransactions(FALSE)
     * @param string $sql
     * @param boolean $transaction
     * @return boolean
     */
    public function exec($sql, $transaction = NULL) {
        /// usamos self::$autoTransactions como valor por defecto para la función
        if (is_null($transaction)) {
            $transaction = self::$autoTransactions;
        }

        /// limpiamos la lista de tablas, ya que podría haber cambios al ejecutar este sql.
        self::$tableList = FALSE;

        return self::$engine->exec($sql, $transaction);
    }

    /**
     * Devuleve el último ID asignado al hacer un INSERT en la base de datos.
     * @return integer
     */
    public function lastval() {
        return self::$engine->lastval();
    }

    /**
     * Inicia una transacción SQL.
     * @return boolean
     */
    public function beginTransaction() {
        return self::$engine->beginTransaction();
    }

    /**
     * Guarda los cambios de una transacción SQL.
     * @return boolean
     */
    public function commit() {
        return self::$engine->commit();
    }

    /**
     * Deshace los cambios de una transacción SQL.
     * @return boolean
     */
    public function rollback() {
        return self::$engine->rollback();
    }

    /**
     * Escapa las comillas de la cadena de texto.
     * @param string $str
     * @return string
     */
    public function escapeString($str) {
        return self::$engine->escapeString($str);
    }

    /**
     * Devuelve el estilo de fecha del motor de base de datos.
     * @return string
     */
    public function dateStyle() {
        return self::$engine->dateStyle();
    }

    /**
     * Devuelve el SQL necesario para convertir la columna a entero.
     * @param string $colName
     * @return string
     */
    public function sql2int($colName) {
        return self::$engine->sql2int($colName);
    }

    /**
     * Compara dos arrays de columnas, devuelve una sentencia sql en caso de encontrar diferencias.
     * @param string $tableName
     * @param array $xmlCols
     * @param array $dbCols
     * @return string
     */
    public function compareColumns($tableName, $xmlCols, $dbCols) {
        return self::$engine->compareColumns($tableName, $xmlCols, $dbCols);
    }

    /**
     * Compara dos arrays de restricciones, devuelve una sentencia sql en caso de encontrar diferencias.
     * @param string $tableName
     * @param array $xmlCons
     * @param array $dbCons
     * @param boolean $deleteOnly
     * @return string
     */
    public function compareConstraints($tableName, $xmlCons, $dbCons, $deleteOnly = FALSE) {
        return self::$engine->compareConstraints($tableName, $xmlCons, $dbCons, $deleteOnly);
    }

    /**
     * Devuelve la sentencia sql necesaria para crear una tabla con la estructura proporcionada.
     * @param string $tableName
     * @param array $xmlCols
     * @param array $xmlCons
     * @return string
     */
    public function generateTable($tableName, $xmlCols, $xmlCons) {
        return self::$engine->generateTable($tableName, $xmlCols, $xmlCons);
    }

    /**
     * Realiza comprobaciones extra a la tabla.
     * @param string $tableName
     * @return boolean
     */
    public function checkTableAux($tableName) {
        return self::$engine->checkTableAux($tableName);
    }

}
