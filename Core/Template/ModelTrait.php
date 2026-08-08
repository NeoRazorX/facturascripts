<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DbUpdater;

/**
 * Aporta a los modelos la implementación de los métodos estáticos de consulta
 * (all, find, count, create...), el acceso al constructor de consultas de su
 * tabla y la carga de la definición de sus campos desde la base de datos.
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
trait ModelTrait
{
    use ExtensionsTrait;

    /**
     * Lista de campos de la tabla del modelo.
     *
     * @var array
     */
    protected static $fields = [];

    abstract protected static function db(): DataBase;

    abstract public static function primaryColumn(): string;

    abstract public static function tableName(): string;

    /**
     * Devuelve todos los registros que cumplen las condiciones.
     *
     * @param array $where
     * @param array $order
     * @param int $offset
     * @param int $limit
     * @return static[]
     */
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

    /**
     * Devuelve todos los registros donde el campo es igual al valor.
     *
     * @param string $field
     * @param mixed $value
     * @param array $order
     * @return static[]
     */
    public static function allWhereEq(string $field, $value, array $order = []): array
    {
        $data = self::table()
            ->whereEq($field, $value)
            ->orderMulti($order)
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

    /**
     * Cuenta los registros donde el campo es igual al valor.
     *
     * @param string $field
     * @param mixed $value
     */
    public static function countWhereEq(string $field, $value): int
    {
        return self::table()
            ->whereEq($field, $value)
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

    public static function findWhereEq(string $field, $value): ?static
    {
        $data = self::table()
            ->whereEq($field, $value)
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
     * Devuelve la lista de campos de la tabla.
     *
     * @return array
     */
    public function getModelFields(): array
    {
        if (empty(static::$fields)) {
            $this->loadModelFields();
        }

        return static::$fields;
    }

    /**
     * Devuelve el nombre de la clase del modelo sin el espacio de nombres.
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
        // comprobamos si la tabla existe
        if (!DbUpdater::isTableChecked(static::tableName())) {
            new static();
        }

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

        // leemos de la caché
        $key = 'model-fields-' . $this->modelClassName();
        static::$fields = Cache::get($key);
        if (is_array(static::$fields) && static::$fields) {
            return;
        }

        // ¿existe la tabla?
        if (false === $this->db()->tableExists(static::tableName())) {
            static::$fields = [];
            return;
        }

        // obtenemos de la base de datos y guardamos en la caché
        static::$fields = $this->db()->getColumns(static::tableName());
        Cache::set($key, static::$fields);
    }
}
