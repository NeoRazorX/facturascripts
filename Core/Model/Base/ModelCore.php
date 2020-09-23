<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\DataBase\DataBaseTools;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Lib\Import\CSVImport;

/**
 * The class from which all models inherit, connects to the database,
 * check the structure of the table and if necessary create or adapt.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class ModelCore
{

    const DATE_STYLE = 'd-m-Y';
    const DATETIME_STYLE = 'd-m-Y H:i:s';
    const HOUR_STYLE = 'H:i:s';

    /**
     * List of already tested tables.
     *
     * @var array
     */
    private static $checkedTables = [];

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
    abstract public function getModelFields();

    /**
     * Loads table fields if is necessary.
     *
     * @param DataBase $dataBase
     * @param string   $tableName
     */
    abstract protected function loadModelFields(DataBase &$dataBase, string $tableName);

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
    abstract protected function modelName();

    /**
     * Executes all $name methods added from the extensions.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    abstract public function pipe($name, ...$arguments);

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    abstract public static function primaryColumn();

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    abstract public static function tableName();

    /**
     * ModelClass constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        if (self::$dataBase === null) {
            self::$dataBase = new DataBase();

            $tables = $this->toolBox()->cache()->get('fs_checked_tables');
            if (\is_array($tables) && !empty($tables)) {
                self::$checkedTables = $tables;
            }
        }

        if (static::tableName() !== '' && false === \in_array(static::tableName(), self::$checkedTables, false) && $this->checkTable()) {
            $this->toolBox()->i18nLog()->debug('table-checked', ['%tableName%' => static::tableName()]);
            self::$checkedTables[] = static::tableName();
            $this->toolBox()->cache()->set('fs_checked_tables', self::$checkedTables);
        }

        $this->loadModelFields(self::$dataBase, static::tableName());
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Change the value of the primary column in the model and the database.
     *
     * @param mixed $newValue
     *
     * @return bool
     */
    public function changePrimaryColumnValue($newValue)
    {
        if (empty($newValue) || $newValue == $this->primaryColumnValue()) {
            return true;
        }

        $sql = "UPDATE " . $this->tableName() . " SET " . $this->primaryColumn() . " = " . self::$dataBase->var2str($newValue)
            . " WHERE " . $this->primaryColumn() . " = " . self::$dataBase->var2str($this->primaryColumnValue()) . ";";
        if (self::$dataBase->exec($sql)) {
            $this->{$this->primaryColumn()} = $newValue;
            return true;
        }

        return false;
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        foreach (\array_keys($this->getModelFields()) as $fieldName) {
            $this->{$fieldName} = null;
        }

        $this->pipe('clear');
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
        return CSVImport::importTableSQL(static::tableName());
    }

    /**
     * Assign the values of the $data array to the model properties.
     *
     * @param array $data
     * @param array $exclude
     */
    public function loadFromData(array $data = [], array $exclude = [])
    {
        $fields = $this->getModelFields();
        foreach ($data as $key => $value) {
            if (\in_array($key, $exclude)) {
                continue;
            } elseif (!isset($fields[$key])) {
                $this->{$key} = $value;
                continue;
            }

            // We check if it is a varchar (with established length) or another type of data
            $field = $fields[$key];
            $type = \strpos($field['type'], '(') === false ? $field['type'] : \substr($field['type'], 0, \strpos($field['type'], '('));

            switch ($type) {
                case 'tinyint':
                case 'boolean':
                    $this->{$key} = $this->getBoolValueForField($field, $value);
                    break;

                case 'integer':
                case 'int':
                    $this->{$key} = $this->getIntergerValueForField($field, $value);
                    break;

                case 'decimal':
                case 'double':
                case 'double precision':
                case 'float':
                    $this->{$key} = $this->getFloatValueForField($field, $value);
                    break;

                case 'date':
                    $this->{$key} = empty($value) ? null : \date(self::DATE_STYLE, \strtotime($value));
                    break;

                case 'datetime':
                case 'timestamp':
                    $this->{$key} = empty($value) ? null : \date(self::DATETIME_STYLE, \strtotime($value));
                    break;

                default:
                    $this->{$key} = ($value === null && $field['is_nullable'] === 'NO') ? '' : $value;
            }
        }
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
     * Returns an array with the model fields values.
     *
     * @return array
     */
    public function toArray()
    {
        $data = [];
        foreach (\array_keys($this->getModelFields()) as $fieldName) {
            $data[$fieldName] = $this->{$fieldName};
        }

        return $data;
    }

    /**
     * Checks and updates the structure of the table if necessary.
     *
     * @return bool
     */
    private function checkTable()
    {
        $xmlCols = [];
        $xmlCons = [];
        if (false === DataBaseTools::getXmlTable(static::tableName(), $xmlCols, $xmlCons)) {
            $this->toolBox()->i18nLog()->critical('error-on-xml-file', ['%fileName%' => static::tableName() . '.xml']);
            return false;
        }

        $sql = self::$dataBase->tableExists(static::tableName()) ?
            DataBaseTools::checkTable(static::tableName(), $xmlCols, $xmlCons) :
            DataBaseTools::generateTable(static::tableName(), $xmlCols, $xmlCons) . $this->install();

        if ($sql !== '' && false === self::$dataBase->exec($sql)) {
            $this->toolBox()->i18nLog()->critical('check-table', ['%tableName%' => static::tableName()]);
            $this->toolBox()->cache()->clear();
            return false;
        }

        return true;
    }

    /**
     * Returns the boolean value for the field.
     *
     * @param array  $field
     * @param string $value
     *
     * @return bool|null
     */
    private function getBoolValueForField($field, $value)
    {
        if (\in_array(\strtolower($value), ['true', 't', '1'], false)) {
            return true;
        } elseif (\in_array(\strtolower($value), ['false', 'f', '0'], false)) {
            return false;
        }

        return $field['is_nullable'] === 'NO' ? false : null;
    }

    /**
     * Returns the float value for the field.
     *
     * @param array  $field
     * @param string $value
     *
     * @return float|null
     */
    private function getFloatValueForField($field, $value)
    {
        if (\is_numeric($value)) {
            return (float) $value;
        }

        return $field['is_nullable'] === 'NO' ? 0.0 : null;
    }

    /**
     * Returns the integer value by controlling special cases for the PK and FK.
     *
     * @param array  $field
     * @param string $value
     *
     * @return int|null
     */
    private function getIntergerValueForField($field, $value)
    {
        if (\is_numeric($value)) {
            return (int) $value;
        }

        if ($field['name'] === static::primaryColumn()) {
            return null;
        }

        return $field['is_nullable'] === 'NO' ? 0 : null;
    }

    /**
     *
     * @return ToolBox
     */
    protected static function toolBox()
    {
        return new ToolBox();
    }
}
