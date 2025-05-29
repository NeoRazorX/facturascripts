<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Lib\Import\CSVImport;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Core\WorkQueue;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * Main class for managing data models
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
abstract class ModelClass extends ModelCore
{
    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     * Adds an extension to this model.
     *
     * @param mixed $extension
     */
    abstract public static function addExtension($extension);

    /**
     * Returns the list of fields in the table.
     *
     * @return array
     */
    abstract public function getModelFields(): array;

    /**
     * Executes all $name methods added from the extensions.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    abstract public function pipe(string $name, ...$arguments);

    /**
     * Executes all $name methods added from the extensions until someone returns false.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return bool
     */
    abstract public function pipeFalse($name, ...$arguments): bool;

    /**
     * Loads table fields if is necessary.
     *
     * @param DataBase $dataBase
     * @param string $tableName
     */
    abstract protected function loadModelFields(DataBase $dataBase, string $tableName);

    /**
     * Returns the name of the model.
     *
     * @return string
     */
    abstract protected function modelName(): string;

    /**
     * Model constructor.
     *
     * @param array $data
     * @throws Exception
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

        if (false === DbUpdater::isTableChecked(static::tableName())) {
            if (self::$dataBase->tableExists(static::tableName())) {
                DbUpdater::updateTable(static::tableName());
            } else {
                DbUpdater::createTable(static::tableName(), [], $this->install());
            }
        }

        $this->loadModelFields(self::$dataBase, static::tableName());
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Returns array with the models that correspond to the selected filters.
     *
     * @param Where[] $where filters to apply to model records.
     * @param array $order fields to use in the sorting. For example ['code' => 'ASC']
     * @param int $offset
     * @param int $limit
     *
     * @return static[]
     * @throws Exception
     */
    public static function all(array $where = [], array $order = [], int $offset = 0, int $limit = 0): array
    {
        if (false === DbUpdater::isTableChecked(static::tableName())
            || is_null(self::$dataBase)
            || false === self::$dataBase->connected()
        ) {
            new static();
        }

        $data = static::table()
            ->limit($limit)
            ->offset($offset)
            ->where($where)
            ->orderMulti($order)
            ->get();

        $result = [];
        foreach ($data as $row) {
            $result[] = new static($row);
        }
        return $result;
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $fields = $this->getModelFields();
        foreach ($fields as $field) {
            $this->setFieldValue($field, null);
        }

        $this->pipe('clear');
    }

    /**
     * Remove data from cache model.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::deleteMulti('model-' . static::modelClassName() . '-');
        Cache::deleteMulti('join-model-');
        Cache::deleteMulti('table-' . static::tableName() . '-');
    }

    /**
     * Allows to use this model as source in CodeModel special model.
     *
     * @param string $fieldCode
     *
     * @return CodeModel[]
     */
    public function codeModelAll(string $fieldCode = ''): array
    {
        $field = empty($fieldCode) ? static::primaryColumn() : $fieldCode;
        $sql = 'SELECT DISTINCT ' . $field . ' AS code, ' . $this->primaryDescriptionColumn() . ' AS description '
            . 'FROM ' . static::tableName() . ' ORDER BY 2 ASC';

        $results = [];
        foreach (self::$dataBase->selectLimit($sql, CodeModel::ALL_LIMIT) as $d) {
            $results[] = new CodeModel($d);
        }

        return $results;
    }

    /**
     * Allows to use this model as source in CodeModel special model.
     * TODO: Correct the DataBaseWhere calls
     * FIXME: Old version of the function use DataBaseWhere in the call to all method
     *
     * @param string $query
     * @param string $fieldCode
     * @param Where[] $where
     *
     * @return CodeModel[]
     */
    public function codeModelSearch(string $query, string $fieldCode = '', array $where = []): array
    {
        $field = empty($fieldCode) ? static::primaryColumn() : $fieldCode;
        $fields = $field . '|' . $this->primaryDescriptionColumn();
        $where[] = Where::like($fields, mb_strtolower($query, 'UTF8'));
        return CodeModel::all(static::tableName(), $field, $this->primaryDescriptionColumn(), false, $where);
    }

    /**
     * Returns the number of records in the model that meet the condition.
     *
     * @param Where[] $where filters to apply to model records.
     *
     * @return int
     * @throws Exception
     */
    public static function count(array $where = []): int
    {
        $key = 'model-' . static::modelClassName() . '-count';
        return Cache::remember($key, function () use ($where) {
            return static::table()
                ->where($where)
                ->count();
        });
    }

    /**
     * Remove the model data from the database.
     *
     * @return bool
     * @throws KernelException
     */
    public function delete(): bool
    {
        if (null === $this->primaryColumnValue()) {
            return true;
        }

        if ($this->pipeFalse('deleteBefore') === false) {
            return false;
        }

        $pkValue = self::$dataBase->var2str($this->primaryColumnValue());
        if (false === $this->table()
            ->whereEq(static::primaryColumn(), $pkValue)
            ->delete()
        ) {
            return false;
        }

        $this->clearCache();

        WorkQueue::send(
            'Model.' . $this->modelClassName() . '.Delete',
            $this->primaryColumnValue(),
            $this->toArray()
        );

        return $this->pipeFalse('delete');
    }

    /**
     * Delete records from the model table which meet the conditions.
     *
     * @param Where[] $where
     * @return bool
     * @throws Exception
     */
    public static function deleteAll(array $where): bool
    {
        if (false === static::table()
            ->where($where)
            ->delete()
        ) {
            return false;
        }
        static::clearCache();
        return true;
    }

    /**
     * Returns true if the model data is stored in the database.
     * // TODO: Check if the method is necessary
     *
     * @return bool
     * @throws KernelException
     */
    public function exists(): bool
    {
        return !empty($this->primaryColumnValue())
            && self::find($this->primaryColumnValue()) !== null;
    }

    /**
     * Return the model whose primary column corresponds to the value $code
     * If not found, it throws an exception.
     *
     * @param $code
     * @return static|null
     * @throws KernelException|Exception
     */
    public static function find($code)
    {
        $cacheKey = 'model-' . static::modelClassName() . '-' . $code;
        return Cache::remember($cacheKey, function () use ($code) {
            $where = [ Where::eq(static::primaryColumn(), $code) ];
            $data = self::getRecord($where);
            return empty($data) ? null : $data[0];
        });
    }

    /**
     * Return the model whose primary column corresponds to the value $code
     * If not found, it throws an exception.
     *
     * @param $code
     * @return static
     * @throws KernelException
     */
    public static function findOrFail($code)
    {
        $result = self::find($code);
        if (empty($result)) {
            throw new KernelException('RecordNotFound', 'Record not found');
        }
        return $result;
    }

    /**
     * Returns the model whose primary column corresponds to the value $cod
     * // TODO: Check if the method is necessary
     *
     * @param string $code
     *
     * @return static|false
     * @throws KernelException
     */
    public function get($code)
    {
        $data = self::find($code);
        return empty($data) ? false : $data;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     * @throws Exception
     */
    public function install(): string
    {
        return CSVImport::importTableSQL(static::tableName());
    }

    /**
     * Read the record whose primary column corresponds to the value $code
     *
     * @param $code
     * @return bool
     * @throws Exception
     */
    public function load($code): bool
    {
        $cacheKey = 'model-' . $this->modelClassName() . '-' . $code;
        return Cache::remember($cacheKey, function () use ($code) {
            $where = [ Where::eq(static::primaryColumn(), $code) ];
            $data = $this->getRecord($where);
            if (empty($data)) {
                $this->clear();
                return false;
            }

            $this->loadFromData($data[0]);
            return true;
        });
    }

    /**
     * Read the record whose primary column corresponds to the value $code
     * If not found, it throws an exception.
     *
     * @param $code
     * @return bool
     * @throws Exception
     */
    public function loadOrFail($code): bool
    {
        if (false === $this->load($code)) {
            throw new KernelException('RecordNotFound', 'Record not found');
        }
        return true;
    }

    /**
     * @param Where[] $where filters to apply to model records.
     * @param array $order fields to use in the sorting. For example ['code' => 'ASC']
     * @return bool
     * @throws Exception
     */
    public function loadWhere(array $where, array $order = []): bool
    {
        $data = $this->getRecord($where, $order);
        if (empty($data)) {
            $this->clear();
            return false;
        }
        $this->loadFromData($data[0]);
        return true;
    }

    /**
     * @param Where[] $where filters to apply to model records.
     * @param array $order fields to use in the sorting. For example ['code' => 'ASC']
     * @return bool
     * @throws Exception
     */
    public function loadWhereOrNew(array $where, array $order): bool
    {
        if ($this->loadWhere($where, $order)) {
            return true;
        }

        $fields = $this->getModelFields();
        foreach ($where as $item) {
            $columns = explode(Where::FIELD_SEPARATOR, $item->fields);
            foreach ($columns as $column) {
                if (false === isset($fields[$column])) {
                    continue;
                }
                $dbField = $fields[$column];
                $this->setFieldValue($dbField, $item->value);
            }
        }
        return false;
    }

    /**
     * Assign the values of the $data array to the model properties.
     *
     * @param array $data
     * @param array $exclude
     */
    public function loadFromData(array $data = [], array $exclude = []): void
    {
        $fields = $this->getModelFields();
        foreach ($data as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }

            if (false === isset($fields[$key])) {
                $this->{$key} = $value;
                continue;
            }
            $this->setFieldValue($fields[$key], $value);
        }
    }

    /**
     * Returns the following code for the reported field or the primary key of the model.
     *
     * @param string $field
     * @param array $where
     *
     * @return int
     * @throws KernelException
     */
    public function newCode(string $field = '', array $where = []): int
    {
        // TODO: Call to new Class to get the next code
        $field = empty($field) ? static::primaryColumn() : $field;
        throw new KernelException('DeveloperException', 'Method not implemented');
    }

    /**
     * Returns the current value of the main column of the model.
     *
     * @return mixed
     */
    public function primaryColumnValue()
    {
        return $this->{$this->primaryColumn()};
    }

    /**
     * Returns the name of the column that describes the model, such as name, description...
     *
     * @return string
     */
    public function primaryDescriptionColumn(): string
    {
        $fields = $this->getModelFields();
        return isset($fields['descripcion']) ? 'descripcion' : static::primaryColumn();
    }

    /**
     * Descriptive identifier for humans of the data record
     *
     * @return string
     */
    public function primaryDescription(): string
    {
        $field = $this->primaryDescriptionColumn();
        return $this->{$field} ?? (string)$this->primaryColumnValue();
    }

    /**
     * Stores the model data in the database.
     *
     * @return bool
     * @throws KernelException
     */
    public function save(): bool
    {
        if ($this->pipeFalse('saveBefore') === false) {
            return false;
        }

        if (false === $this->test()) {
            return false;
        }

        $done = $this->exists() ? $this->saveUpdate() : $this->saveInsert();
        if (false === $done) {
            return false;
        }

        WorkQueue::send(
            'Model.' . $this->modelClassName() . '.Save',
            $this->primaryColumnValue(),
            $this->toArray()
        );

        return $this->pipeFalse('save');
    }

    /**
     * Return the DbQuery object for the model table.
     *
     * @return DbQuery
     * @throws Exception
     */
    public static function table(): DbQuery
    {
        return DbQuery::table(static::tableName());
    }

    /**
     * Returns true if there are no errors in the values of the model properties.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test(): bool
    {
        if ($this->pipeFalse('testBefore') === false) {
            return false;
        }

        $fields = $this->getModelFields();
        if (empty($fields)) {
            return false;
        }

        $return = true;
        foreach ($fields as $key => $value) {
            // set null to primary key if it is empty (new records)
            if ($key == static::primaryColumn()) {
                if (empty($this->{$key})) {
                    $this->{$key} = null;
                    continue;
                }
            }

            // check if the required fields are not empty
            if ($this->{$key} === null) {
                if (null === $value['default'] && $value['is_nullable'] === 'NO') {
                    Tools::log()->warning('field-can-not-be-null', ['%fieldName%' => $key, '%tableName%' => static::tableName()]);
                    $return = false;
                }
                continue;
            }

            // check if the value is correct for the field type
            if (false === $this->checkValueType($key, $value)) {
                $return = false;
            }
        }
        if (false === $return) {
            return false;
        }

        return $this->pipeFalse('test');
    }

    /**
     * Returns an array with the model fields values.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [];
        foreach (array_keys($this->getModelFields()) as $fieldName) {
            $data[$fieldName] = $this->{$fieldName} ?? null;
        }

        return $data;
    }

    /**
     * Returns the sum of the values of the field in the model table.
     *
     * @param string $field
     * @param Where[] $where
     * @return float
     * @throws Exception
     */
    public function totalSum(string $field, array $where = []): float
    {
        if (false === empty($where)) {
            return $this->table()
                ->where($where)
                ->sum($field);
        }

        // No where, update the cache
        $key = 'model-' . $this->modelClassName() . '-' . $field . '-total-sum';
        return Cache::remember($key, function () use ($field) {
            return $this->table()->sum($field);
        });
    }

    /**
     * Update the model data in the database.
     *
     * @param array $newValues
     * @return bool
     * @throws KernelException|Exception
     */
    public function update(array $newValues): bool
    {
        if (false === $this->table()
            ->whereEq($this->primaryColumn(), $this->primaryColumnValue())
            ->update($newValues)
        ) {
            return false;
        }

        $this->loadFromData($newValues);
        $this->clearCache();
        return true;
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List'): string
    {
        $value = $this->primaryColumnValue();
        $model = $this->modelClassName();

        $return = $this->pipe('url', $type, $list);
        if ($return) {
            return $return;
        }

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
     * Insert the model data in the database.
     *
     * @return bool
     * @throws Exception
     */
    protected function saveInsert(): bool
    {
        if ($this->pipeFalse('saveInsertBefore') === false) {
            return false;
        }

        $data = $this->toArray();
        if (false === $this->table()->insert($data)) {
            return false;
        }

        if ($this->primaryColumnValue() === null) {
            $this->{static::primaryColumn()} = self::$dataBase->lastval();
        } else {
            // update sequence, is necessary for PostgreSQL
            self::$dataBase->updateSequence(static::tableName(), $this->getModelFields());
        }

        $this->clearCache();
        WorkQueue::send(
            'Model.' . $this->modelClassName() . '.Insert',
            $this->primaryColumnValue(),
            $data
        );

        return $this->pipeFalse('saveInsert');
    }

    /**
     * Update the model data in the database.
     *
     * @return bool
     * @throws KernelException
     */
    protected function saveUpdate(): bool
    {
        if ($this->pipeFalse('saveUpdateBefore') === false) {
            return false;
        }

        $data = $this->toArray();
        if (false === $this->update($data)) {
            return false;
        }

        $this->clearCache();
        WorkQueue::send(
            'Model.' . $this->modelClassName() . '.Update',
            $this->primaryColumnValue(),
            $data
        );

        return $this->pipeFalse('saveUpdate');
    }

    /**
     * Check the value of the field according to its type.
     *
     * @param string $field
     * @param array $fieldData
     * @return bool
     */
    private function checkValueType(string $field, array $fieldData): bool
    {
        switch ($this->fieldType($fieldData['type'])) {
            case 'tinyint':
            case 'boolean':
                if (is_bool($this->{$field})) {
                    return true;
                }
                if (in_array(strtolower($this->{$field}), ['true', 't', '1'])) {
                    $this->{$field} = true;
                    return true;
                }
                if (in_array(strtolower($this->{$field}), ['false', 'f', '0'])) {
                    $this->{$field} = false;
                    return true;
                }
                Tools::log()->warning('field-must-be-boolean', ['%fieldName%' => $field]);
                return false;

            case 'integer':
            case 'int':
                if (is_numeric($this->{$field})) {
                    return true;
                }
                Tools::log()->warning('field-must-be-integer', ['%fieldName%' => $field]);
                return false;

            case 'decimal':
            case 'double':
            case 'double precision':
            case 'float':
                if (is_numeric($this->{$field})) {
                    return true;
                }
                Tools::log()->warning('field-must-be-float', ['%fieldName%' => $field]);
                return false;

            case 'date':
                if (Tools::date($this->{$field})) {
                    return true;
                }
                Tools::log()->warning('field-must-be-date', ['%fieldName%' => $field]);
                return false;

            case 'datetime':
            case 'timestamp':
                if (Tools::dateTime($this->{$field})) {
                    return true;
                }
                Tools::log()->warning('field-must-be-datetime', ['%fieldName%' => $field]);
                return false;

            default:
                if (false === is_string($this->{$field})) {
                    Tools::log()->warning('field-must-be-string', ['%fieldName%' => $field]);
                    return false;
                }

                // Check the max length of the field
                if (preg_match('/\((\d+)\)/', $fieldData['type'], $matches)) {
                    if ((int)$matches[1] < strlen($this->{$field})) {
                        Tools::log()->warning('field-length-exceeded', ['%fieldName%' => $field]);
                        return false;
                    }
                }
                return true;
        }
    }

    /**
     * Return the database field type.
     *
     * @param string $type
     *
     * @return string
     */
    private function fieldType(string $type): string
    {
        // We check if it is a varchar (with established length) or another type of data
        return strpos($type, '(') === false
            ? $type
            : substr($type, 0, strpos($type, '('));
    }

    /**
     * Returns the boolean value for the field.
     *
     * @param array $field
     * @param mixed $value
     *
     * @return bool|null
     */
    private function getBoolValueForField(array $field, $value): ?bool
    {
        if ($value === null) {
            return $field['is_nullable'] === 'NO' ? false : null;
        }

        if (is_bool($value)) {
            return $value;
        }
        return in_array(strtolower($value), ['true', 't', '1']);
    }

    /**
     * Returns the date value for the field.
     *
     * @param array $field
     * @param $value
     *
     * @return string|null
     */
    private function getDateValueForField(array $field, $value): ?string
    {
        if (empty($value) && $field['is_nullable'] !== 'NO') {
            return null;
        }
        return Tools::date($value);
    }

    /**
     * Return the date time value for the field.
     *
     * @param array $field
     * @param $value
     *
     * @return string|null
     */
    private function getDateTimeValueForField(array $field, $value): ?string
    {
        if (empty($value) && $field['is_nullable'] !== 'NO') {
            return null;
        }
        return Tools::dateTime($value);
    }

    /**
     * Returns the float value for the field.
     *
     * @param array $field
     * @param string $value
     *
     * @return float|null
     */
    private function getFloatValueForField(array $field, string $value): ?float
    {
        if (is_numeric($value)) {
            return (float)$value;
        }
        return $field['is_nullable'] === 'NO' ? 0.0 : null;
    }

    /**
     * Returns the integer value by controlling special cases for the PK and FK.
     *
     * @param array $field
     * @param string $value
     *
     * @return int|null
     */
    private function getIntegerValueForField(array $field, string $value): ?int
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
     * @param array $where
     * @param array $order
     * @return array
     * @throws Exception
     */
    private static function getRecord(array $where, array $order = []): array
    {
        return static::table()
            ->where($where)
            ->orderMulti($order)
            ->first();
    }

    /**
     * Set the value of the field according to its type.
     *
     * @param array $field
     * @param $value
     *
     * @return void
     */
    private function setFieldValue(array $field, $value): void
    {
        switch ($this->fieldType($field['type'])) {
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
                $this->{$key} = $this->getDateValueForField($field, $value);
                break;

            case 'datetime':
            case 'timestamp':
                $this->{$key} = $this->getDateTimeValueForField($field, $value);
                break;

            default:
                $this->{$key} = ($value === null && $field['is_nullable'] === 'NO') ? '' : $value;
        }
    }
}
