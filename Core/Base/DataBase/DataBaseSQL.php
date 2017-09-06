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

/**
 * Interface para gestionar las sentencias SQL necesarias
 * por el motor de base de datos
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
interface DataBaseSQL
{
    /**
     * Sentencia necesaria para convertir la columna a entero.
     *
     * @param string $colName
     *
     * @return string
     */
    public function sql2Int($colName);

    /**
     * Sentencia SQL para obtener el último valor de una secuencia o ID
     */
    public function sqlLastValue();

    /**
     * Sentencia SQL para obtener las columnas de una tabla
     *
     * @param string $tableName
     */
    public function sqlColumns($tableName);

    /**
     * Sentencia SQL para obtener las constraints de una tabla
     *
     * @param string $tableName
     */
    public function sqlConstraints($tableName);

    /**
     * Sentencia SQL para obtener las constraints (extendidas) de una tabla
     *
     * @param string $tableName
     */
    public function sqlConstraintsExtended($tableName);

    /**
     * Genera el SQL para establecer las restricciones proporcionadas.
     *
     * @param array $xmlCons
     */
    public function sqlTableConstraints($xmlCons);

    /**
     * Sentencia SQL para obtener los indices de una tabla
     *
     * @param string $tableName
     */
    public function sqlIndexes($tableName);

    /**
     * Sentencia SQL para crear una tabla
     *
     * @param string $tableName
     * @param array  $columns
     * @param array  $constraints
     */
    public function sqlCreateTable($tableName, $columns, $constraints);

    /**
     * Sentencia SQL para añadir una columna a una tabla
     *
     * @param string $tableName
     * @param array  $colData
     */
    public function sqlAlterAddColumn($tableName, $colData);

    /**
     * Sentencia SQL para modificar la definición de una columna de una tabla
     *
     * @param string $tableName
     * @param array  $colData
     */
    public function sqlAlterModifyColumn($tableName, $colData);

    /**
     * Sentencia SQL para modificar valor por defecto de una columna de una tabla
     *
     * @param string $tableName
     * @param array  $colData
     */
    public function sqlAlterConstraintDefault($tableName, $colData);

    /**
     * Sentencia SQL para modificar un constraint null de una columna de una tabla
     *
     * @param string $tableName
     * @param array  $colData
     */
    public function sqlAlterConstraintNull($tableName, $colData);

    /**
     * Sentencia SQL para eliminar una constraint de una tabla
     *
     * @param string $tableName
     * @param array  $colData
     */
    public function sqlDropConstraint($tableName, $colData);

    /**
     * Sentencia SQL para añadir una constraint de una tabla
     *
     * @param string $tableName
     * @param string $constraintName
     * @param string $sql
     */
    public function sqlAddConstraint($tableName, $constraintName, $sql);

    /**
     * Sentencia para crear una secuencia
     *
     * @param string $seqName
     */
    public function sqlSequenceExists($seqName);
}
