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
use FacturaScripts\Core\DbQuery;
use FacturaScripts\Core\DbUpdater;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Lib\Import\CSVImport;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

/**
 * Main class for managing data models
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
abstract class ModelClass
{
    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     * List of fields and his values in the table.
     *
     * @var array
     */
    private $model_properties = [];

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    abstract public static function tableName(): string;

    /**
     * Returns the list of fields in the table.
     *
     * @return array
     */
    abstract public function getModelFields(): array;

    /**
     * Loads table fields if is necessary.
     *
     * @param DataBase $dataBase
     * @param string $tableName
     */
    abstract protected function loadModelFields(DataBase $dataBase, string $tableName);

    /**
     * Returns the name of the class of the model.
     *
     * @return string
     */
    abstract public function modelClassName(): string;

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
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    abstract public static function primaryColumn(): string;

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
     * Return model view field value
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        if (!isset($this->model_properties[$name])) {
            $this->model_properties[$name] = null;
        }

        return $this->model_properties[$name];
    }

    /**
     * Check if exits value to property
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->model_properties);
    }

    /**
     * Set value to model view field
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value): void
    {
        $this->model_properties[$name] = $value;
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
        $where = [ Where::eq(static::primaryColumn(), $code) ];
        $data = $this->getRecord($where);
        if (empty($data)) {
            $this->clear();
            return false;
        }

        $this->loadFromData($data[0]);
        return true;
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
    private function getRecord(array $where, array $order = []): array
    {
        return $this->table()
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
