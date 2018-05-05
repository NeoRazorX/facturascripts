<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
    private static $dataBase;

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
     * Reset the values of all model properties.
     */
    protected function clear()
    {
        foreach (array_keys($this->getFields()) as $field) {
            $this->values[$field] = null;
        }
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
     * Return Group By clausule
     *
     * @return string
     */
    protected function getGroupBy(): string
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
     * Returns the number of records that meet the condition.
     *
     * @param DataBaseWhere[] $where filters to apply to records.
     *
     * @return int
     */
    public function count(array $where = [])
    {
        $sql = 'SELECT COUNT(1) AS total FROM ' . $this->getSQLFrom() . DataBaseWhere::getSQLWhere($where);
        $data = self::$dataBase->select($sql);
        return empty($data) ? 0 : $data[0]['total'];
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
            $sqlWhere = DataBaseWhere::getSQLWhere($where);
            $sqlOrderBy = $this->getOrderBy($order);
            $sql = 'SELECT ' . $this->fieldsList()
                . ' FROM ' . $this->getSQLFrom()
                . $sqlWhere
                . ' '
                . $this->getGroupBy()
                . ' '
                . $sqlOrderBy;
            foreach (self::$dataBase->selectLimit($sql, $limit, $offset) as $d) {
                $result[] = new $class($d);
            }
        }
        return $result;
    }
}
