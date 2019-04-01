<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * The class from which all model views inherit.
 * It allows the visualization of data of several models.
 * This type of model is only for reading data,
 * it does not allow modification or deletion.
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class ModelView
{

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     * List of values for record view
     *
     * @var array
     */
    private $values;

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

        $this->values = [];

        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Check if exits value to property
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->values);
    }

    /**
     * Set value to modal view field
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->values[$name] = $value;
    }

    /**
     * Return modal view field value
     *
     * @param string $name
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
     * Load data for the indicated where.
     *
     * @param DataBaseWhere[] $where  filters to apply to model records.
     * @param array           $order  fields to use in the sorting. For example ['code' => 'ASC']
     * @param int             $offset
     * @param int             $limit
     *
     * @return self[]
     */
    public function all(array $where, array $order = [], int $offset = 0, int $limit = 0)
    {
        $result = [];
        if ($this->checkTables()) {
            $class = get_class($this);
            $sql = 'SELECT ' . $this->fieldsList()
                . ' FROM ' . $this->getSQLFrom()
                . DataBaseWhere::getSQLWhere($where)
                . $this->getGroupBy()
                . $this->getOrderBy($order);
            foreach (self::$dataBase->selectLimit($sql, $limit, $offset) as $d) {
                $result[] = new $class($d);
            }
        }
        return $result;
    }

    /**
     * Check list of tables required.
     *
     * @return bool
     */
    private function checkTables(): bool
    {
        $result = true;
        foreach ($this->getTables() as $tableName) {
            if (!self::$dataBase->tableExists($tableName)) {
                $result = false;
                break;
            }
        }
        return $result;
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        foreach (array_keys($this->getFields()) as $field) {
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
        return ($count == 1) ? $data[0]['count_total'] : $count;
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
    protected function getGroupBy(): string
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
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        return '';
    }
}
