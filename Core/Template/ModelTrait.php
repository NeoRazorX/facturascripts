<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\DbQuery;

trait ModelTrait
{
    use ExtensionsTrait;

    /**
     * List of fields in the table.
     *
     * @var array
     */
    protected static $fields = [];

    abstract protected function db(): DataBase;

    abstract public static function primaryColumn(): string;

    abstract public static function tableName(): string;

    public static function all(array $where = [], array $order = [], int $offset = 0, int $limit = 0): array
    {
        $data = self::table()
            ->where($where)
            ->orderMulti($order)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $list = [];
        foreach ($data as $row) {
            $list[] = new static($row);
        }

        return $list;
    }

    public static function count(array $where = []): int
    {
        return self::table()
            ->where($where)
            ->count();
    }

    public static function create(array $data): ?static
    {
        $model = new static($data);
        return $model->save() ? $model : null;
    }

    public static function deleteWhere(array $where): bool
    {
        return self::table()
            ->where($where)
            ->delete();
    }

    public static function find($code): ?static
    {
        $data = self::table()
            ->whereEq(static::primaryColumn(), $code)
            ->first();

        return $data ? new static($data) : null;
    }

    public static function findWhere(array $where, array $order = []): ?static
    {
        $data = self::table()
            ->where($where)
            ->orderMulti($order)
            ->first();

        return $data ? new static($data) : null;
    }

    public static function findOrCreate(array $where, array $data = []): ?static
    {
        $row = self::table()
            ->where($where)
            ->first();
        if ($row) {
            return new static($row);
        }

        $data = array_merge($where, $data);
        $model = new static($data);
        return $model->save() ? $model : null;
    }

    /**
     * Returns the list of fields in the table.
     *
     * @return array
     */
    public function getModelFields(): array
    {
        return static::$fields;
    }

    /**
     * Returns the name of the class of the model.
     *
     * @return string
     */
    public function modelClassName(): string
    {
        $result = explode('\\', get_class($this));
        return end($result);
    }

    public static function table(): DbQuery
    {
        return DbQuery::table(static::tableName());
    }

    public static function totalSum(string $field, array $where = []): float
    {
        return self::table()
            ->where($where)
            ->sum($field);
    }

    public static function updateOrCreate(array $where, array $data): ?static
    {
        $row = self::table()
            ->where($where)
            ->first();
        if ($row) {
            $model = new static($row);
            $model->loadFromData($data);
            return $model->save() ? $model : null;
        }

        $data = array_merge($where, $data);
        $model = new static($data);
        return $model->save() ? $model : null;
    }

    protected function loadModelFields(): void
    {
        if (static::$fields) {
            return;
        }

        // read from the cache
        $key = 'model-fields-' . $this->modelClassName();
        static::$fields = Cache::get($key);
        if (is_array(static::$fields) && static::$fields) {
            return;
        }

        // table exists?
        if (false === $this->db()->tableExists(static::tableName())) {
            static::$fields = [];
            return;
        }

        // get from the database and store on the cache
        static::$fields = $this->db()->getColumns(static::tableName());
        Cache::set($key, static::$fields);
    }
}
