<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core;

use Exception;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Permite crear cláusulas WHERE para consultas SQL.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class Where
{
    const FIELD_SEPARATOR = '|';

    /** @var DataBase */
    private static $db;

    /** @var string */
    public $fields;

    /** @var string */
    public $operator;

    /** @var string */
    public $operation;

    /** @var Where[] */
    public $subWhere;

    /** @var mixed */
    public $value;

    public function __construct(string $fields, $value, string $operator = '=', string $operation = 'AND')
    {
        $this->fields = $fields;
        $this->value = $value;
        $this->operator = $operator;
        $this->operation = $operation;
    }

    public static function between(string $fields, $value1, $value2): self
    {
        return new self($fields, [$value1, $value2], 'BETWEEN');
    }

    public static function column(string $fields, $value, string $operator = '=', string $operation = 'AND'): self
    {
        return new self($fields, $value, $operator, $operation);
    }

    public static function eq(string $fields, $value): self
    {
        return new self($fields, $value, '=');
    }

    public static function gt(string $fields, $value): self
    {
        return new self($fields, $value, '>');
    }

    public static function gte(string $fields, $value): self
    {
        return new self($fields, $value, '>=');
    }

    public static function in(string $fields, $values): self
    {
        return new self($fields, $values, 'IN');
    }

    public static function isNotNull(string $fields): self
    {
        return new self($fields, null, 'IS NOT');
    }

    public static function isNull(string $fields): self
    {
        return new self($fields, null, 'IS');
    }

    public static function like(string $fields, string $value): self
    {
        return new self($fields, $value, 'LIKE');
    }

    public static function lt(string $fields, $value): self
    {
        return new self($fields, $value, '<');
    }

    public static function lte(string $fields, $value): self
    {
        return new self($fields, $value, '<=');
    }

    public static function multiSql(array $where): string
    {
        $sql = '';
        foreach ($where as $item) {
            // si no es una instancia de Where, lanzamos una excepción
            if (!($item instanceof self)) {
                throw new Exception('Invalid where clause ' . print_r($item, true));
            }

            if (!empty($sql)) {
                $sql .= ' ' . $item->operation . ' ';
            }

            if ($item->operator === '(') {
                $sql .= '(' . self::multiSql($item->subWhere) . ')';
                continue;
            }

            $sql .= $item->sql();
        }

        return $sql;
    }

    public static function multiSqlLegacy(array $where): string
    {
        $sql = '';
        $group = false;

        foreach ($where as $key => $item) {
            // si es una instancia de DataBaseWhere, lo convertimos a sql
            if ($item instanceof DataBaseWhere) {
                $dbWhere = new self($item->fields, $item->value, $item->operator, $item->operation);

                if (!empty($sql)) {
                    $sql .= ' ' . $item->operation . ' ';
                }

                // si el siguiente elemento es un OR, lo agrupamos
                if (!$group && isset($where[$key + 1]) && $where[$key + 1] instanceof DataBaseWhere && $where[$key + 1]->operation === 'OR') {
                    $sql .= '(';
                    $group = true;
                }

                $sql .= $dbWhere->sql();

                // si estamos agrupando y el siguiente elemento no es un OR, cerramos el grupo
                if ($group && (!isset($where[$key + 1]) || !($where[$key + 1] instanceof DataBaseWhere) || $where[$key + 1]->operation !== 'OR')) {
                    $sql .= ')';
                    $group = false;
                }
                continue;
            }

            // si no es una instancia de Where, lanzamos una excepción
            if (!($item instanceof self)) {
                throw new Exception('Invalid where clause ' . print_r($item, true));
            }

            if (!empty($sql)) {
                $sql .= ' ' . $item->operation . ' ';
            }

            if ($item->operator === '(') {
                $sql .= '(' . self::multiSql($item->subWhere) . ')';
                continue;
            }

            $sql .= $item->sql();
        }

        return empty($sql) ? '' : ' WHERE ' . $sql;
    }

    public static function notBetween(string $fields, $value1, $value2): self
    {
        return new self($fields, [$value1, $value2], 'NOT BETWEEN');
    }

    public static function notEq(string $fields, $value): self
    {
        return new self($fields, $value, '!=');
    }

    public static function notIn(string $fields, $values): self
    {
        return new self($fields, $values, 'NOT IN');
    }

    public static function notLike(string $fields, string $value): self
    {
        return new self($fields, $value, 'NOT LIKE');
    }

    public static function or(string $fields, $value, string $operator = '='): self
    {
        return new self($fields, $value, $operator, 'OR');
    }

    public static function orBetween(string $fields, $value1, $value2): self
    {
        return new self($fields, [$value1, $value2], 'BETWEEN', 'OR');
    }

    public static function orEq(string $fields, $value): self
    {
        return new self($fields, $value, '=', 'OR');
    }

    public static function orGt(string $fields, $value): self
    {
        return new self($fields, $value, '>', 'OR');
    }

    public static function orGte(string $fields, $value): self
    {
        return new self($fields, $value, '>=', 'OR');
    }

    public static function orIn(string $fields, $values): self
    {
        return new self($fields, $values, 'IN', 'OR');
    }

    public static function orIsNotNull(string $fields): self
    {
        return new self($fields, null, 'IS NOT', 'OR');
    }

    public static function orIsNull(string $fields): self
    {
        return new self($fields, null, 'IS', 'OR');
    }

    public static function orLike(string $fields, string $value): self
    {
        return new self($fields, $value, 'LIKE', 'OR');
    }

    public static function orLt(string $fields, $value): self
    {
        return new self($fields, $value, '<', 'OR');
    }

    public static function orLte(string $fields, $value): self
    {
        return new self($fields, $value, '<=', 'OR');
    }

    public static function orNotBetween(string $fields, $value1, $value2): self
    {
        return new self($fields, [$value1, $value2], 'NOT BETWEEN', 'OR');
    }

    public static function orNotEq(string $fields, $value): self
    {
        return new self($fields, $value, '!=', 'OR');
    }

    public static function orNotIn(string $fields, $values): self
    {
        return new self($fields, $values, 'NOT IN', 'OR');
    }

    public static function orNotLike(string $fields, string $value): self
    {
        return new self($fields, $value, 'NOT LIKE', 'OR');
    }

    public static function orRegexp(string $fields, string $value): self
    {
        return new self($fields, $value, 'REGEXP', 'OR');
    }

    public static function orSub(array $where): self
    {
        return self::sub($where, 'OR');
    }

    public static function orXlike(string $fields, string $value): self
    {
        return new self($fields, $value, 'XLIKE', 'OR');
    }

    public static function regexp(string $fields, string $value): self
    {
        return new self($fields, $value, 'REGEXP');
    }

    public function sql(): string
    {
        $fields = explode(self::FIELD_SEPARATOR, $this->fields);

        $sql = count($fields) > 1 ? '(' : '';

        foreach ($fields as $key => $field) {
            if ($key > 0) {
                $sql .= ' OR ';
            }

            switch ($this->operator) {
                case '=':
                    $sql .= is_null($this->value) ?
                        self::sqlColumn($field) . ' IS NULL' :
                        self::sqlColumn($field) . ' = ' . self::sqlValue($this->value);
                    break;

                case '!=':
                case '<>':
                    $sql .= is_null($this->value) ?
                        self::sqlColumn($field) . ' IS NOT NULL' :
                        self::sqlColumn($field) . ' ' . $this->operator . ' ' . self::sqlValue($this->value);
                    break;

                case '>':
                case '<':
                case '>=':
                case '<=':
                case 'REGEXP':
                    $sql .= self::sqlColumn($field) . ' ' . $this->operator . ' ' . self::sqlValue($this->value);
                    break;

                case 'IS':
                case 'IS NOT':
                    $sql .= self::sqlColumn($field) . ' ' . $this->operator . ' NULL';
                    break;

                case 'IN':
                case 'NOT IN':
                    $sql .= self::sqlOperatorIn($field, $this->value, $this->operator);
                    break;

                case 'BETWEEN':
                case 'NOT BETWEEN':
                    $sql .= self::sqlOperatorBetween($field, $this->value, $this->operator);
                    break;

                case 'LIKE':
                case 'NOT LIKE':
                    $sql .= self::sqlOperatorLike($field, $this->value, $this->operator);
                    break;

                case 'XLIKE':
                    $sql .= self::sqlOperatorXLike($field, $this->value);
                    break;
            }
        }

        return count($fields) > 1 ? $sql . ')' : $sql;
    }

    public static function sub(array $where, string $operation = 'AND'): self
    {
        // comprobamos si el $where es un array de Where
        foreach ($where as $item) {
            // si no es una instancia de Where, lanzamos una excepción
            if (!($item instanceof self)) {
                throw new Exception('Invalid where clause ' . print_r($item, true));
            }
        }

        $item = new self('', '', '(', $operation);
        $item->subWhere = $where;
        return $item;
    }

    public static function xlike(string $fields, string $value): self
    {
        return new self($fields, $value, 'XLIKE');
    }

    private static function db(): DataBase
    {
        if (empty(self::$db)) {
            self::$db = new DataBase();
        }

        return self::$db;
    }

    private static function sqlColumn(string $field): string
    {
        // si empieza por integer: hacemos el cast
        if (substr($field, 0, 8) === 'integer:') {
            return self::db()->castInteger(substr($field, 8));
        }

        return self::db()->escapeColumn($field);
    }

    private static function sqlOperatorBetween(string $field, $values, string $operator): string
    {
        // si no es un array, lanzamos una excepción
        if (!is_array($values)) {
            throw new Exception('Invalid values in where clause ' . print_r($values, true));
        }

        // si no tiene 2 elementos, lanzamos una excepción
        if (count($values) !== 2) {
            throw new Exception('Invalid values in where clause ' . print_r($values, true));
        }

        return self::sqlColumn($field) . ' ' . $operator . ' ' . self::sqlValue($values[0])
            . ' AND ' . self::sqlValue($values[1]);
    }

    private static function sqlOperatorIn(string $field, $values, string $operator): string
    {
        if (is_array($values)) {
            $items = [];
            foreach ($values as $val) {
                $items[] = self::db()->var2str($val);
            }

            return self::sqlColumn($field) . ' ' . $operator . ' (' . implode(',', $items) . ')';
        }

        // si comienza por SELECT, lo tratamos como una subconsulta
        if (substr(strtoupper($values), 0, 6) === 'SELECT') {
            return self::sqlColumn($field) . ' ' . $operator . ' (' . $values . ')';
        }

        // es un string, separamos los valores por coma
        $items = [];
        foreach (explode(',', $values) as $val) {
            $items[] = self::db()->var2str(trim($val));
        }

        return self::sqlColumn($field) . ' ' . $operator . ' (' . implode(',', $items) . ')';
    }

    private static function sqlOperatorLike(string $field, string $value, string $operator): string
    {
        // si no contiene %, se los añadimos
        if (strpos($value, '%') === false) {
            return 'LOWER(' . self::sqlColumn($field) . ') ' . $operator
                . " LOWER('%" . self::db()->escapeString($value) . "%')";
        }

        // contiene algún comodín
        return 'LOWER(' . self::sqlColumn($field) . ') ' . $operator
            . " LOWER('" . self::db()->escapeString($value) . "')";
    }

    private static function sqlOperatorXLike(string $field, string $value): string
    {
        // separamos las palabras en $value
        $words = explode(' ', $value);

        // si solamente hay una palabra, la tratamos como un like
        if (count($words) === 1) {
            return '(' . self::sqlOperatorLike($field, $value, 'LIKE') . ')';
        }

        // si hay más de una palabra, las tratamos como un like con OR
        $sql = '';
        foreach ($words as $word) {
            if (!empty($sql)) {
                $sql .= ' AND ';
            }
            $sql .= self::sqlOperatorLike($field, trim($word), 'LIKE');
        }

        return '(' . $sql . ')';
    }

    private static function sqlValue($value): string
    {
        // si empieza por field: lo tratamos como un campo
        if (substr($value, 0, 6) === 'field:') {
            return self::sqlColumn(substr($value, 6));
        }

        // si no, lo tratamos como un valor
        return self::db()->var2str($value);
    }
}
