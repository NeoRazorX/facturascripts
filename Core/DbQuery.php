<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Permite realizar consultas a la base de datos de forma sencilla.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class DbQuery
{
    /** @var DataBase */
    private static $db;

    /** @var string */
    public $fields = '*';

    /** @var string */
    public $groupBy;

    /** @var string */
    public $having;

    /** @var int */
    public $limit = 0;

    /** @var int */
    public $offset = 0;

    /** @var array */
    public $orderBy = [];

    /** @var string */
    private $table;

    /** @var Where[] */
    private $where = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function __call($method, $parameters)
    {
        // Si se llama al where dinámicamente
        // whereNombre(), whereCiudad()
        if (str_starts_with($method, 'where')) {
            $field = strtolower(substr($method, 5));
            return $this->whereEq($field, $parameters[0]);
        }

        if (false === method_exists($this, $method)) {
            throw new Exception('Call to undefined method ' . get_class($this) . '::' . $method . '()');
        }

        return $this->$method(...$parameters);
    }

    public function array(string $key, string $value): array
    {
        $result = [];
        foreach ($this->get() as $row) {
            $result[$row[$key]] = $row[$value];
        }

        return $result;
    }

    public function avg(string $field, ?int $decimals = null): float
    {
        $this->fields = 'AVG(' . self::db()->escapeColumn($field) . ') as _avg';

        $row = $this->first();
        return is_null($decimals) ?
            (float)$row['_avg'] :
            round((float)$row['_avg'], $decimals);
    }

    public function avgArray(string $field, string $groupByKey): array
    {
        $this->fields = self::db()->escapeColumn($groupByKey) . ', AVG(' . self::db()->escapeColumn($field) . ') as _avg';

        return $this->groupBy($groupByKey)->array($groupByKey, '_avg');
    }

    public function count(string $field = ''): int
    {
        if ($field !== '') {
            $this->fields = 'COUNT(DISTINCT ' . self::db()->escapeColumn($field) . ') as _count';
        } elseif ($this->fields === '*' || empty($this->fields)) {
            $this->fields = 'COUNT(*) as _count';
        } else {
            $this->fields = 'COUNT(' . $this->fields . ') as _count';
        }

        foreach ($this->first() as $value) {
            return (int)$value;
        }

        return 0;
    }

    public function countArray(string $field, string $groupByKey): array
    {
        $this->fields = self::db()->escapeColumn($groupByKey) . ', COUNT(' . self::db()->escapeColumn($field) . ') as _count';

        return $this->groupBy($groupByKey)->array($groupByKey, '_count');
    }

    public function delete(): bool
    {
        $sql = 'DELETE FROM ' . self::db()->escapeColumn($this->table);

        if (!empty($this->where)) {
            $sql .= Where::multiSqlLegacy($this->where);
        }

        return self::db()->exec($sql);
    }

    public function first(): array
    {
        $this->limit = 1;
        $this->offset = 0;

        foreach ($this->get() as $row) {
            return $row;
        }

        return [];
    }

    public function get(): array
    {
        return self::db()->selectLimit($this->sql(), $this->limit, $this->offset);
    }

    public function groupBy(string $fields): self
    {
        $list = [];
        foreach (explode(',', $fields) as $field) {
            $list[] = self::db()->escapeColumn(trim($field));
        }

        $this->groupBy = implode(', ', $list);

        return $this;
    }

    public function having(string $having): self
    {
        $this->having = $having;

        return $this;
    }

    public function insert(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $fields = [];
        $values = [];

        // comprobamos si es una inserción simple (no es un array de arrays)
        $first = reset($data);
        if (!is_array($first)) {
            foreach ($data as $field => $value) {
                $fields[] = self::db()->escapeColumn($field);
                $values[] = self::db()->var2str($value);
            }

            $sql = 'INSERT INTO ' . self::db()->escapeColumn($this->table)
                . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ');';
            return self::db()->exec($sql);
        }

        // inserción múltiple
        foreach (array_keys($first) as $field) {
            $fields[] = self::db()->escapeColumn($field);
        }

        foreach ($data as $row) {
            $line = [];
            foreach ($row as $value) {
                $line[] = self::db()->var2str($value);
            }
            $values[] = '(' . implode(', ', $line) . ')';
        }

        $sql = 'INSERT INTO ' . self::db()->escapeColumn($this->table)
            . ' (' . implode(', ', $fields) . ') VALUES ' . implode(', ', $values) . ';';
        return self::db()->exec($sql);
    }

    public function insertGetId(array $data): ?int
    {
        if ($this->insert($data)) {
            return self::db()->lastval();
        }

        return null;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function max(string $field, ?int $decimals = null): float
    {
        $max = $this->maxString($field);
        return is_null($decimals) ?
            (float)$max :
            round((float)$max, $decimals);
    }

    public function maxString(string $field): string
    {
        $this->fields = 'MAX(' . self::db()->escapeColumn($field) . ') as _max';
        return $this->first()['_max'];
    }

    public function maxArray(string $field, string $groupByKey): array
    {
        $this->fields = self::db()->escapeColumn($groupByKey) . ', MAX(' . self::db()->escapeColumn($field) . ') as _max';

        return $this->groupBy($groupByKey)->array($groupByKey, '_max');
    }

    public function min(string $field, ?int $decimals = null): float
    {
        $min = $this->minString($field);
        return is_null($decimals) ?
            (float)$min :
            round((float)$min, $decimals);
    }

    public function minString(string $field): string
    {
        $this->fields = 'MIN(' . self::db()->escapeColumn($field) . ') as _min';
        return $this->first()['_min'];
    }

    public function minArray(string $field, string $groupByKey): array
    {
        $this->fields = self::db()->escapeColumn($groupByKey) . ', MIN(' . self::db()->escapeColumn($field) . ') as _min';

        return $this->groupBy($groupByKey)->array($groupByKey, '_min');
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function orderBy(string $field, string $order = 'ASC'): self
    {
        // si lleva paréntesis, no escapamos
        if (strpos($field, '(') !== false && strpos($field, ')') !== false) {
            $this->orderBy[] = $field . ' ' . $order;
            return $this;
        }

        // si contiene espacios, no escapamos
        if (strpos($field, ' ') !== false) {
            $this->orderBy[] = $field . ' ' . $order;
            return $this;
        }

        // si el campo comienza por integer: hacemos el cast a integer
        if (0 === strpos($field, 'integer:')) {
            $field = self::db()->castInteger(substr($field, 8));
        }

        // si empieza por lower, hacemos el lower
        if (0 === strpos($field, 'lower:')) {
            $field = 'LOWER(' . self::db()->escapeColumn(substr($field, 6)) . ')';
        }

        $this->orderBy[] = self::db()->escapeColumn($field) . ' ' . $order;

        return $this;
    }

    public function orderMulti(array $fields): self
    {
        foreach ($fields as $field => $order) {
            $this->orderBy($field, $order);
        }

        return $this;
    }

    public function reorder(): self
    {
        $this->orderBy = [];

        return $this;
    }

    public function select(string $fields): self
    {
        $list = [];
        foreach (explode(',', $fields) as $field) {
            $list[] = self::db()->escapeColumn(trim($field));
        }

        $this->fields = implode(', ', $list);

        return $this;
    }

    public function selectRaw(string $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function sql(): string
    {
        $sql = 'SELECT ' . $this->fields . ' FROM ' . self::db()->escapeColumn($this->table);

        if (!empty($this->where)) {
            $sql .= Where::multiSqlLegacy($this->where);
        }

        if (!empty($this->groupBy)) {
            $sql .= ' GROUP BY ' . $this->groupBy;
        }

        if (!empty($this->having)) {
            $sql .= ' HAVING ' . $this->having;
        }

        if (!empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        return $sql;
    }

    public function sum(string $field, ?int $decimals = null): float
    {
        $this->fields = 'SUM(' . self::db()->escapeColumn($field) . ') as _sum';

        $row = $this->first();
        return is_null($decimals) ?
            (float)$row['_sum'] :
            round((float)$row['_sum'], $decimals);
    }

    public function sumArray(string $field, string $groupByKey): array
    {
        $this->fields = self::db()->escapeColumn($groupByKey) . ', SUM(' . self::db()->escapeColumn($field) . ') as _sum';

        return $this->groupBy($groupByKey)->array($groupByKey, '_sum');
    }

    public static function table(string $table): self
    {
        return new self($table);
    }

    public function update(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $fields = [];
        foreach ($data as $field => $value) {
            $fields[] = self::db()->escapeColumn($field) . ' = ' . self::db()->var2str($value);
        }

        $sql = 'UPDATE ' . self::db()->escapeColumn($this->table) . ' SET ' . implode(', ', $fields);

        if (!empty($this->where)) {
            $sql .= Where::multiSqlLegacy($this->where);
        }

        return self::db()->exec($sql);
    }

    /**
     * @param Where[] $where
     * @return $this
     * @throws Exception
     */
    public function where(array $where): self
    {
        // si el array está vacío, no hacemos nada
        if (empty($where)) {
            return $this;
        }

        foreach ($where as $value) {
            // si no es una instancia de Where o DataBaseWhere, lanzamos una excepción
            if (!($value instanceof Where) && !($value instanceof DataBaseWhere)) {
                throw new Exception('Invalid where clause ' . print_r($value, true));
            }

            $this->where[] = $value;
        }

        return $this;
    }

    public function whereBetween(string $field, $value1, $value2): self
    {
        $this->where[] = Where::between($field, $value1, $value2);

        return $this;
    }

    public function whereEq(string $field, $value): self
    {
        $this->where[] = Where::eq($field, $value);

        return $this;
    }

    public function whereGt(string $field, $value): self
    {
        $this->where[] = Where::gt($field, $value);

        return $this;
    }

    public function whereGte(string $field, $value): self
    {
        $this->where[] = Where::gte($field, $value);

        return $this;
    }

    public function whereIn(string $field, array $values): self
    {
        $this->where[] = Where::in($field, $values);

        return $this;
    }

    public function whereLike(string $field, string $value): self
    {
        $this->where[] = Where::like($field, $value);

        return $this;
    }

    public function whereLt(string $field, $value): self
    {
        $this->where[] = Where::lt($field, $value);

        return $this;
    }

    public function whereLte(string $field, $value): self
    {
        $this->where[] = Where::lte($field, $value);

        return $this;
    }

    public function whereNotEq(string $field, $value): self
    {
        $this->where[] = Where::notEq($field, $value);

        return $this;
    }

    public function whereNotIn(string $field, array $values): self
    {
        $this->where[] = Where::notIn($field, $values);

        return $this;
    }

    public function whereNotNull(string $field): self
    {
        $this->where[] = Where::isNotNull($field);

        return $this;
    }

    public function whereNull(string $field): self
    {
        $this->where[] = Where::isNull($field);

        return $this;
    }

    private static function db(): DataBase
    {
        if (null === self::$db) {
            self::$db = new DataBase();
            self::$db->connect();
        }

        return self::$db;
    }
}
