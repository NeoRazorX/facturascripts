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
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\DbQuery;
use FacturaScripts\Core\Tools;

abstract class Model
{
    const ID_COLUMN = 'id';
    const TABLE_NAME = '';

    /** @var array */
    private $m_changes = [];

    /** @var array */
    private static $m_fields = [];

    /** @var array */
    private $m_values = [];

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        return $this->m_values[$name] ?? null;
    }

    public function __set($name, $value)
    {
        $this->m_changes[$name] = $this->{$name};

        if (property_exists($this, $name)) {
            $this->{$name} = $value;
        } else {
            $this->m_values[$name] = $value;
        }
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
        if (false === $this->onDelete()) {
            return false;
        }

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

    public function fill(array $data, array $exclude = []): self
    {
        $fields = $this->getModelFields();

        foreach ($data as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }

            if (!isset($fields[$key])) {
                $this->{$key} = $value;
                continue;
            }

            // We check if it is a varchar (with established length) or another type of data
            $field = $fields[$key];
            $type = strpos($field['type'], '(') === false ?
                $field['type'] :
                substr($field['type'], 0, strpos($field['type'], '('));

            switch ($type) {
                case 'tinyint':
                case 'boolean':
                    $this->{$key} = $this->getBoolValueForField($field, $value);
                    break;

                case 'integer':
                case 'int':
                    $this->{$key} = $this->getIntegerValueForField($field, $value);
                    break;

                case 'decimal':
                case 'double':
                case 'double precision':
                case 'float':
                    $this->{$key} = $this->getFloatValueForField($field, $value);
                    break;

                case 'date':
                    $this->{$key} = empty($value) ? null : Tools::date($value);
                    break;

                case 'datetime':
                case 'timestamp':
                    $this->{$key} = empty($value) ? null : Tools::dateTime($value);
                    break;

                default:
                    $this->{$key} = ($value === null && $field['is_nullable'] === 'NO') ? '' : $value;
            }
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

    public function getModelFields(): array
    {
        if (isset(self::$m_fields[static::TABLE_NAME])) {
            return self::$m_fields[static::TABLE_NAME];
        }

        self::$m_fields[static::TABLE_NAME] = Cache::remember('_table_' . static::TABLE_NAME, function () {
            $db = new DataBase();
            return $db->getColumns(static::TABLE_NAME);
        });

        return self::$m_fields[static::TABLE_NAME];
    }

    public function getOriginal(string $field = '')
    {
        if (empty($field)) {
            $data = $this->toArray();

            foreach ($this->m_changes as $key => $value) {
                $data[$key] = $value;
            }

            return $data;
        }

        return $this->m_changes[$field] ?? $this->{$field};
    }

    public function id()
    {
        return $this->{static::ID_COLUMN};
    }

    public function isClean(string $field = ''): bool
    {
        return empty($field) || !isset($this->m_changes[$field]);
    }

    public function isDirty(string $field = ''): bool
    {
        return empty($field) ? !empty($this->m_changes) : isset($this->m_changes[$field]);
    }

    public function load($id): bool
    {
        $data = self::table()
            ->whereEq(static::ID_COLUMN, $id)
            ->first();

        if ($data) {
            $this->fill($data);
            $this->m_changes = [];

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
            $this->m_changes = [];

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

    public function reload(): bool
    {
        return $this->load($this->id());
    }

    public function save(): bool
    {
        return $this->exists() ?
            $this->onUpdate() && $this->saveUpdate() :
            $this->onInsert() && $this->saveInsert();
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
        $data = get_object_vars($this);

        // quitamos los campos m_changes, m_fields y m_values
        unset($data['m_changes'], $data['m_fields'], $data['m_values']);

        return $data;
    }

    private function getBoolValueForField(array $field, $value): ?bool
    {
        if ($value === null) {
            return $field['is_nullable'] === 'NO' ? false : null;
        }

        return is_bool($value) ?
            $value :
            in_array(strtolower($value), ['true', 't', '1'], false);
    }

    private function getFloatValueForField($field, $value): ?float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        return $field['is_nullable'] === 'NO' ? 0.0 : null;
    }

    private function getIntegerValueForField($field, $value): ?int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        if ($field['name'] === static::ID_COLUMN) {
            return null;
        }

        return $field['is_nullable'] === 'NO' ? 0 : null;
    }

    protected function onDelete(): bool
    {
        return true;
    }

    protected function onInsert(): bool
    {
        return true;
    }

    protected function onUpdate(): bool
    {
        return true;
    }

    protected function saveInsert(): bool
    {
        $id = self::table()->insertGetId($this->toArray());

        if ($id) {
            $this->{static::ID_COLUMN} = $id;
            $this->m_changes = [];

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
            $this->m_changes = [];

            return true;
        }

        return false;
    }
}
