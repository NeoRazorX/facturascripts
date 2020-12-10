<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;

/**
 * The class from which all views of the model are inherited.
 * It allows the visualization of data of several tables of the database.
 * This type of model is only for reading data, it does not allow modification
 * or deletion of data directly.
 *
 * A main model ("master") must be indicated, which will be responsible for executing
 * the data modification actions. This means that when inserting, modifying or deleting,
 * only the operation on the indicated master model is performed.
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
abstract class JoinModel
{

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     * Master model
     *
     * @var ModelClass
     */
    private $masterModel;

    /**
     * List of values for record view
     *
     * @var array
     */
    private $values = [];

    /**
     * List of tables required for the execution of the view.
     */
    abstract protected function getTables(): array;

    /**
     * List of fields or columns to select clausule
     */
    abstract protected function getFields(): array;

    /**
     * List of tables related to from clausule
     */
    abstract protected function getSQLFrom(): string;

    /**
     * Constructor and class initializer.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();
        }

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
    public function __get($name)
    {
        if (!isset($this->values[$name])) {
            $this->values[$name] = null;
        }

        return $this->values[$name];
    }

    /**
     * Check if exits value to property
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return \array_key_exists($name, $this->values);
    }

    /**
     * Set value to model view field
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->values[$name] = $value;
    }

    /**
     * Load data for the indicated where.
     *
     * @param DataBaseWhere[] $where  filters to apply to model records.
     * @param array           $order  fields to use in the sorting. For example ['code' => 'ASC']
     * @param int             $offset
     * @param int             $limit
     *
     * @return static[]
     */
    public function all(array $where, array $order = [], int $offset = 0, int $limit = 0)
    {
        $result = [];
        if ($this->checkTables()) {
            $sql = 'SELECT ' . $this->fieldsList() . ' FROM ' . $this->getSQLFrom()
                . DataBaseWhere::getSQLWhere($where) . $this->getGroupBy() . $this->getOrderBy($order);
            foreach (self::$dataBase->selectLimit($sql, $limit, $offset) as $row) {
                $result[] = new static($row);
            }
        }

        return $result;
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        foreach (\array_keys($this->getFields()) as $field) {
            $this->values[$field] = null;
        }
    }

    /**
     * Returns the number of records that meet the condition.
     *
     * @param DataBaseWhere[] $where filters to apply to records.
     *
     * @return int
     */
    public function count(array $where = [])
    {
        $groupFields = $this->getGroupFields();
        if (!empty($groupFields)) {
            $groupFields .= ', ';
        }

        $sql = 'SELECT ' . $groupFields . 'COUNT(*) count_total'
            . ' FROM ' . $this->getSQLFrom()
            . DataBaseWhere::getSQLWhere($where)
            . $this->getGroupBy();

        $data = self::$dataBase->select($sql);
        $count = count($data);
        return ($count == 1) ? (int) $data[0]['count_total'] : $count;
    }

    /**
     * Remove the model master data from the database.
     *
     * @return bool
     */
    public function delete()
    {
        if (isset($this->masterModel)) {
            $primaryColumn = $this->masterModel->primaryColumn();
            $this->masterModel->{$primaryColumn} = $this->primaryColumnValue();
            return $this->masterModel->delete();
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
        return isset($this->masterModel) ? $this->masterModel->exists() : $this->count() > 0;
    }

    /**
     * 
     * @return array
     */
    public function getModelFields()
    {
        $fields = [];
        foreach ($this->getFields() as $key => $field) {
            $fields[$key] = [
                'name' => $field,
                'type' => ''
            ];
        }

        return $fields;
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
        if (!$this->loadFilterWhere($cod, $where)) {
            $this->clear();
            return false;
        }

        $sql = 'SELECT ' . $this->fieldsList()
            . ' FROM ' . $this->getSQLFrom()
            . DataBaseWhere::getSQLWhere($where)
            . $this->getGroupBy()
            . $this->getOrderBy($orderby);

        $data = self::$dataBase->selectLimit($sql, 1);
        if (empty($data)) {
            $this->clear();
            return false;
        }

        $this->loadFromData($data[0]);
        return true;
    }

    /**
     * Gets the value from model view cursor of the master model primary key.
     * 
     * @return mixed
     */
    public function primaryColumnValue()
    {
        if (isset($this->masterModel)) {
            $primaryColumn = $this->masterModel->primaryColumn();
            return $this->{$primaryColumn};
        }

        return null;
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
        if (isset($this->masterModel)) {
            $primaryColumn = $this->masterModel->primaryColumn();
            $this->masterModel->{$primaryColumn} = $this->primaryColumnValue();
            return $this->masterModel->url($type, $list);
        }

        return '';
    }

    /**
     * Check list of tables required.
     *
     * @return bool
     */
    private function checkTables(): bool
    {
        foreach ($this->getTables() as $tableName) {
            if (!self::$dataBase->tableExists($tableName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert the list of fields into a string to use as a select clause
     *
     * @return string
     */
    private function fieldsList(): string
    {
        $result = '';
        $comma = '';
        foreach ($this->getFields() as $key => $value) {
            $result = $result . $comma . $value . ' ' . $key;
            $comma = ',';
        }
        return $result;
    }

    /**
     * Return Group By clausule
     *
     * @return string
     */
    private function getGroupBy(): string
    {
        $fields = $this->getGroupFields();
        return empty($fields) ? '' : ' GROUP BY ' . $fields;
    }

    /**
     * Return Group By fields
     *
     * @return string
     */
    protected function getGroupFields(): string
    {
        return '';
    }

    /**
     * Convert an array of filters order by in string.
     *
     * @param array $order
     *
     * @return string
     */
    private function getOrderBy(array $order): string
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
     * If a value is reported for the PK create a database where for
     * the master key of the master model.
     *
     * @param string $cod
     * @param array  $where
     *
     * @return bool
     */
    private function loadFilterWhere($cod, &$where): bool
    {
        /// If there is no search by code we use the where informed
        if (empty($cod)) {
            return true;
        }

        /// If dont define master model cant load from code
        if (!isset($this->masterModel)) {
            return false;
        }

        /// Search primary key from field list
        $primaryColumn = $this->masterModel->primaryColumn();
        foreach ($this->getFields() as $field => $sqlField) {
            if ($field == $primaryColumn) {
                $where = [new DataBaseWhere($sqlField, $cod)];
                return true;
            }
        }

        /// The PK field is not defined in the field list. No posible search by PK
        return false;
    }

    /**
     * Assign the values of the $data array to the model view properties.
     *
     * @param array $data
     */
    protected function loadFromData($data)
    {
        foreach ($data as $field => $value) {
            $this->values[$field] = $value;
        }
    }

    /**
     * Sets the master model for data operations
     *
     * @param ModelClass $model
     */
    protected function setMasterModel($model)
    {
        $this->masterModel = $model;
    }

    /**
     * 
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
    }
}
