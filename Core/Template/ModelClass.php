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
use FacturaScripts\Core\Internal\CacheWithMemory;
use FacturaScripts\Core\Lib\Import\CSVImport;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Core\WorkQueue;
use JetBrains\PhpStorm\Deprecated;

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

        if (!DbUpdater::isTableChecked(static::tableName())) {
            $sql_insert = self::$dataBase->tableExists(static::tableName()) ? '' : $this->install();
            if (!DbUpdater::createOrUpdateTable(static::tableName(), [], $sql_insert)) {
                throw new Exception(DbUpdater::getLastError());
            }
            $this->clearCache();
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

    /**
     * @param $new_id
     * @return bool
     * @deprecated replace with changeId()
     */
    public function changePrimaryColumnValue($new_id): bool
    {
        return $this->changeId($new_id);
    }

    public function clear(): void
    {
        foreach ($this->getModelFields() as $key => $field) {
            // si es la clave primaria, asignamos null
            if ($key == static::primaryColumn()) {
                $this->{$key} = null;
                continue;
            }

            // si no tiene valor por defecto, asignamos null
            if ($field['default'] === null) {
                $this->{$key} = null;
                continue;
            }

            // convertimos el valor por defecto al tipo adecuado
            $type = strpos($field['type'], '(') === false ?
                $field['type'] :
                substr($field['type'], 0, strpos($field['type'], '('));
            $this->{$key} = match ($type) {
                'tinyint', 'boolean' => in_array($field['default'], ['true', 't', '1'], false),
                'integer', 'int' => intval($field['default']),
                'decimal', 'double', 'double precision', 'float' => floatval($field['default']),
                'date' => Tools::date(), // asumimos que el campo fecha nunca tendrá valor por defecto
                'datetime', 'timestamp' => Tools::dateTime(), // asumimos que el campo datetime nunca tendrá valor por defecto
                default => $field['default'],
            };
        }

        $this->pipeFalse('clear');
    }

    public function clearCache(): void
    {
        CacheWithMemory::deleteMulti('model-' . $this->modelClassName() . '-');
        CacheWithMemory::deleteMulti('join-model-');
        CacheWithMemory::deleteMulti('table-' . static::tableName() . '-');
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

        $this->onDelete();
        $this->syncOriginal();
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

    /**
     * @deprecated Use find() instead
     */
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

    public function getDirty(): array
    {
        $dirty = [];
        foreach (array_keys($this->getModelFields()) as $key) {
            if ($this->isDirty($key)) {
                $dirty[$key] = $this->{$key};
            }
        }
        return $dirty;
    }

    public function getOriginal(?string $key = null)
    {
        if ($key === null) {
            return $this->original;
        }

        return $this->original[$key] ?? null;
    }

    /**
     * @param string $field
     * @return bool
     * @deprecated replace with isDirty()
     */
    public function hasChanged(string $field): bool
    {
        return $this->isDirty($field);
    }

    public function hasColumn(string $columnName): bool
    {
        $fields = $this->getModelFields();
        return isset($fields[$columnName]);
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
            $current = [];
            foreach (array_keys($this->getModelFields()) as $key) {
                $current[$key] = $this->{$key};
            }
            return $current !== $this->original;
        }

        $current = $this->{$key} ?? null;
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

    /**
     * Carga un registro del modelo utilizando un código y opcionalmente condiciones adicionales.
     *
     * IMPORTANTE: Este método está deprecado. Se recomienda usar las alternativas siguientes:
     * - Si solo se proporciona $code: usar directamente load($code)
     * - Si se proporciona $code junto con $where o $order: usar loadWhere() con las condiciones apropiadas
     *
     * Este método actúa como wrapper que redirige a load() cuando solo se proporciona el código,
     * o a loadWhere() cuando se incluyen condiciones WHERE u ordenamiento adicionales.
     *
     * @param mixed $code Código o identificador del registro a cargar. Se usa únicamente cuando
     *                     no se proporcionan condiciones WHERE adicionales.
     * @param array $where Array de instancias de Where o DatabaseWhere que definen condiciones
     *                     de filtrado adicionales. Si se proporciona, el método delega a loadWhere().
     *                     Por defecto es un array vacío.
     * @param array $order Array asociativo que define el ordenamiento de los resultados.
     *                     Las claves son nombres de columnas y los valores la dirección del ordenamiento.
     *                     Por defecto es un array vacío.
     *
     * @return bool Retorna true si se encontró y cargó un registro exitosamente.
     *              Retorna false si no se encontró ningún registro.
     * @deprecated Usar load() cuando solo se necesita cargar por código, o loadWhere() cuando
     *             se requieren condiciones WHERE u ordenamiento adicionales.
     *
     */
    public function loadFromCode($code, array $where = [], array $order = []): bool
    {
        if (!empty($where)) {
            return $this->loadWhere($where, $order);
        }

        return $this->load($code);
    }

    public function loadFromData(array $data = [], array $exclude = [], bool $sync = true): void
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
            $type = !str_contains($field['type'], '(') ?
                $field['type'] :
                substr($field['type'], 0, strpos($field['type'], '('));

            $this->{$key} = match ($type) {
                'tinyint', 'boolean' => $this->getBoolValueForField($field, $value),
                'integer', 'int' => $this->getIntegerValueForField($field, $value),
                'decimal', 'double', 'double precision', 'float' => $this->getFloatValueForField($field, $value),
                'date' => empty($value) ? null : Tools::date($value),
                'datetime', 'timestamp' => empty($value) ? null : Tools::dateTime($value),
                default => ($value === null && $field['is_nullable'] === 'NO') ? '' : $value,
            };
        }

        if ($sync) {
            $this->syncOriginal();
        }
    }

    /**
     * Carga el primer registro que coincida con las condiciones especificadas.
     *
     * Este método consulta la tabla asociada al modelo aplicando las condiciones WHERE proporcionadas
     * y el ordenamiento especificado. Si encuentra un registro, carga sus datos en la instancia actual
     * del modelo. Si no encuentra ningún registro, limpia la instancia y retorna false.
     *
     * @param array $where Array de instancias de Where o DatabaseWhere que definen las condiciones
     *                     de filtrado para la consulta. Cada elemento representa una condición que
     *                     debe cumplir el registro a cargar.
     * @param array $order Array asociativo que define el ordenamiento de los resultados.
     *                     Las claves son nombres de columnas y los valores indican la dirección
     *                     del ordenamiento (ej: ['id' => 'DESC', 'nombre' => 'ASC']).
     *                     Por defecto es un array vacío (sin ordenamiento específico).
     *
     * @return bool Retorna true si se encontró y cargó un registro exitosamente.
     *              Retorna false si no se encontró ningún registro que cumpla las condiciones.
     */
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

    public function loadWhereEq(string $field, $value): bool
    {
        return $this->loadWhere([Where::eq($field, $value)]);
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

    /**
     * @deprecated Use id() instead
     */
    #[Deprecated(
        reason: 'Use id() instead',
        replacement: '%class%->id()',
    )]
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
        } elseif (isset($fields['descripcion'])) {
            return 'descripcion';
        } elseif (isset($fields['name'])) {
            return 'name';
        } elseif (isset($fields['nombre'])) {
            return 'nombre';
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
        $this->original = [];

        if (null === $this->id()) {
            // If the model has no ID, we do not sync original values
            return;
        }

        foreach (array_keys($this->getModelFields()) as $key) {
            $this->original[$key] = $this->{$key};
        }
    }

    public function test(): bool
    {
        if (false === $this->pipeFalse('testBefore')) {
            return false;
        }

        // comprobamos que los campos estén definidos
        $fields = $this->getModelFields();
        if (empty($fields)) {
            throw new Exception('The model fields are not defined in the model ' . $this->modelClassName());
        }

        // comprobamos que los campos no nulos tengan algún valor asignado
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

    public function toArray(bool $dynamic_attributes = false): array
    {
        $data = [];
        foreach (array_keys($this->getModelFields()) as $field_name) {
            $data[$field_name] = $this->{$field_name} ?? null;
        }

        if ($dynamic_attributes) {
            foreach ($this->attributes as $key => $value) {
                if (!array_key_exists($key, $data)) {
                    $data[$key] = $value;
                }
            }
        }

        return $this->pipe('toArray', $data, $dynamic_attributes) ?? $data;
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

    /**
     * Define a one-to-one relationship.
     *
     * @param string $modelName
     * @param string $foreignKey
     * @return object|null
     */
    protected function belongsTo(string $modelName, string $foreignKey): ?object
    {
        if (empty($this->{$foreignKey})) {
            return null;
        }

        // Extract class name if full class path is provided
        if (strpos($modelName, '\\') !== false) {
            $parts = explode('\\', $modelName);
            $modelName = end($parts);
        }

        // Cache key for this relationship
        $key = $this->{$foreignKey};
        $cacheKey = 'model-' . $modelName . '-' . $key;

        return Cache::withMemory()->remember($cacheKey, function () use ($modelName, $key) {
            $modelClass = '\\FacturaScripts\\Dinamic\\Model\\' . $modelName;
            $model = new $modelClass();
            return $model->load($key) ? $model : null;
        });
    }

    protected static function db(): DataBase
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

    /**
     * Define a one-to-many relationship.
     *
     * @param string $modelName
     * @param string $foreignKey
     * @param array $where
     * @param array $order
     * @return array
     */
    protected function hasMany(string $modelName, string $foreignKey, array $where = [], array $order = []): array
    {
        // Extract class name if full class path is provided
        if (strpos($modelName, '\\') !== false) {
            $parts = explode('\\', $modelName);
            $modelName = end($parts);
        }

        $modelClass = '\\FacturaScripts\\Dinamic\\Model\\' . $modelName;
        $where[] = Where::eq($foreignKey, $this->id());
        return $modelClass::all($where, $order);
    }

    /**
     * This method is called before save (update) when some field has changed.
     *
     * @param string $field
     *
     * @return bool
     */
    protected function onChange(string $field): bool
    {
        if (false === $this->pipe('onChange', $field)) {
            return false;
        }

        return true;
    }

    /**
     * This method is called after a record is removed from the database.
     */
    protected function onDelete(): void
    {
        $this->pipe('onDelete');
    }

    /**
     * This method is called after a new record is saved on the database (saveInsert).
     */
    protected function onInsert(): void
    {
        $this->pipe('onInsert');
    }

    /**
     * This method is called after a record is updated on the database (saveUpdate).
     */
    protected function onUpdate(): void
    {
        $this->pipe('onUpdate');
    }

    protected function saveInsert(): bool
    {
        if (false === $this->pipeFalse('saveInsertBefore')) {
            return false;
        }

        $data = $this->toArray();
        // Remove primary key if it is not set, to allow the database to generate it
        if (null === $this->id()) {
            unset($data[static::primaryColumn()]);
        }

        $inserted = static::table()->insert($data);
        if (false === $inserted) {
            return false;
        }

        // Update the attributes with the new id
        if (null === $this->id()) {
            $this->{$this->primaryColumn()} = static::$dataBase->lastval();
        } else {
            static::$dataBase->updateSequence(static::tableName(), $this->getModelFields());
        }

        $this->onInsert();

        WorkQueue::send(
            'Model.' . $this->modelClassName() . '.Insert',
            $this->id(),
            $this->toArray()
        );

        return $this->pipeFalse('saveInsert');
    }

    protected function saveUpdate(): bool
    {
        foreach (array_keys($this->original) as $field) {
            if ($this->isDirty($field) && !$this->onChange($field)) {
                return false;
            }
        }

        if (false === $this->pipeFalse('saveUpdateBefore')) {
            return false;
        }

        $updated = static::table()
            ->whereEq(static::primaryColumn(), $this->id())
            ->update($this->toArray());
        if (false === $updated) {
            return false;
        }

        $this->onUpdate();

        WorkQueue::send(
            'Model.' . $this->modelClassName() . '.Update',
            $this->id(),
            $this->toArray()
        );

        return $this->pipeFalse('saveUpdate');
    }
}
