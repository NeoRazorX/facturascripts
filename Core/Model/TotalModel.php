<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

/**
 * Modelo auxiliar para cargar una lista de totales,
 * con o sin agrupación por código.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class TotalModel
{
    /** @var DataBase */
    private static $dataBase;

    /** @var string */
    public $code;

    /** @var array */
    public $totals;

    public function __construct(array $data = [])
    {
        $this->code = '';
        $this->totals = [];
        foreach ($data as $field => $value) {
            if ($field === 'code') {
                $this->code = $value;
                continue;
            }

            $this->totals[$field] = empty($value) ? 0 : $value;
        }
    }

    /**
     * Carga una lista de TotalModel (código y campos de estadísticas) para la tabla indicada.
     *
     * @param string $tableName
     * @param array $where
     * @param array $fieldList (['key' => 'SUM(total)', 'key2' => 'MAX(total)' ...])
     * @param string $fieldCode (para múltiples filas agrupadas por el campo código)
     *
     * @return static[]
     */
    public static function all(string $tableName, array $where, array $fieldList, string $fieldCode = ''): array
    {
        // validamos el nombre de tabla para evitar SQL injection
        if (false === self::isValidTableName($tableName)) {
            Tools::log()->error('invalid-table-name: ' . $tableName);
            return self::emptyResult($fieldList);
        }

        // validamos el campo código (puede ser vacío)
        if (false === self::isValidFieldName($fieldCode)) {
            Tools::log()->error('invalid-field-name: ' . $fieldCode);
            return self::emptyResult($fieldList);
        }

        // validamos las claves y valores de fieldList
        foreach ($fieldList as $alias => $expression) {
            if (false === self::isValidAlias((string)$alias)
                || false === self::isValidAggregate((string)$expression)) {
                Tools::log()->error('invalid-field-list: ' . $alias . ' => ' . $expression);
                return self::emptyResult($fieldList);
            }
        }

        $result = [];
        if (self::dataBase()->tableExists($tableName)) {
            $sql = 'SELECT ' . self::getFieldSQL($fieldCode, $fieldList);
            $groupby = empty($fieldCode) ? ';' : ' GROUP BY 1 ORDER BY 1;';

            $sql .= ' FROM ' . self::dataBase()->escapeColumn($tableName) . Where::multiSqlLegacy($where) . $groupby;
            $data = self::dataBase()->select($sql);
            foreach ($data as $row) {
                $result[] = new static($row);
            }
        }

        // si el resultado está vacío, devolvemos siempre un registro con los totales a cero
        if (empty($result)) {
            return self::emptyResult($fieldList);
        }

        return $result;
    }

    /**
     * Reinicia los totales a 0.0.
     *
     * @param array $totalFields
     */
    public function clearTotals(array $totalFields): void
    {
        foreach ($totalFields as $fieldName) {
            $this->totals[$fieldName] = 0.0;
        }
    }

    public static function sum(string $tableName, string $fieldName, array $where): float
    {
        // validamos nombres para evitar SQL injection
        if (false === self::isValidTableName($tableName)) {
            Tools::log()->error('invalid-table-name: ' . $tableName);
            return 0.0;
        }
        if (false === self::isValidFieldName($fieldName) || empty($fieldName)) {
            Tools::log()->error('invalid-field-name: ' . $fieldName);
            return 0.0;
        }

        if (false === self::dataBase()->tableExists($tableName)) {
            return 0.0;
        }

        $sql = 'SELECT SUM(' . self::dataBase()->escapeColumn($fieldName) . ') as sum'
            . ' FROM ' . self::dataBase()->escapeColumn($tableName) . Where::multiSqlLegacy($where);
        foreach (self::dataBase()->select($sql) as $row) {
            return (float)$row['sum'];
        }
        return 0.0;
    }

    private static function dataBase(): DataBase
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
            self::$dataBase->connect();
        }

        return self::$dataBase;
    }

    /**
     * Devuelve un resultado con los totales a cero para los campos indicados.
     *
     * @param array $fieldList
     * @return static[]
     */
    private static function emptyResult(array $fieldList): array
    {
        $item = new static();
        $item->clearTotals(array_keys($fieldList));
        return [$item];
    }

    /**
     * Devuelve los campos como parte de la consulta SQL.
     *
     * @param string $fieldCode
     * @param array $fieldList
     *
     * @return string
     */
    private static function getFieldSQL(string $fieldCode, array $fieldList): string
    {
        $result = '';
        $comma = '';

        if (!empty($fieldCode)) {
            $result .= $fieldCode . ' AS code';
            $comma = ', ';
        }

        foreach ($fieldList as $fieldName => $fieldSQL) {
            $result .= $comma . $fieldSQL . ' AS ' . self::dataBase()->escapeColumn($fieldName);
            $comma = ', ';
        }

        return $result;
    }

    /**
     * Valida una expresión agregada del tipo SUM(campo), COUNT(*), AVG(tabla.campo), etc.
     */
    private static function isValidAggregate(string $expression): bool
    {
        $expression = trim($expression);
        $ident = '[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)?';
        return preg_match('/^(SUM|AVG|MIN|MAX|COUNT)\(\s*(\*|' . $ident . ')\s*\)$/i', $expression) === 1;
    }

    /**
     * Valida un alias de columna. Solo permite letras, números y guiones bajos.
     */
    private static function isValidAlias(string $alias): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $alias) === 1;
    }

    /**
     * Valida un nombre de campo. Permite identificadores simples,
     * tabla.campo y las funciones lower(), upper(), substring(), concat().
     */
    private static function isValidFieldName(string $fieldName): bool
    {
        if ($fieldName === '') {
            return true;
        }

        $fieldName = trim($fieldName);
        $ident = '[a-zA-Z_][a-zA-Z0-9_]*(?:\.[a-zA-Z_][a-zA-Z0-9_]*)?';

        if (preg_match('/^' . $ident . '$/', $fieldName)) {
            return true;
        }

        if (preg_match('/^(lower|upper)\((' . $ident . ')\)$/i', $fieldName)) {
            return true;
        }

        if (preg_match('/^substring\((' . $ident . '),\s*(\d+)\s*,\s*(\d+)\s*\)$/i', $fieldName, $m)) {
            $start = (int)$m[2];
            $len = (int)$m[3];
            return $start >= 1 && $len >= 1 && $len <= 1000;
        }

        $arg = "(?:$ident|'[^']*')";
        if (preg_match('/^concat\(\s*' . $arg . '(?:\s*,\s*' . $arg . ')+\s*\)$/i', $fieldName)) {
            return true;
        }

        return false;
    }

    /**
     * Valida un nombre de tabla. Solo permite letras, números y guiones bajos.
     */
    private static function isValidTableName(string $tableName): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName) === 1;
    }
}
