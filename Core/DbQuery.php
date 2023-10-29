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

    public function array(string $key, string $value): array
    {
        $result = [];
        foreach ($this->get() as $row) {
            $result[$row[$key]] = $row[$value];
        }

        return $result;
    }

    public function avg(string $field): float
    {
        $this->fields = 'AVG(' . self::db()->escapeColumn($field) . ')';

        return (float)$this->first();
    }

    public function count(): int
    {
        $this->fields = 'COUNT(*)';

        return (int)$this->first();
    }

    public function delete(): bool
    {
        $sql = 'DELETE FROM ' . self::db()->escapeColumn($this->table);

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . Where::multiSql($this->where);
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

    public function groupBy(string $groupBy): self
    {
        $this->groupBy = $groupBy;

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
        foreach ($data as $field => $value) {
            $fields[] = self::db()->escapeColumn($field);
            $values[] = self::db()->var2str($value);
        }

        $sql = 'INSERT INTO ' . self::db()->escapeColumn($this->table)
            . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
        return self::db()->exec($sql);
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function min(string $field): float
    {
        $this->fields = 'MIN(' . self::db()->escapeColumn($field) . ')';

        return (float)$this->first();
    }

    public function max(string $field): float
    {
        $this->fields = 'MAX(' . self::db()->escapeColumn($field) . ')';

        return (float)$this->first();
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function orderBy(string $field, string $order = 'ASC'): self
    {
        // si el campo comienza por integer: hacemos el cast a integer
        if (0 === strpos($field, 'integer:')) {
            $field = self::db()->castInteger(substr($field, 8));
        }

        $this->orderBy[] = self::db()->escapeColumn($field) . ' ' . $order;

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

    public function sql(): string
    {
        $sql = 'SELECT ' . $this->fields . ' FROM ' . self::db()->escapeColumn($this->table);

        if (!empty($this->where)) {
            $sql .= ' WHERE ' . Where::multiSql($this->where);
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

    public function sum(string $field): float
    {
        $this->fields = 'SUM(' . self::db()->escapeColumn($field) . ')';

        return (float)$this->first();
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

        foreach ($where as $key => $value) {
            // si no es una instancia de Where, lanzamos una excepción
            if (!($value instanceof Where)) {
                throw new Exception('Invalid where clause ' . print_r($value, true));
            }

            $this->where[] = $value;
        }

        return $this;
    }

    public function whereEq(string $field, $value): self
    {
        $this->where[] = Where::eq($field, $value);

        return $this;
    }

    private static function db(): DataBase
    {
        if (null === self::$db) {
            self::$db = new DataBase();
        }

        return self::$db;
    }
}
