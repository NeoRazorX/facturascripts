<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\EventManager;
use FacturaScripts\Core\Model\CodeModel;

/**
 * The class from which all models inherit, connects to the database,
 * check the structure of the table and if necessary create or adapt.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class ModelClass extends ModelCore
{

    /**
     * Returns the name of the class of the model.
     *
     * @return string
     */
    abstract public function modelClassName();

    /**
     * Returns the name of the model.
     *
     * @return string
     */
    abstract public function modelName();

    /**
     * Returns all models that correspond to the selected filters.
     *
     * @param array $where filters to apply to model records.
     * @param array $order fields to use in the sorting. For example ['code' => 'ASC']
     * @param int   $offset
     * @param int   $limit
     *
     * @return array
     */
    public function all(array $where = [], array $order = [], int $offset = 0, int $limit = 50)
    {
        $modelList = [];
        $sqlWhere = DataBase\DataBaseWhere::getSQLWhere($where);
        $sql = 'SELECT * FROM ' . static::tableName() . $sqlWhere . $this->getOrderBy($order);
        $data = self::$dataBase->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            $class = $this->modelName();
            foreach ($data as $d) {
                $modelList[] = new $class($d);
            }
        }

        return $modelList;
    }

    /**
     * Check an array of data so that it has the correct structure of the model.
     *
     * @param array $data
     */
    public function checkArrayData(array &$data)
    {
        $dateTypes = ['date', 'datetime', 'timestamp'];
        foreach ($this->getModelFields() as $field => $values) {
            if (in_array($values['type'], $dateTypes) && isset($data[$field]) && '' === $data[$field]) {
                $data[$field] = null;
            }
        }
    }

    /**
     * Allows to use this model as source in CodeModel special model.
     * 
     * @param string $fieldcode
     * 
     * @return CodeModel[]
     */
    public function codeModelAll(string $fieldcode = '')
    {
        $results = [];
        $field = empty($fieldcode) ? $this->primaryColumn() : $fieldcode;

        $sql = 'SELECT DISTINCT ' . $field . ' AS code, ' . $this->primaryDescriptionColumn() . ' AS description '
            . 'FROM ' . $this->tableName() . ' ORDER BY 2 ASC';
        foreach (self::$dataBase->selectLimit($sql, CodeModel::ALL_LIMIT) as $d) {
            $results[] = new CodeModel($d);
        }

        return $results;
    }

    /**
     * Allows to use this model as source in CodeModel special model.
     * 
     * @param string $query
     * @param string $fieldcode
     *
     * @return CodeModel[]
     */
    public function codeModelSearch(string $query, string $fieldcode = '')
    {
        $field = empty($fieldcode) ? $this->primaryColumn() : $fieldcode;
        $fields = $field . '|' . $this->primaryDescriptionColumn();
        $where = [new DataBase\DataBaseWhere($fields, mb_strtolower($query, 'UTF8'), 'LIKE')];
        return CodeModel::all($this->tableName(), $field, $this->primaryDescriptionColumn(), false, $where);
    }

    /**
     * Returns the number of records in the model that meet the condition.
     *
     * @param DataBase\DataBaseWhere[] $where filters to apply to model records.
     *
     * @return int
     */
    public function count(array $where = [])
    {
        $sql = 'SELECT COUNT(1) AS total FROM ' . static::tableName() . DataBase\DataBaseWhere::getSQLWhere($where);
        $data = self::$dataBase->select($sql);
        return empty($data) ? 0 : (int) $data[0]['total'];
    }

    /**
     * Remove the model data from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . static::tableName() . ' WHERE ' . static::primaryColumn()
            . ' = ' . self::$dataBase->var2str($this->primaryColumnValue()) . ';';

        if (self::$dataBase->exec($sql)) {
            EventManager::trigger('Model:' . $this->modelClassName() . ':delete', $this);
            return true;
        }

        return false;
    }

    /**
     * Returns true if the model data is stored in the database.
     *
     * @return bool
     */
    public function exists()
    {
        if ($this->primaryColumnValue() === null) {
            return false;
        }

        $sql = 'SELECT 1 FROM ' . static::tableName() . ' WHERE ' . static::primaryColumn()
            . ' = ' . self::$dataBase->var2str($this->primaryColumnValue()) . ';';

        return (bool) self::$dataBase->select($sql);
    }

    /**
     * Returns the model whose primary column corresponds to the value $cod
     *
     * @param string $cod
     *
     * @return mixed
     */
    public function get($cod)
    {
        $data = $this->getRecord($cod);
        if (!empty($data)) {
            $class = $this->modelName();
            return new $class($data[0]);
        }

        return false;
    }

    /**
     * Fill the class with the registry values
     * whose primary column corresponds to the value $cod, or according to the condition
     * where indicated, if value is not reported in $cod.
     * Initializes the values of the class if there is no record that
     * meet the above conditions.
     * Returns True if the record exists and False otherwise.
     *
     * @param string $cod
     * @param array  $where
     * @param array  $orderby
     *
     * @return bool
     */
    public function loadFromCode($cod, array $where = [], array $orderby = [])
    {
        $data = $this->getRecord($cod, $where, $orderby);
        if (empty($data)) {
            $this->clear();
            return false;
        }

        $this->loadFromData($data[0]);
        return true;
    }

    /**
     * Returns the following code for the reported field or the primary key of the model.
     *
     * @param string $field
     * @param array  $where
     *
     * @return int
     */
    public function newCode(string $field = '', array $where = [])
    {
        /// if not field value take PK Field
        if (empty($field)) {
            $field = $this->primaryColumn();
        }

        /// get fields list
        $modelFields = $this->getModelFields();

        /// Set Cast to Integer if field it's not
        if (!in_array($modelFields[$field]['type'], ['integer', 'int', 'serial'])) {
            /// Set Where to Integers values only
            $where[] = new DataBase\DataBaseWhere($field, '^-?[0-9]+$', 'REGEXP');
            $field = self::$dataBase->sql2Int($field);
        }

        /// Search for new code value
        $sqlWhere = DataBase\DataBaseWhere::getSQLWhere($where);
        $sql = 'SELECT MAX(' . $field . ') as cod FROM ' . static::tableName() . $sqlWhere . ';';
        $cod = self::$dataBase->select($sql);
        return empty($cod) ? 1 : 1 + (int) $cod[0]['cod'];
    }

    /**
     * Returns the name of the column that describes the model, such as name, description...
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        $fields = $this->getModelFields();
        if (isset($fields['descripcion'])) {
            return 'descripcion';
        }

        return $this->primaryColumn();
    }

    /**
     * Descriptive identifier for humans of the data record
     *
     * @return string
     */
    public function primaryDescription()
    {
        $field = $this->primaryDescriptionColumn();
        return isset($this->{$field}) ? $this->{$field} : (string) $this->primaryColumnValue();
    }

    /**
     * Stores the model data in the database.
     *
     * @return bool
     */
    public function save()
    {
        if ($this->test()) {
            if ($this->exists()) {
                return $this->saveUpdate();
            }

            return $this->saveInsert();
        }

        return false;
    }

    /**
     * Returns true if there are no errors in the values of the model properties.
     * It runs inside the save method.
     *
     * @return bool
     */
    public function test()
    {
        $fields = $this->getModelFields();
        if (empty($fields)) {
            return false;
        }

        $return = true;
        foreach ($fields as $key => $value) {
            if ($key == $this->primaryColumn()) {
                continue;
            } elseif (null === $value['default'] && $value['is_nullable'] === 'NO' && $this->{$key} === null) {
                self::$miniLog->alert(self::$i18n->trans('field-can-not-be-null', ['%fieldName%' => $key, '%tableName%' => static::tableName()]));
                $return = false;
            }
        }

        return $return;
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        $value = $this->primaryColumnValue();
        $model = $this->modelClassName();
        switch ($type) {
            case 'edit':
                return is_null($value) ? 'Edit' . $model : 'Edit' . $model . '?code=' . $value;

            case 'list':
                return $list . $model;

            case 'new':
                return 'Edit' . $model;
        }

        /// default
        return empty($value) ? $list . $model : 'Edit' . $model . '?code=' . $value;
    }

    /**
     * Insert the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {

        EventManager::trigger('Model:' . $this->modelClassName() . ':saveInsert:before', $this);
        EventManager::trigger('Model:' . $this->modelClassName() . ':save:before', $this);

        $insertFields = [];
        $insertValues = [];
        foreach ($this->getModelFields() as $field) {
            if (isset($this->{$field['name']})) {
                $fieldName = $field['name'];
                $fieldValue = isset($values[$fieldName]) ? $values[$fieldName] : $this->{$fieldName};

                $insertFields[] = $fieldName;
                $insertValues[] = self::$dataBase->var2str($fieldValue);
            }
        }

        $sql = 'INSERT INTO ' . static::tableName()
            . ' (' . implode(',', $insertFields) . ') VALUES (' . implode(',', $insertValues) . ');';
        if (self::$dataBase->exec($sql)) {
            if ($this->primaryColumnValue() === null) {
                $this->{static::primaryColumn()} = self::$dataBase->lastval();
            }

            EventManager::trigger('Model:' . $this->modelClassName() . ':saveInsert', $this);
            EventManager::trigger('Model:' . $this->modelClassName() . ':save', $this);
            return true;
        }

        return false;
    }

    /**
     * Update the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveUpdate(array $values = [])
    {
        EventManager::trigger('Model:' . $this->modelClassName() . ':saveUpdate:before', $this);
        EventManager::trigger('Model:' . $this->modelClassName() . ':save:before', $this);

        $sql = 'UPDATE ' . static::tableName();
        $coma = ' SET';

        foreach ($this->getModelFields() as $field) {
            if ($field['name'] !== $this->primaryColumn()) {
                $fieldName = $field['name'];
                $fieldValue = isset($values[$fieldName]) ? $values[$fieldName] : $this->{$fieldName};
                $sql .= $coma . ' ' . $fieldName . ' = ' . self::$dataBase->var2str($fieldValue);
                $coma = ', ';
            }
        }

        $sql .= ' WHERE ' . static::primaryColumn() . ' = ' . self::$dataBase->var2str($this->primaryColumnValue()) . ';';
        if (self::$dataBase->exec($sql)) {
            EventManager::trigger('Model:' . $this->modelClassName() . ':saveUpdate', $this);
            EventManager::trigger('Model:' . $this->modelClassName() . ':save', $this);
            return true;
        }

        return false;
    }

    /**
     * Convert an array of filters order by in string.
     *
     * @param array $order
     *
     * @return string
     */
    private function getOrderBy(array $order)
    {
        $result = '';
        $coma = ' ORDER BY ';
        foreach ($order as $key => $value) {
            $result .= $coma . $key . ' ' . $value;
            $coma = ', ';
        }

        return $result;
    }

    /**
     * Read the record whose primary column corresponds to the value $cod
     * or the first that meets the indicated condition.
     *
     * @param string $cod
     * @param array  $where
     * @param array  $orderby
     *
     * @return array
     */
    private function getRecord($cod, array $where = [], array $orderby = [])
    {
        $sqlWhere = empty($where) ? ' WHERE ' . static::primaryColumn() . ' = ' . self::$dataBase->var2str($cod) : DataBase\DataBaseWhere::getSQLWhere($where);
        $sql = 'SELECT * FROM ' . static::tableName() . $sqlWhere . $this->getOrderBy($orderby);

        return self::$dataBase->selectLimit($sql, 1);
    }
}
