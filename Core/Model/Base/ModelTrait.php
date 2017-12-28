<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\Cache;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseTools;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Base\Utils;

/**
 * The class from which all models inherit, connects to the database,
 * check the structure of the table and if necessary create or adapt.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait ModelTrait
{

    use Utils;

    /**
     * List of fields in the table.
     *
     * @var array
     */
    protected static $fields;

    /**
     * Name of the model From the class that initiates this trait.
     *
     * @var string
     */
    private static $modelName;

    /**
     * List of already tested tables.
     *
     * @var array|null
     */
    private static $checkedTables;

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

    /**
     * It allows to connect and interact with the cache system.
     *
     * @var Cache
     */
    protected static $cache;

    /**
     * Multi-language translator.
     *
     * @var Translator
     */
    protected static $i18n;

    /**
     * Manage the log of all controllers, models and database.
     *
     * @var MiniLog
     */
    protected static $miniLog;

    /**
     * ModelTrait constructor.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        $this->init();
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Initialize what is necessary.
     */
    private function init()
    {
        if (self::$cache === null) {
            self::$cache = new Cache();
            self::$dataBase = new DataBase();
            self::$i18n = new Translator();
            self::$miniLog = new MiniLog();
        }

        if (self::$checkedTables === null) {
            self::$checkedTables = self::$cache->get('fs_checked_tables');
            if (self::$checkedTables === null || self::$checkedTables === false) {
                self::$checkedTables = [];
            }

            self::$modelName = get_class($this);
        }

        if (static::tableName() !== '' && !in_array(static::tableName(), self::$checkedTables, false) && $this->checkTable(static::tableName())) {
            self::$miniLog->debug(self::$i18n->trans('table-checked', ['%tableName%' => static::tableName()]));
            self::$checkedTables[] = static::tableName();
            self::$cache->set('fs_checked_tables', self::$checkedTables);
        }

        if (self::$fields === null) {
            self::$fields = (self::$dataBase->tableExists(static::tableName()) ? self::$dataBase->getColumns(static::tableName()) : []);
        }
    }

    /**
     * This function is called when creating the model table. Returns the SQL
           * that will be executed after the creation of the table. Useful to insert values
           * default.
     *
     * @return string
     */
    public function install()
    {
        if (method_exists(__CLASS__, 'cleanCache')) {
            $this->cleanCache();
        }

        return '';
    }

    /**
     * Returns the name of the class of the model.
     *
     * @return string
     */
    public function modelClassName()
    {
        $result = explode('\\', $this->modelName());
        return end($result);
    }

    /**
     * Returns the name of the model.
     *
     * @return string
     */
    public function modelName()
    {
        return self::$modelName;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    abstract public function primaryColumn();

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
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    abstract public function tableName();

    /**
     * Check an array of data so that it has the correct structure of the model.
     *
     * @param array $data
     */
    public function checkArrayData(&$data)
    {
        foreach (self::$fields as $field => $values) {
            if (in_array($values['type'], ['boolean', 'tinyint(1)']) && !isset($data[$field])) {
                $data[$field] = false;
            } elseif(isset($data[$field]) && $data[$field] === '---null---') {
                /// ---null--- text comes from widgetItemSelect.
                $data[$field] = null;
            }
        }
    }

    /**
     * Returns the integer value by controlling special cases for the PK and FK.
     *
     * @param array $field
     * @param string $value
     * @return integer|NULL
     */
    private function getIntergerValueForField($field, $value)
    {
        if (!empty($value)) {
            return (int) $value;
        }

        if ($field['name'] === $this->primaryColumn()) {
            return null;
        }

        return ($field['is_nullable'] === 'NO') ? 0 : null;
    }

    /**
     * Assign the values of the $data array to the model properties.
     *
     * @param array $data
     * @param string[] $exclude
     */
    public function loadFromData(array $data = [], array $exclude = [])
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }

            if (isset(self::$fields[$key])) {
                $field = self::$fields[$key];

                // We check if it is a varchar (with established length) or another type of data
                $type = (strpos($field['type'], '(') === false) ? $field['type'] : substr($field['type'], 0, strpos($field['type'], '('));

                switch ($type) {
                    case 'tinyint':
                    case 'boolean':
                        $this->{$key} = $this->str2bool($value);
                        break;

                    case 'integer':
                    case 'int':
                        $this->{$key} = $this->getIntergerValueForField($field, $value);
                        break;

                    case 'double':
                    case 'double precision':
                    case 'float':
                        $this->{$key} = empty($value) ? 0.00 : (float) $value;
                        break;

                    case 'date':
                        $this->{$key} = empty($value) ? null : date('d-m-Y', strtotime($value));
                        break;

                    default:
                        if ($value === null && $field['is_nullable'] === 'NO') {
                            $value = '';
                        }
                        $this->{$key} = $this->fixHtml($value);
                }
            } else {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        foreach (self::$fields as $field) {
            $this->{$field['name']} = null;
        }
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
     * @param DataBase\DataBaseWhere[] $where
     * @param array $orderby
     *
     * @return bool
     */
    public function loadFromCode($cod, $where = null, $orderby = [])
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
     * Returns the model whose primary column corresponds to the value $cod
     *
     * @param string $cod
     *
     * @return mixed|bool
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
     * Returns true if the model data is stored in the database.
     *
     * @return bool
     */
    public function exists()
    {
        if ($this->primaryColumnValue() === null) {
            return false;
        }

        $sql = 'SELECT 1 FROM ' . static::tableName()
            . ' WHERE ' . $this->primaryColumn() . ' = ' . self::$dataBase->var2str($this->primaryColumnValue()) . ';';

        return (bool) self::$dataBase->select($sql);
    }

    /**
     * Returns true if there are no errors in the values of the model properties.
           * It runs inside the save method.
     *
     * @return bool
     */
    public function test()
    {
        return true;
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
     * Remove the model data from the database.
     *
     * @return bool
     */
    public function delete()
    {
        if (method_exists(__CLASS__, 'cleanCache')) {
            $this->cleanCache();
        }
        $sql = 'DELETE FROM ' . static::tableName()
            . ' WHERE ' . $this->primaryColumn() . ' = ' . self::$dataBase->var2str($this->primaryColumnValue()) . ';';

        return self::$dataBase->exec($sql);
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
        if (empty($data)) {
            return 0;
        }

        return $data[0]['total'];
    }

    /**
     * Returns all models that correspond to the selected filters.
     *
     * @param DataBase\DataBaseWhere[] $where  filters to apply to model records.
     * @param array $order  fields to use in the sorting. For example ['code' => 'ASC']
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function all(array $where = [], array $order = [], $offset = 0, $limit = 50)
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
     * Returns the following code for the reported field or the primary key of the model.
     *
     * @param string $field
     *
     * @return int
     */
    public function newCode($field = '')
    {
        if (empty($field)) {
            $field = self::$dataBase->sql2Int($this->primaryColumn());
        }
        $sql = 'SELECT MAX(' . $field . ') as cod FROM ' . static::tableName() . ';';
        $cod = self::$dataBase->select($sql);
        if (empty($cod)) {
            return 1;
        }

        return 1 + (int) $cod[0]['cod'];
    }

    /**
     * Check and update the structure of the table if necessary.
     *
     * @param string $tableName
     *
     * @return bool
     */
    private function checkTable($tableName)
    {
        $dbTools = new DataBaseTools();
        $sql = '';
        $xmlCols = [];
        $xmlCons = [];

        if (!$dbTools->getXmlTable($tableName, $xmlCols, $xmlCons)) {
            self::$miniLog->critical(self::$i18n->trans('error-on-xml-file'));
            return false;
        }

        if (self::$dataBase->tableExists($tableName)) {
            $sql .= $dbTools->checkTable($tableName, $xmlCols, $xmlCons);
        } else {
            /// we generate the sql to create the table
            $sql .= $dbTools->generateTable($tableName, $xmlCols, $xmlCons);
            $sql .= $this->install();
        }

        if ($sql !== '' && !self::$dataBase->exec($sql)) {
            self::$miniLog->critical(self::$i18n->trans('check-table', ['%tableName%' => $tableName]));
            self::$cache->clear();
            return false;
        }

        return true;
    }

    /**
     * Read the record whose primary column corresponds to the value $cod
           * or the first that meets the indicated condition
     *
     * @param string $cod
     * @param array|null $where
     * @param array $orderby
     *
     * @return array
     */
    private function getRecord($cod, $where = null, $orderby = [])
    {
        $sqlWhere = empty($where) ? ' WHERE ' . $this->primaryColumn() . ' = ' . self::$dataBase->var2str($cod) : DataBase\DataBaseWhere::getSQLWhere($where);

        $sql = 'SELECT * FROM ' . static::tableName() . $sqlWhere . $this->getOrderBy($orderby);
        return self::$dataBase->selectLimit($sql, 1);
    }

    /**
     * Update the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    private function saveUpdate($values = [])
    {
        $sql = 'UPDATE ' . static::tableName();
        $coma = ' SET';

        foreach (self::$fields as $field) {
            if ($field['name'] !== $this->primaryColumn()) {
                $fieldName = $field['name'];
                $fieldValue = isset($values[$fieldName]) ? $values[$fieldName] : $this->{$fieldName};
                $sql .= $coma . ' ' . $fieldName . ' = ' . self::$dataBase->var2str($fieldValue);
                if ($coma === ' SET') {
                    $coma = ', ';
                }
            }
        }

        $sql .= ' WHERE ' . $this->primaryColumn() . ' = ' . self::$dataBase->var2str($this->primaryColumnValue()) . ';';

        return self::$dataBase->exec($sql);
    }

    /**
     * Insert the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    private function saveInsert($values = [])
    {
        $insertFields = [];
        $insertValues = [];
        foreach (self::$fields as $field) {
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
                $this->{$this->primaryColumn()} = self::$dataBase->lastval();
            }

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
            if ($coma === ' ORDER BY ') {
                $coma = ', ';
            }
        }

        return $result;
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url($type = 'auto', $list = 'List')
    {
        $value = $this->primaryColumnValue();
        $model = $this->modelClassName();
        $result = 'index.php?page=';
        switch ($type) {
            case 'list':
                $result .= $list . $model;
                break;

            case 'edit':
                $result .= 'Edit' . $model . '&code=' . $value;
                break;

            case 'new':
                $result .= 'Edit' . $model;
                break;

            default:
                $result .= empty($value) ? $list . $model : 'Edit' . $model . '&code=' . $value;
                break;
        }

        return $result;
    }
}
