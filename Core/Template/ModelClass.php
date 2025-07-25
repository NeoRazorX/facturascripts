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

use Exception;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\DbQuery;
use FacturaScripts\Core\DbUpdater;
use FacturaScripts\Core\Lib\Import\CSVImport;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Core\WorkQueue;

abstract class ModelClass
{
    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     * The model's original attributes.
     *
     * @var array
     */
    protected $original = [];

    abstract public static function addExtension($extension, int $priority = 100): void;

    abstract public static function all(array $where = [], array $order = [], int $offset = 0, int $limit = 0): array;

    abstract public static function count(array $where = []): int;

    abstract public static function create(array $data): ?static;

    abstract public static function deleteWhere(array $where): bool;

    abstract public static function find($code): ?static;

    abstract public static function findWhere(array $where, array $order = []): ?static;

    abstract public static function findOrCreate(array $where, array $data = []): ?static;

    abstract public function getModelFields(): array;

    abstract public function hasExtension($extension): bool;

    abstract protected function loadModelFields(): void;

    abstract public function modelClassName(): string;

    abstract public function pipe($name, ...$arguments);

    abstract public function pipeFalse($name, ...$arguments): bool;

    abstract public static function table(): DbQuery;

    abstract public static function tableName(): string;

    abstract public static function totalSum(string $field, array $where = []): float;

    abstract public static function updateOrCreate(array $where, array $data): ?static;

    public function __construct(array $data = [])
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
            self::$dataBase->connect();
        }

        if (empty(static::tableName())) {
            throw new Exception('The table name is not defined in the model ' . $this->modelClassName());
        }

        if (DbUpdater::isTableChecked(static::tableName())) {
            // none
        } elseif (false === DbUpdater::createOrUpdateTable(static::tableName(), [], $this->install())) {
            throw new Exception('Error creating or updating the table ' . static::tableName() . ' in model ' . $this->modelClassName());
        }

        $this->loadModelFields();

        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    public function changeId($new_id): bool
    {
        if (empty($new_id) || $new_id === $this->id()) {
            return false;
        }

        if (false === $this->pipeFalse('changePrimaryColumnValueBefore')) {
            return false;
        }

        $changed = static::table()
            ->whereEq($this->primaryColumn(), $this->id())
            ->update([$this->primaryColumn() => $new_id]);
        if (false === $changed) {
            return false;
        }

        // Update the attributes with the new id
        $this->{$this->primaryColumn()} = $new_id;

        $this->syncOriginal();
        $this->clearCache();

        return $this->pipeFalse('changePrimaryColumnValueAfter');
    }

    public function clear(): void
    {
        foreach (array_keys($this->getModelFields()) as $field_name) {
            $this->{$field_name} = null;
        }

        $this->pipeFalse('clear');
    }

    public function clearCache(): void
    {
        Cache::deleteMulti('model-' . $this->modelClassName() . '-');
        Cache::deleteMulti('join-model-');
        Cache::deleteMulti('table-' . static::tableName() . '-');
    }

    public function delete(): bool
    {
        if (null === $this->id()) {
            return true;
        }

        if (false === $this->pipeFalse('deleteBefore')) {
            return false;
        }

        $deleted = static::table()
            ->whereEq(static::primaryColumn(), $this->id())
            ->delete();
        if (false === $deleted) {
            return false;
        }

        $this->clearCache();

        WorkQueue::send(
            'Model.' . $this->modelClassName() . '.Delete',
            $this->id(),
            $this->toArray()
        );

        return $this->pipeFalse('delete');
    }

    public function exists(): bool
    {
        if (null === $this->id()) {
            return false;
        }

        return static::table()
                ->whereEq(static::primaryColumn(), $this->id())
                ->count() > 0;
    }

    public function get($code)
    {
        if (null === $code) {
            return false;
        }

        $data = static::table()
            ->whereEq(static::primaryColumn(), $code)
            ->first();

        return empty($data) ?
            false :
            new static($data);
    }

    public function getOriginal(?string $key = null)
    {
        if ($key === null) {
            return $this->original;
        }

        return $this->original[$key] ?? null;
    }

    public function id()
    {
        return $this->{static::primaryColumn()};
    }

    public function install(): string
    {
        return CSVImport::importTableSQL(static::tableName());
    }

    public function isDirty(?string $key = null): bool
    {
        if ($key === null) {
            return $this->attributes !== $this->original;
        }

        $current = $this->attributes[$key] ?? null;
        $original = $this->original[$key] ?? null;

        return $current !== $original;
    }

    public function load($code): bool
    {
        if (null === $code) {
            return false;
        }

        $data = static::table()
            ->whereEq(static::primaryColumn(), $code)
            ->first();
        if (empty($data)) {
            $this->clear();
            return false;
        }

        $this->loadFromData($data);
        return true;
    }

    public function loadFromData(array $data = [], array $exclude = []): void
    {
        $fields = $this->getModelFields();
        foreach ($data as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            } elseif (!isset($fields[$key])) {
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

        $this->syncOriginal();
    }

    public function loadWhere(array $where, array $order = []): bool
    {
        $data = static::table()
            ->where($where)
            ->orderMulti($order)
            ->first();

        if (empty($data)) {
            $this->clear();
            return false;
        }

        $this->loadFromData($data);
        return true;
    }

    public function newCode(string $field = '', array $where = [])
    {
        // if not field value take PK Field
        if (empty($field)) {
            $field = static::primaryColumn();
        }

        // get fields list
        $model_fields = $this->getModelFields();

        // Set Cast to Integer if field it's not
        if (false === in_array($model_fields[$field]['type'], ['integer', 'int', 'serial'])) {
            // Set Where to Integers values only
            $where[] = Where::regexp($field, '^-?[0-9]+$');
            $field = self::$dataBase->getEngine()->getSQL()->sql2Int($field);
        }

        // Search for new code value
        $sqlWhere = Where::multiSqlLegacy($where);
        $sql = 'SELECT MAX(' . $field . ') as cod FROM ' . static::tableName() . $sqlWhere . ';';
        $data = self::$dataBase->select($sql);
        return empty($data) ? 1 : 1 + (int)$data[0]['cod'];
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public function primaryColumnValue()
    {
        return $this->{static::primaryColumn()};
    }

    public function primaryDescription()
    {
        return $this->{$this->primaryDescriptionColumn()};
    }

    public function primaryDescriptionColumn(): string
    {
        $fields = $this->getModelFields();
        if (isset($fields['description'])) {
            return 'description';
        } elseif (isset($fields['name'])) {
            return 'name';
        }

        return static::primaryColumn();
    }

    public function reload(): bool
    {
        if (null === $this->id()) {
            return false;
        }

        if (false === $this->pipeFalse('reloadBefore')) {
            return false;
        }

        if (false === $this->load($this->id())) {
            return false;
        }

        return $this->pipeFalse('reload');
    }

    public function save(): bool
    {
        if (false === $this->pipeFalse('saveBefore')) {
            return false;
        }

        if (false === $this->test()) {
            return false;
        }

        $done = $this->exists() ? $this->saveUpdate() : $this->saveInsert();
        if (false === $done) {
            return false;
        }

        $this->syncOriginal();
        $this->clearCache();

        WorkQueue::send(
            'Model.' . $this->modelClassName() . '.Save',
            $this->id(),
            $this->toArray()
        );

        return $this->pipeFalse('save');
    }

    public function syncOriginal(): void
    {
        $this->original = $this->attributes;
    }

    public function test(): bool
    {
        if (false === $this->pipeFalse('testBefore')) {
            return false;
        }

        // comprobamos que los campos no nulos tengan algún valor asignado
        $fields = $this->getModelFields();
        if (empty($fields)) {
            return false;
        }
        $return = true;
        foreach ($fields as $key => $value) {
            if ($key == static::primaryColumn()) {
                $this->{$key} = empty($this->{$key}) ? null : $this->{$key};
            } elseif (null === $value['default'] && $value['is_nullable'] === 'NO' && $this->{$key} === null) {
                Tools::log()->warning('field-can-not-be-null', ['%fieldName%' => $key, '%tableName%' => static::tableName()]);
                $return = false;
            }
        }
        if (false === $return) {
            return false;
        }

        return $this->pipeFalse('test');
    }

    public function toArray(): array
    {
        $data = [];
        foreach (array_keys($this->getModelFields()) as $field_name) {
            $data[$field_name] = $this->{$field_name} ?? null;
        }

        $data = $this->pipe('toArray', $data) ?? $data;

        return $data;
    }

    public function update(array $values): bool
    {
        if (null === $this->id()) {
            return false;
        }

        if (false === $this->pipeFalse('updateBefore')) {
            return false;
        }

        $updated = static::table()
            ->whereEq(static::primaryColumn(), $this->id())
            ->update($values);
        if (false === $updated) {
            return false;
        }

        // Update the attributes with the new values
        foreach ($values as $key => $value) {
            $this->{$key} = $value;
        }

        $this->syncOriginal();
        $this->clearCache();

        WorkQueue::send(
            'Model.' . $this->modelClassName() . '.Update',
            $this->id(),
            $values
        );

        return $this->pipeFalse('update');
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        $return = $this->pipe('url', $type, $list);
        if ($return) {
            return $return;
        }

        $model = $this->modelClassName();
        $value = $this->id();

        switch ($type) {
            case 'edit':
                return is_null($value) ? 'Edit' . $model : 'Edit' . $model . '?code=' . rawurlencode($value);

            case 'list':
                return $list . $model;

            case 'new':
                return 'Edit' . $model;
        }

        // default
        return empty($value) ? $list . $model : 'Edit' . $model . '?code=' . rawurlencode($value);
    }

    protected function db(): DataBase
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
            self::$dataBase->connect();
        }

        return self::$dataBase;
    }

    private function getBoolValueForField(array $field, $value): ?bool
    {
        if ($value === null) {
            return $field['is_nullable'] === 'NO' ? false : null;
        } elseif (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower($value), ['true', 't', '1'], false);
    }

    private function getIntegerValueForField(array $field, $value): ?int
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        if ($field['name'] === static::primaryColumn()) {
            return null;
        }

        return $field['is_nullable'] === 'NO' ? 0 : null;
    }

    private function getFloatValueForField(array $field, $value): ?float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        return $field['is_nullable'] === 'NO' ? 0.0 : null;
    }

    protected function saveInsert(): bool
    {
        if (false === $this->pipeFalse('saveInsertBefore')) {
            return false;
        }

        $inserted = static::table()->insert($this->toArray());
        if (false === $inserted) {
            return false;
        }

        // Update the attributes with the new id
        if (empty($this->id())) {
            $this->{$this->primaryColumn()} = static::$dataBase->lastval();
        }

        WorkQueue::send(
            'Model.' . $this->modelClassName() . '.Insert',
            $this->id(),
            $this->toArray()
        );

        return $this->pipeFalse('saveInsert');
    }

    protected function saveUpdate(): bool
    {
        if (false === $this->pipeFalse('saveUpdateBefore')) {
            return false;
        }

        $updated = static::table()
            ->whereEq(static::primaryColumn(), $this->id())
            ->update($this->toArray());
        if (false === $updated) {
            return false;
        }

        WorkQueue::send(
            'Model.' . $this->modelClassName() . '.Update',
            $this->id(),
            $this->toArray()
        );

        return $this->pipeFalse('saveUpdate');
    }
}
