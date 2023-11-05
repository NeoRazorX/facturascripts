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

namespace FacturaScripts\Core\Template;

use Exception;
use FacturaScripts\Core\DbQuery;

abstract class Model
{
    const ID_COLUMN = 'id';
    const TABLE_NAME = '';

    /** @var array */
    private $_changed = [];

    public function __set($name, $value)
    {
        $this->_changed[$name] = $this->{$name};

        $this->{$name} = $value;
    }

    public static function all(array $where = [], array $orderBy = [], int $offset = 0, int $limit = 0): array
    {
        $query = static::table()
            ->where($where)
            ->orderMulti($orderBy)
            ->offset($offset)
            ->limit($limit);

        $list = [];
        foreach ($query->get() as $row) {
            $list[] = static::create($row);
        }

        return $list;
    }

    public static function create(array $data = []): self
    {
        $model = new static();

        return $model->fill($data);
    }

    public function delete(): bool
    {
        return self::table()
            ->whereEq(static::ID_COLUMN, $this->id())
            ->delete();
    }

    public static function deleteAll(array $where): bool
    {
        return self::table()
            ->where($where)
            ->delete();
    }

    public function exists(): bool
    {
        if (empty($this->id())) {
            return false;
        }

        return self::table()
                ->whereEq(static::ID_COLUMN, $this->id())
                ->count() > 0;
    }

    public function fill(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    public static function find($id): ?self
    {
        $data = self::table()
            ->whereEq(static::ID_COLUMN, $id)
            ->first();

        return $data ? static::create($data) : null;
    }

    public static function findOrCreate($id, array $data = []): self
    {
        $model = static::find($id);
        if ($model) {
            return $model;
        }

        $model = new static();

        return $model->fill($data);
    }

    public static function findOrFail($id): self
    {
        $model = static::find($id);
        if ($model) {
            return $model;
        }

        throw new Exception('Not found');
    }

    public static function findWhere(array $where): ?self
    {
        $data = self::table()
            ->where($where)
            ->first();

        return $data ? static::create($data) : null;
    }

    public static function findWhereOrCreate(array $where, array $data = []): self
    {
        $model = static::findWhere($where);
        if ($model) {
            return $model;
        }

        $model = new static();

        return $model->fill($data);
    }

    public static function findWhereOrFail(array $where): self
    {
        $model = static::findWhere($where);
        if ($model) {
            return $model;
        }

        throw new Exception('Not found');
    }

    public function getOriginal(string $field)
    {
        if (empty($field)) {
            $data = $this->toArray();

            foreach ($this->_changed as $key => $value) {
                $data[$key] = $value;
            }

            return $data;
        }

        return $this->_changed[$field] ?? $this->{$field};
    }

    public function id()
    {
        return $this->{static::ID_COLUMN};
    }

    public function isClean(string $field): bool
    {
        return empty($field) || !isset($this->_changed[$field]);
    }

    public function isDirty(string $field): bool
    {
        return empty($field) ? !empty($this->_changed) : isset($this->_changed[$field]);
    }

    public function load($id): bool
    {
        $data = self::table()
            ->whereEq(static::ID_COLUMN, $id)
            ->first();

        if ($data) {
            $this->fill($data);
            $this->_changed = [];

            return true;
        }

        return false;
    }

    public function loadOrCreate($id, array $data = []): bool
    {
        if ($this->load($id)) {
            return true;
        }

        return $this->fill($data)->save();
    }

    public function loadOrFail($id): bool
    {
        if ($this->load($id)) {
            return true;
        }

        throw new Exception('Not found');
    }

    public function loadWhere(array $where): bool
    {
        $data = self::table()
            ->where($where)
            ->first();

        if ($data) {
            $this->fill($data);
            $this->_changed = [];

            return true;
        }

        return false;
    }

    public function loadWhereOrCreate(array $where, array $data = []): bool
    {
        if ($this->loadWhere($where)) {
            return true;
        }

        return $this->fill($data)->save();
    }

    public function save(): bool
    {
        return $this->exists() ?
            $this->saveUpdate() :
            $this->saveInsert();
    }

    public function saveOrFail(): bool
    {
        if ($this->save()) {
            return true;
        }

        throw new Exception('Save failed');
    }

    public static function table(): DbQuery
    {
        return DbQuery::table(static::TABLE_NAME);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    protected function saveInsert(): bool
    {
        $id = self::table()->insertGetId($this->toArray());

        if ($id) {
            $this->{static::ID_COLUMN} = $id;
            $this->_changed = [];

            return true;
        }

        return false;
    }

    protected function saveUpdate(): bool
    {
        $updated = self::table()
            ->whereEq(static::ID_COLUMN, $this->id())
            ->update($this->toArray());

        if ($updated) {
            $this->_changed = [];

            return true;
        }

        return false;
    }
}
