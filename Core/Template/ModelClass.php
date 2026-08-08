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
     * Campos a ocultar en la API, añadidos por los plugins, indexados por tabla.
     *
     * @var array
     */
    private static $api_fields_to_hide = [];

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

    /** Añade una extensión al modelo con la prioridad indicada. */
    abstract public static function addExtension($extension, int $priority = 100): void;

    /** Devuelve los registros que cumplen las condiciones especificadas. */
    abstract public static function all(array $where = [], array $order = [], int $offset = 0, int $limit = 0): array;

    /** Devuelve los registros cuyo campo coincide con el valor indicado. */
    abstract public static function allWhereEq(string $field, $value, array $order = []): array;

    /** Cuenta los registros que cumplen las condiciones especificadas. */
    abstract public static function count(array $where = []): int;

    /** Cuenta los registros cuyo campo coincide con el valor indicado. */
    abstract public static function countWhereEq(string $field, $value): int;

    /** Crea y guarda un nuevo registro con los datos proporcionados. */
    abstract public static function create(array $data): ?static;

    /** Elimina los registros que cumplen las condiciones especificadas. */
    abstract public static function deleteWhere(array $where): bool;

    /** Busca un registro mediante su clave primaria. */
    abstract public static function find($code): ?static;

    /** Busca el primer registro que cumple las condiciones especificadas. */
    abstract public static function findWhere(array $where, array $order = []): ?static;

    /** Busca un registro o lo crea cuando no existe. */
    abstract public static function findOrCreate(array $where, array $data = []): ?static;

    /** Devuelve la definición de los campos del modelo. */
    abstract public function getModelFields(): array;

    /** Comprueba si el modelo tiene registrada la extensión indicada. */
    abstract public function hasExtension($extension): bool;

    /** Carga la definición de los campos del modelo. */
    abstract protected function loadModelFields(): void;

    /** Devuelve el nombre de la clase del modelo sin el espacio de nombres. */
    abstract public function modelClassName(): string;

    /** Ejecuta las extensiones asociadas al punto indicado y devuelve su resultado. */
    abstract public function pipe($name, ...$arguments);

    /** Ejecuta las extensiones y devuelve false si alguna cancela la operación. */
    abstract public function pipeFalse($name, ...$arguments): bool;

    /** Devuelve el constructor de consultas asociado a la tabla del modelo. */
    abstract public static function table(): DbQuery;

    /** Devuelve el nombre de la tabla asociada al modelo. */
    abstract public static function tableName(): string;

    /** Suma los valores de un campo para los registros que cumplen las condiciones. */
    abstract public static function totalSum(string $field, array $where = []): float;

    /** Actualiza el registro coincidente o crea uno nuevo. */
    abstract public static function updateOrCreate(array $where, array $data): ?static;

    /**
     * Inicializa el modelo, comprueba su tabla y carga los datos proporcionados.
     *
     * @param array $data
     */
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
                if (Tools::config('debug')) {
                    throw new Exception(DbUpdater::getLastError());
                }
                Tools::log()->critical(DbUpdater::getLastError());
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

    /** Devuelve el valor de un atributo dinámico. */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /** Comprueba si existe un atributo dinámico con valor no nulo. */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /** Asigna el valor de un atributo dinámico. */
    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /** Elimina un atributo dinámico. */
    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Cambia el valor de la clave primaria del registro.
     *
     * @param mixed $new_id
     * @return bool
     */
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

        // sync original
        $this->original[$this->primaryColumn()] = $this->id();
        $this->clearCache();

        return $this->pipeFalse('changePrimaryColumnValueAfter');
    }

    /**
     * Cambia el valor de la clave primaria del registro.
     *
     * @param mixed $new_id
     * @return bool
     * @deprecated replace with changeId()
     */
    public function changePrimaryColumnValue($new_id): bool
    {
        return $this->changeId($new_id);
    }

    /** Restablece los campos del modelo a sus valores predeterminados. */
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

    /** Elimina de la caché los datos relacionados con el modelo y su tabla. */
    public function clearCache(): void
    {
        CacheWithMemory::deleteMulti('model-' . $this->modelClassName() . '-');
        CacheWithMemory::deleteMulti('join-model-', '-' . static::tableName() . '-');
        CacheWithMemory::deleteMulti('table-' . static::tableName() . '-');
    }

    /**
     * Elimina el registro actual de la base de datos.
     *
     * @return bool
     */
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

    /**
     * Comprueba si el registro actual existe en la base de datos.
     *
     * @return bool
     */
    public function exists(): bool
    {
        if (null === $this->id() || '' === $this->id()) {
            return false;
        }

        return static::table()
                ->whereEq(static::primaryColumn(), $this->id())
                ->count() > 0;
    }

    /**
     * Busca un registro mediante su clave primaria y devuelve una nueva instancia.
     *
     * @param mixed $code
     * @return static|false
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

    /**
     * Añade un campo a la lista de campos que no deben exponerse en la API.
     * Solo permite añadir: los campos ocultos por el core no se pueden quitar.
     *
     * @param string $field
     */
    public static function addApiFieldToHide(string $field): void
    {
        if (false === in_array($field, self::$api_fields_to_hide[static::tableName()] ?? [], true)) {
            self::$api_fields_to_hide[static::tableName()][] = $field;
        }
    }

    /**
     * Devuelve los nombres de campos que no deben exponerse en la API
     * (ni en GET, ni en el schema). Los modelos con datos sensibles
     * deben sobrescribir este método fusionando con parent::getApiFieldsToHide().
     *
     * @return string[]
     */
    public function getApiFieldsToHide(): array
    {
        return self::$api_fields_to_hide[static::tableName()] ?? [];
    }

    /**
     * Devuelve un array asociativo con los campos modificados y sus valores actuales.
     *
     * @return array
     */
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

    /**
     * Devuelve el valor original de un campo, si no se indica campo devuelve todo el array original
     *
     * @param string|null $key
     * @return mixed|null Devuelve el valor original del campo o null si no existe
     */
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

    /**
     * Comprueba si el modelo contiene la columna indicada.
     *
     * @param string $columnName
     * @return bool
     */
    public function hasColumn(string $columnName): bool
    {
        $fields = $this->getModelFields();
        return isset($fields[$columnName]);
    }

    /**
     * Devuelve el valor de la clave primaria del modelo.
     *
     * @return mixed
     */
    public function id()
    {
        return $this->{static::primaryColumn()};
    }

    /**
     * Devuelve el SQL inicial necesario para instalar los datos del modelo.
     *
     * @return string
     */
    public function install(): string
    {
        return CSVImport::importTableSQL(static::tableName());
    }

    /**
     * Comprueba si los campos del modelo han sido modificados.
     * Devuelve true si hay cambios, false en caso contrario.
     *
     * Si introduces un parámetro key, comprueba solo ese campo
     *
     * @param string|null $key
     * @return bool true si ha sido modificado, false en caso contrario
     */
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

    /**
     * Carga un modelo dada su primary key
     *
     * @param mixed $code Código o identificador del registro a cargar
     *
     * @return bool Devuelve true si se encontró y cargó un registro exitosamente.
     */
    public function load($code): bool
    {
        if (null === $code || '' === $code) {
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

    /**
     * Carga los campos y atributos del modelo desde un array.
     *
     * @param array $data
     * @param array $exclude Campos que no se deben cargar.
     * @param bool $sync Indica si se deben sincronizar los valores originales.
     */
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

    /**
     * Carga el primer registro cuyo campo coincide con el valor indicado.
     *
     * @param string $field
     * @param mixed $value
     * @return bool
     */
    public function loadWhereEq(string $field, $value): bool
    {
        return $this->loadWhere([Where::eq($field, $value)]);
    }

    /**
     * Devuelve la longitud máxima de un campo de texto, o 0 si no tiene límite o no es un campo de texto.
     *
     * @param string $field Nombre del campo
     * @return int Longitud máxima del campo
     */
    public function maxLength(string $field): int
    {
        $fields = $this->getModelFields();
        if (!isset($fields[$field])) {
            return 0;
        }

        return preg_match('/^(?:varchar|character varying|char|character)\((\d+)\)/i', $fields[$field]['type'], $matches) ?
            (int)$matches[1] :
            0;
    }

    /**
     * Calcula el siguiente código numérico disponible para un campo.
     *
     * @param string $field
     * @param array $where
     * @return int
     */
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

    /**
     * Devuelve el nombre de la columna que actúa como clave primaria.
     *
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * Devuelve el valor de la clave primaria del modelo.
     *
     * @return mixed
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

    /**
     * Devuelve la descripción principal del registro.
     *
     * @return mixed
     */
    public function primaryDescription()
    {
        return $this->{$this->primaryDescriptionColumn()};
    }

    /**
     * Devuelve el nombre de la columna utilizada como descripción principal.
     *
     * @return string
     */
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

    /**
     * Vuelve a cargar desde la base de datos el registro actual.
     *
     * @return bool
     */
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

    /**
     * Guarda el modelo en la base de datos después de ejecutar las comprobaciones `$this->test()`
     *
     * @return bool Devuelve true si se ha guardado correctamente, false en caso contrario
     */
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

    /** Sincroniza los valores actuales con la copia original del modelo. */
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

    /**
     * Realiza pruebas de validación sobre los campos del modelo antes de guardarlo.
     *
     * @return bool Devuelve true si todas las pruebas pasan, false en caso contrario
     */
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

            // comprobamos que los campos de texto no superen la longitud máxima
            $max_length = $this->maxLength($key);
            if ($max_length > 0 && is_string($this->{$key}) && mb_strlen($this->{$key}) > $max_length) {
                Tools::log()->warning('field-value-too-long', [
                    '%fieldName%' => $key,
                    '%tableName%' => static::tableName(),
                    '%length%' => $max_length
                ]);
                $return = false;
            }
        }
        if (false === $return) {
            return false;
        }

        return $this->pipeFalse('test');
    }

    /**
     * Devuelve un array con los campos y valores del modelo.
     *
     * @param bool $dynamic_attributes Si es true, añade también los atributos dinámicos al array resultante
     * @return array Array asociativo con los campos y valores del modelo
     */
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

    /**
     * Actualiza el modelo con los valores proporcionados en el array
     *
     * @param array $values Array asociativo con los campos y valores a actualizar
     * @return bool Devuelve true si la actualización se ha realizado correctamente, false en caso
     */
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

    /**
     * Genera una URL para el modelo según el tipo especificado.
     *
     * @param string $type Tipo de URL a generar. Puede ser 'auto', 'edit', 'list' o 'new'.
     * @param string $list Nombre de la lista a utilizar en la URL cuando el tipo es 'list' o 'auto'.
     * @return string URL generada para el modelo
     */
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
     * Define una relación uno a uno.
     * 
     * Hace uso de la propiedad del modelo (que le indiques en el parámetro $foreignKey) y devuelve el objeto
     * cargado de la relación definida.
     * 
     * Ejemplo:
     *  - Asigno a "Empresa" $this->idlogo
     *  - Creo una función que devuelva un belongsTo('AttachedFile', 'idfile') en el Modelo
     *  - si se llama a esa función (por ejemplo) $miempresa->getLogo(), se recibirá un attachedFile con el logo en $this->idlogo o null
     *
     * @param string $modelName el nombre o path (model::class) del modelo que se va a usar
     * @param string $foreignKey el nombre de la columna con la primary key
     * @return object|null el objeto si se ha encontrado o null
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

    /**
     * Devuelve la instancia de la base de datos actual.
     *
     * @return DataBase
     */
    protected static function db(): DataBase
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
            self::$dataBase->connect();
        }

        return self::$dataBase;
    }

    /**
     * Convierte un valor al tipo booleano definido por el campo.
     *
     * @param array $field
     * @param mixed $value
     * @return bool|null
     */
    private function getBoolValueForField(array $field, $value): ?bool
    {
        if ($value === null) {
            return $field['is_nullable'] === 'NO' ? false : null;
        } elseif (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower($value), ['true', 't', '1'], false);
    }

    /**
     * Convierte un valor al tipo entero definido por el campo.
     *
     * @param array $field
     * @param mixed $value
     * @return int|null
     */
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

    /**
     * Convierte un valor al tipo decimal definido por el campo.
     *
     * @param array $field
     * @param mixed $value
     * @return float|null
     */
    private function getFloatValueForField(array $field, $value): ?float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        return $field['is_nullable'] === 'NO' ? 0.0 : null;
    }

    /**
     * Define una relación uno a muchos.
     *
     * @param string $modelName
     * @param string $foreignKey
     * @param array $where
     * @param array $order
     * @param bool $cached usar solo en relaciones de solo lectura y de listas pequeñas y cerradas
     * @return array
     */
    protected function hasMany(string $modelName, string $foreignKey, array $where = [], array $order = [], bool $cached = false): array
    {
        // Extract class name if full class path is provided
        if (strpos($modelName, '\\') !== false) {
            $parts = explode('\\', $modelName);
            $modelName = end($parts);
        }

        $modelClass = '\\FacturaScripts\\Dinamic\\Model\\' . $modelName;
        $where[] = Where::eq($foreignKey, $this->id());

        if (false === $cached) {
            return $modelClass::all($where, $order);
        }

        // clave prefijada por la tabla del modelo relacionado, para que su
        // clearCache() (deleteMulti 'table-<tabla>-') la purgue al cambiar
        $cacheKey = 'table-' . $modelClass::tableName() . '-hasmany-'
            . md5($foreignKey . '|' . serialize($where) . '|' . serialize($order));

        return (new CacheWithMemory())->remember($cacheKey, function () use ($modelClass, $where, $order) {
            return $modelClass::all($where, $order);
        });
    }

    /**
     * Comprueba un campo modificado antes de actualizar el registro.
     *
     * @param string $field
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
     * Este método se llama al eliminar un registro de la base de datos.
     */
    protected function onDelete(): void
    {
        $this->pipe('onDelete');
    }

    /**
     * Este método se llama al insertar un nuevo registro en la base de datos (saveInsert).
     */
    protected function onInsert(): void
    {
        $this->pipe('onInsert');
    }

    /**
     * Este método se llama al actualizar un registro en la base de datos.
     */
    protected function onUpdate(): void
    {
        $this->pipe('onUpdate');
    }

    /**
     * Inserta un nuevo registro en la base de datos.
     *
     * @return bool
     */
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

    /**
     * Este método se llama al actualizar un registro existente en la base de datos.
     *
     * @return bool Devuelve true si la actualización se realizó correctamente, false en caso contrario
     */
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
