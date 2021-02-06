<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\CodeModel;

/**
 * The class from which all models inherit, connects to the database,
 * check the structure of the table and if necessary create or adapt.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class ModelClass extends ModelCore
{

    /**
     * Returns all models that correspond to the selected filters.
     *
     * @param array $where filters to apply to model records.
     * @param array $order fields to use in the sorting. For example ['code' => 'ASC']
     * @param int   $offset
     * @param int   $limit
     *
     * @return static[]
     */
    public function all(array $where = [], array $order = [], int $offset = 0, int $limit = 50)
    {
        $modelList = [];
        $sql = 'SELECT * FROM ' . static::tableName() . DataBaseWhere::getSQLWhere($where) . $this->getOrderBy($order);
        foreach (self::$dataBase->selectLimit($sql, $limit, $offset) as $row) {
            $modelList[] = new static($row);
        }

        return $modelList;
    }

    /**
     * Allows to use this model as source in CodeModel special model.
     * 
     * @param string $fieldCode
     * 
     * @return CodeModel[]
     */
    public function codeModelAll(string $fieldCode = '')
    {
        $results = [];
        $field = empty($fieldCode) ? static::primaryColumn() : $fieldCode;

        $sql = 'SELECT DISTINCT ' . $field . ' AS code, ' . $this->primaryDescriptionColumn() . ' AS description '
            . 'FROM ' . static::tableName() . ' ORDER BY 2 ASC';
        foreach (self::$dataBase->selectLimit($sql, CodeModel::ALL_LIMIT) as $d) {
            $results[] = new CodeModel($d);
        }

        return $results;
    }

    /**
     * Allows to use this model as source in CodeModel special model.
     * 
     * @param string          $query
     * @param string          $fieldCode
     * @param DataBaseWhere[] $where
     *
     * @return CodeModel[]
     */
    public function codeModelSearch(string $query, string $fieldCode = '', $where = [])
    {
        $field = empty($fieldCode) ? static::primaryColumn() : $fieldCode;
        $fields = $field . '|' . $this->primaryDescriptionColumn();
        $where[] = new DataBaseWhere($fields, \mb_strtolower($query, 'UTF8'), 'LIKE');
        return CodeModel::all(static::tableName(), $field, $this->primaryDescriptionColumn(), false, $where);
    }

    /**
     * Returns the number of records in the model that meet the condition.
     *
     * @param DataBaseWhere[] $where filters to apply to model records.
     *
     * @return int
     */
    public function count(array $where = [])
    {
        $sql = 'SELECT COUNT(1) AS total FROM ' . static::tableName() . DataBaseWhere::getSQLWhere($where);
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
        if ($this->pipe('deleteBefore') === false) {
            return false;
        }

        $sql = 'DELETE FROM ' . static::tableName() . ' WHERE ' . static::primaryColumn()
            . ' = ' . self::$dataBase->var2str($this->primaryColumnValue()) . ';';

        if (self::$dataBase->exec($sql)) {
            $this->pipe('delete');
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
        $sql = 'SELECT 1 FROM ' . static::tableName() . ' WHERE ' . static::primaryColumn()
            . ' = ' . self::$dataBase->var2str($this->primaryColumnValue()) . ';';

        return empty($this->primaryColumnValue()) ? false : (bool) self::$dataBase->select($sql);
    }

    /**
     * Returns the model whose primary column corresponds to the value $cod
     *
     * @param string $code
     *
     * @return static|false
     */
    public function get($code)
    {
        $data = $this->getRecord($code);
        return empty($data) ? false : new static($data[0]);
    }

    /**
     * Fill the class with the registry values
     * whose primary column corresponds to the value $cod, or according to the condition
     * where indicated, if value is not reported in $cod.
     * Initializes the values of the class if there is no record that
     * meet the above conditions.
     * Returns True if the record exists and False otherwise.
     *
     * @param string $code
     * @param array  $where
     * @param array  $orderby
     *
     * @return bool
     */
    public function loadFromCode($code, array $where = [], array $orderby = [])
    {
        $data = $this->getRecord($code, $where, $orderby);
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
            $field = static::primaryColumn();
        }

        /// get fields list
        $modelFields = $this->getModelFields();

        /// Set Cast to Integer if field it's not
        if (false === \in_array($modelFields[$field]['type'], ['integer', 'int', 'serial'])) {
            /// Set Where to Integers values only
            $where[] = new DataBaseWhere($field, '^-?[0-9]+$', 'REGEXP');
            $field = self::$dataBase->getEngine()->getSQL()->sql2Int($field);
        }

        /// Search for new code value
        $sqlWhere = DataBaseWhere::getSQLWhere($where);
        $sql = 'SELECT MAX(' . $field . ') as cod FROM ' . static::tableName() . $sqlWhere . ';';
        $data = self::$dataBase->select($sql);
        return empty($data) ? 1 : 1 + (int) $data[0]['cod'];
    }

    /**
     * Returns the name of the column that describes the model, such as name, description...
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        $fields = $this->getModelFields();
        return isset($fields['descripcion']) ? 'descripcion' : static::primaryColumn();
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
        if ($this->pipe('saveBefore') === false) {
            return false;
        }

        $done = false;
        if ($this->test()) {
            $done = $this->exists() ? $this->saveUpdate() : $this->saveInsert();
        }

        if ($done) {
            $this->pipe('save');
        }

        return $done;
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
            if ($key == static::primaryColumn()) {
                $this->{$key} = empty($this->{$key}) ? null : $this->{$key};
            } elseif (null === $value['default'] && $value['is_nullable'] === 'NO' && $this->{$key} === null) {
                $this->toolBox()->i18nLog()->warning('field-can-not-be-null', ['%fieldName%' => $key, '%tableName%' => static::tableName()]);
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
                return \is_null($value) ? 'Edit' . $model : 'Edit' . $model . '?code=' . \rawurlencode($value);

            case 'list':
                return $list . $model;

            case 'new':
                return 'Edit' . $model;
        }

        /// default
        return empty($value) ? $list . $model : 'Edit' . $model . '?code=' . \rawurlencode($value);
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
        if ($this->pipe('saveInsertBefore') === false) {
            return false;
        }

        $insertFields = [];
        $insertValues = [];
        foreach ($this->getModelFields() as $field) {
            if (isset($this->{$field['name']})) {
                $fieldName = $field['name'];
                $fieldValue = isset($values[$fieldName]) ? $values[$fieldName] : $this->{$fieldName};

                $insertFields[] = self::$dataBase->escapeColumn($fieldName);
                $insertValues[] = self::$dataBase->var2str($fieldValue);
            }
        }

        $sql = 'INSERT INTO ' . static::tableName() . ' (' . \implode(',', $insertFields) . ') VALUES (' . \implode(',', $insertValues) . ');';
        if (self::$dataBase->exec($sql)) {
            if ($this->primaryColumnValue() === null) {
                $this->{static::primaryColumn()} = self::$dataBase->lastval();
            } else {
                self::$dataBase->updateSequence(static::tableName(), $this->getModelFields());
            }

            $this->pipe('saveInsert');
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
        if ($this->pipe('saveUpdateBefore') === false) {
            return false;
        }

        $sql = 'UPDATE ' . static::tableName();
        $coma = ' SET';

        foreach ($this->getModelFields() as $field) {
            if ($field['name'] !== static::primaryColumn()) {
                $fieldName = $field['name'];
                $fieldValue = isset($values[$fieldName]) ? $values[$fieldName] : $this->{$fieldName};
                $sql .= $coma . ' ' . self::$dataBase->escapeColumn($fieldName) . ' = ' . self::$dataBase->var2str($fieldValue);
                $coma = ', ';
            }
        }

        $sql .= ' WHERE ' . static::primaryColumn() . ' = ' . self::$dataBase->var2str($this->primaryColumnValue()) . ';';
        if (self::$dataBase->exec($sql)) {
            $this->pipe('saveUpdate');
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
     * @param string $code
     * @param array  $where
     * @param array  $orderby
     *
     * @return array
     */
    private function getRecord($code, array $where = [], array $orderby = [])
    {
        $sqlWhere = empty($where) ? ' WHERE ' . static::primaryColumn() . ' = ' . self::$dataBase->var2str($code) : DataBaseWhere::getSQLWhere($where);
        $sql = 'SELECT * FROM ' . static::tableName() . $sqlWhere . $this->getOrderBy($orderby);
        return empty($code) && empty($where) ? [] : self::$dataBase->selectLimit($sql, 1);
    }
}
