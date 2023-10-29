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

/**
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

    public static function and(string $fields, $value, string $operator = '='): self
    {
        return new self($fields, $value, $operator, 'AND');
    }

    public static function andSub(array $where): self
    {
        return self::sub($where, 'AND');
    }

    public static function between(string $fields, $value1, $value2): self
    {
        return new self($fields, [$value1, $value2], 'BETWEEN');
    }

    public static function column(string $fields, $value, string $operator = '=', string $operation = 'AND'): self
    {
        return new self($fields, $value, $operator, $operation);
    }

    public static function gt(string $fields, $value): self
    {
        return new self($fields, $value, '>');
    }

    public static function gte(string $fields, $value): self
    {
        return new self($fields, $value, '>=');
    }

    public static function in(string $fields, $value): self
    {
        return new self($fields, $value, 'IN');
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

            if (empty($item->operator)) {
                $sql .= '(' . self::multiSql($item->subWhere) . ')';
                continue;
            }

            $sql .= $item->sql();
        }

        return $sql;
    }

    public static function notBetween(string $fields, $value1, $value2): self
    {
        return new self($fields, [$value1, $value2], 'NOT BETWEEN');
    }

    public static function notEq(string $fields, $value): self
    {
        return new self($fields, $value, '!=');
    }

    public static function notLike(string $fields, string $value): self
    {
        return new self($fields, $value, 'NOT LIKE');
    }

    public static function or(string $fields, $value, string $operator = '='): self
    {
        return new self($fields, $value, $operator, 'OR');
    }

    public static function orSub(array $where): self
    {
        return self::sub($where, 'OR');
    }

    public function sql(): string
    {
        $fields = explode(self::FIELD_SEPARATOR, $this->fields);

        $sql = count($fields) > 1 ? '(' : '';

        foreach ($fields as $field) {
            if (!empty($sql)) {
                $sql .= ' OR ';
            }

            switch ($this->operator) {
                case '=':
                    $sql .= is_null($this->value) ?
                        self::db()->escapeColumn($field) . ' IS NULL' :
                        self::db()->escapeColumn($field) . ' = ' . self::db()->var2str($this->value);
                    break;

                case '!=':
                case '<>':
                    $sql .= is_null($this->value) ?
                        self::db()->escapeColumn($field) . ' IS NOT NULL' :
                        self::db()->escapeColumn($field) . ' != ' . self::db()->var2str($this->value);
                    break;

                case '>':
                case '<':
                case '>=':
                case '<=':
                    $sql .= self::db()->escapeColumn($field) . ' ' . $this->operator . ' ' . self::db()->var2str($this->value);
                    break;

                case 'IS':
                case 'IS NOT':
                    $sql .= self::db()->escapeColumn($field) . ' ' . $this->operator . ' NULL';
                    break;

                case 'IN':
                case 'NOT IN':
                    if (is_array($this->value)) {
                        $values = [];
                        foreach ($this->value as $value) {
                            $values[] = self::db()->var2str($value);
                        }
                        $sql .= self::db()->escapeColumn($field) . ' ' . $this->operator . ' (' . implode(', ', $values) . ')';
                        break;
                    }
                    $sql .= self::db()->escapeColumn($field) . ' ' . $this->operator . ' (' . $this->value . ')';
                    break;

                case 'BETWEEN':
                case 'NOT BETWEEN':
                    // si no es un array, lanzamos una excepción
                    if (!is_array($this->value)) {
                        throw new Exception('Invalid where clause ' . print_r($this, true));
                    }
                    // si no tiene 2 elementos, lanzamos una excepción
                    if (count($this->value) !== 2) {
                        throw new Exception('Invalid where clause ' . print_r($this, true));
                    }
                    $sql .= self::db()->escapeColumn($field) . ' ' . $this->operator . ' ' . self::db()->var2str($this->value[0])
                        . ' AND ' . self::db()->var2str($this->value[1]);
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

    private static function sqlOperatorLike(string $field, string $value, string $operator): string
    {
        // si no contiene %, se los añadimos
        if (strpos($value, '%') === false) {
            return 'LOWER(' . self::db()->escapeColumn($field) . ') ' . $operator
                . " LOWER('%" . self::db()->escapeString($value) . "%')";
        }

        // contiene algún comodín
        return 'LOWER(' . self::db()->escapeColumn($field) . ') ' . $operator
            . " LOWER(" . self::db()->escapeString($value) . ")";
    }

    private static function sqlOperatorXLike(string $field, string $value): string
    {
        $sql = '(';

        // separamos las palabras en $value
        $words = explode(' ', $value);
        foreach ($words as $word) {
            if (!empty($sql)) {
                $sql .= ' OR ';
            }
            $sql .= self::sqlOperatorLike($field, trim($word), 'LIKE');
        }

        return $sql . ')';
    }
}