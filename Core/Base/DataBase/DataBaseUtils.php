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
 * Clase engloba utilidades para el control y manejo de objetos en la base de
 * datos. Necesita el enlace con el tipo de base de datos (MySQL o PostgreSQL)
 * usado al crear la clase DataBase. (DataBase::$engine)
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class DataBaseUtils {

    /**
     * Enlace al motor de base de datos seleccionado en la configuración
     * @var DatabaseEngine
     */
    private static $engine;

    /**
     * Construye y prepara la clase para su uso
     * @param DatabaseEngine $engine
     */
    public function __construct($engine) {
        self::$engine = $engine;
    }

    /**
     * Busca una columna con un valor por su nombre en un array
     * @param array $items
     * @param string $index
     * @param string $value
     * @return array
     */
    private function searchInArray($items, $index, $value) {
        $result = [];
        foreach ($items as $column) {
            if ($column[$index] === $value) {
                $result = $column;
                break;
            }
        }

        return $result;
    }

    /**
     * Compara los tipos de datos de una columna.
     * Devuelve TRUE si son iguales.
     * @param string $dbType
     * @param string $xmlType
     * @return boolean
     */
    public function compareDataTypes($dbType, $xmlType) {
        $db = strtolower($dbType);
        $xml = strtolower($xmlType);

        $result = (
            (FS_CHECK_DB_TYPES !== '1') ||
            self::$engine->compareDataTypes($db, $xml) ||
            ($xml === 'serial') ||
            (
                strpos($db, 'time') === 0 &&
                strpos($xml, 'time') === 0
            )
        );

        return $result;
    }

    /**
     * Compara dos arrays de columnas, devuelve una sentencia sql en caso de encontrar diferencias.
     * @param string $tableName
     * @param array $xmlCols
     * @param array $dbCols
     * @return string
     */
    public function compareColumns($tableName, $xmlCols, $dbCols) {
        $result = '';
        foreach ($xmlCols as $xml_col) {
            if (strtolower($xml_col['tipo']) === 'integer') {
                /**
                 * Desde la pestaña avanzado el panel de control se puede cambiar
                 * el tipo de entero a usar en las columnas.
                 */
                $xml_col['tipo'] = FS_DB_INTEGER;
            }

            $column = $this->searchInArray($dbCols, 'name', $xml_col['nombre']);
            if (empty($column)) {
                $result .= self::$engine->sqlAlterAddColumn($tableName, $xml_col);
                continue;
            }

            if (!$this->compareDataTypes($column['type'], $xml_col['tipo'])) {
                $result .= self::$engine->sqlAlterModifyColumn($tableName, $xml_col);
            }

            if ($column['default'] === NULL && $xml_col['defecto'] === '') {
                $result .= self::$engine->sqlAlterConstraintDefault($tableName, $xml_col);
            }

            if ($column['is_nullable'] !== $xml_col['nulo']) {
                $result .= self::$engine->sqlAlterConstraintNull($tableName, $xml_col);
            }
        }

        return $result;
    }

    /**
     * Compara dos arrays de restricciones, devuelve una sentencia SQL en caso de encontrar diferencias.
     * @param string $tableName
     * @param array $xmlCons
     * @param array $dbCons
     * @param boolean $deleteOnly
     * @return string
     */
    public function compareConstraints($tableName, $xmlCons, $dbCons, $deleteOnly = FALSE) {
        $result = '';

        foreach ($dbCons as $db_con) {
            if (strpos('PRIMARY;UNIQUE', $db_con['name']) === FALSE) {
                $column = $this->searchInArray($xmlCons, 'nombre', $db_con['name']);
                if (empty($column)) {
                    $result .= self::$engine->sqlDropConstraint($tableName, $db_con);
                }
            }
        }

        if (!empty($xmlCons) && !$deleteOnly && FS_FOREIGN_KEYS === '1') {
            foreach ($xmlCons as $xml_con) {
                if (strpos($xml_con['consulta'], 'PRIMARY') === 0) {
                    continue;
                }

                $column = $this->searchInArray($dbCons, 'name', $xml_con['nombre']);
                if (empty($column)) {
                    $result .= self::$engine->sqlAddConstraint($tableName, $xml_con['nombre'], $xml_con['consulta']);
                }
            }
        }

        return $result;
    }

    /**
     * Devuelve la sentencia SQL necesaria para crear una tabla con la estructura proporcionada.
     * @param string $tableName
     * @param array $xmlCols
     * @param array $xmlCons
     * @return string
     */
    public function generateTable($tableName, $xmlCols, $xmlCons) {
        return self::$engine->sqlCreateTable($tableName, $xmlCols, $xmlCons);
    }
}
