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

namespace FacturaScripts\Core\Base\DataBase;

/**
 * Interface para cada uno de los motores de base de datos compatibles
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
interface DatabaseEngine {

    /**
     * Genera el SQL para establecer las restricciones proporcionadas.
     * @param array $xmlCons
     */
    public function generateTableConstraints($xmlCons);

    /**
     * Convierte los datos leidos del sqlColumns a estructura de trabajo
     * @param array $colData
     */
    public function columnFromData($colData);

    /**
     * Información sobre el motor de base de datos
     * @param mixed $link
     */
    public function version($link);

    /**
     * Conecta con la base de datos
     * @param string $error
     */
    public function connect(&$error);

    /**
     * Cierra la conexión con la base de datos
     * @param mixed $link
     */
    public function close($link);

    /**
     * Último mensaje de error generado un operación con la BD
     * @param mixed $link
     */
    public function errorMessage($link);

    /**
     * Inicia una transacción sobre la conexión
     * @param mixed $link
     */
    public function beginTransaction($link);

    /**
     * Confirma las operaciones realizadas sobre la conexión
     * desde el beginTransaction
     * @param mixed $link
     */
    public function commit($link);

    /**
     * Deshace las operaciones realizadas sobre la conexión
     * desde el beginTransaction
     * @param mixed $link
     */
    public function rollback($link);

    /**
     * Indica si la conexión tiene una transacción abierta
     * @param mixed $link
     */
    public function inTransaction($link);

    /**
     * Ejecuta una sentencia de datos sobre la conexión
     * @param mixed $link
     * @param string $sql
     */
    public function select($link, $sql);

    /**
     * Ejecuta una sentencia DDL sobre la conexión.
     * Si no hay transacción abierta crea una y la finaliza
     * @param mixed $link
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
     * @param mixed $link
     */
    public function listTables($link);

    /**
     * Escapa la cadena indicada
     * @param mixed $link
     * @param string $str
     */
    public function escapeString($link, $str);

    /**
     * Indica el formato de fecha que utiliza la BD
     */
    public function dateStyle();

    /**
     * Indica el SQL a usar para convertir la columna en Integer
     * @param string $colName
     */
    public function sql2int($colName);

    /**
     * Comprueba la existencia de una secuencia
     * @param mixed $link
     * @param string $tableName
     * @param string $default
     * @param string $colname
     */
    public function checkSequence($link, $tableName, $default, $colname);

    /**
     * Comprobación adicional a la existencia de una tabla
     * @param mixed $link
     * @param string $tableName
     * @param string $error
     */
    public function checkTableAux($link, $tableName, &$error);

    /**
     * Sentencia SQL para obtener el último valor de una secuencia o ID
     */
    public function sqlLastValue();

    /**
     * Sentencia SQL para obtener las columnas de una tabla
     * @param string $tableName
     */
    public function sqlColumns($tableName);

    /**
     * Sentencia SQL para obtener las constraints de una tabla
     * @param string $tableName
     */
    public function sqlConstraints($tableName);

    /**
     * Sentencia SQL para obtener las constraints (extendidas) de una tabla
     * @param string $tableName
     */
    public function sqlConstraintsExtended($tableName);

    /**
     * Sentencia SQL para obtener los indices de una tabla
     * @param string $tableName
     */
    public function sqlIndexes($tableName);

    /**
     * Sentencia SQL para crear una tabla
     * @param string $tableName
     * @param array $columns
     * @param array $constraints
     */
    public function sqlCreateTable($tableName, $columns, $constraints);

    /**
     * Sentencia SQL para añadir una columna a una tabla
     * @param string $tableName
     * @param array $colData
     */
    public function sqlAlterAddColumn($tableName, $colData);

    /**
     * Sentencia SQL para modificar la definición de una columna de una tabla
     * @param string $tableName
     * @param array $colData
     */
    public function sqlAlterModifyColumn($tableName, $colData);

    /**
     * Sentencia SQL para modificar valor por defecto de una columna de una tabla
     * @param string $tableName
     * @param array $colData
     */
    public function sqlAlterConstraintDefault($tableName, $colData);

    /**
     * Sentencia SQL para modificar un constraint null de una columna de una tabla
     * @param string $tableName
     * @param array $colData
     */
    public function sqlAlterConstraintNull($tableName, $colData);

    /**
     * Sentencia SQL para eliminar una constraint de una tabla
     * @param string $tableName
     * @param array $colData
     */
    public function sqlDropConstraint($tableName, $colData);

    /**
     * Sentencia SQL para añadir una constraint de una tabla
     * @param string $tableName
     * @param string $constraintName
     * @param string $sql
     */
    public function sqlAddConstraint($tableName, $constraintName, $sql);

    /**
     * Sentencia para crear una secuencia
     * @param string $seqName
     */
    public function sqlSequenceExists($seqName);
}
