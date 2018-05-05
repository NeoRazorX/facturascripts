<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Cache;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseTools;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Dinamic\Lib\Import\CSVImport;

/**
 * The class from which all models inherit, connects to the database,
 * check the structure of the table and if necessary create or adapt.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class ModelCore
{

    /**
     * It allows to connect and interact with the cache system.
     *
     * @var Cache
     */
    protected static $cache;

    /**
     * List of already tested tables.
     *
     * @var array
     */
    private static $checkedTables;

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected static $dataBase;

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
     * Returns the list of fields in the table.
     *
     * @return array
     */
    abstract protected function getModelFields();

    /**
     * Loads table fields if is necessary.
     *
     * @param DataBase  $dataBase
     * @param string    $tableName
     */
    abstract protected function loadModelFields(DataBase &$dataBase, string $tableName);

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
        if (self::$cache === null) {
            self::$cache = new Cache();
            self::$dataBase = new DataBase();
            self::$i18n = new Translator();
            self::$miniLog = new MiniLog();

            self::$checkedTables = self::$cache->get('fs_checked_tables');
            if (self::$checkedTables === null || self::$checkedTables === false) {
                self::$checkedTables = [];
            }
        }

        if (static::tableName() !== '' && !in_array(static::tableName(), self::$checkedTables, false) && $this->checkTable()) {
            self::$miniLog->debug(self::$i18n->trans('table-checked', ['%tableName%' => static::tableName()]));
            self::$checkedTables[] = static::tableName();
            self::$cache->set('fs_checked_tables', self::$checkedTables);
        }

        $this->loadModelFields(self::$dataBase, static::tableName());
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        foreach ($this->getModelFields() as $field) {
            $this->{$field['name']} = null;
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
            if (in_array($key, $exclude)) {
                continue;
            } elseif (!isset($fields[$key])) {
                $this->{$key} = $value;
                continue;
            }

            // We check if it is a varchar (with established length) or another type of data
            $field = $fields[$key];
            $type = (strpos($field['type'], '(') === false) ? $field['type'] : substr($field['type'], 0, strpos($field['type'], '('));

            switch ($type) {
                case 'tinyint':
                case 'boolean':
                    $this->{$key} = Utils::str2bool($value);
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
     * Check and update the structure of the table if necessary.
     *
     * @return bool
     */
    private function checkTable()
    {
        $dbTools = new DataBaseTools();
        $sql = '';
        $xmlCols = [];
        $xmlCons = [];

        if (!$dbTools->getXmlTable(static::tableName(), $xmlCols, $xmlCons)) {
            self::$miniLog->critical(self::$i18n->trans('error-on-xml-file'));

            return false;
        }

        if (self::$dataBase->tableExists(static::tableName())) {
            $sql .= $dbTools->checkTable(static::tableName(), $xmlCols, $xmlCons);
        } else {
            /// we generate the sql to create the table
            $sql .= $dbTools->generateTable(static::tableName(), $xmlCols, $xmlCons);
            $sql .= $this->install();
        }

        if ($sql !== '' && !self::$dataBase->exec($sql)) {
            self::$miniLog->critical(self::$i18n->trans('check-table', ['%tableName%' => static::tableName()]));
            self::$cache->clear();

            return false;
        }

        return true;
    }

    /**
     * Returns the integer value by controlling special cases for the PK and FK.
     *
     * @param array  $field
     * @param string $value
     *
     * @return integer|NULL
     */
    private function getIntergerValueForField($field, $value)
    {
        if (is_numeric($value)) {
            return (int) $value;
        }

        if ($field['name'] === static::primaryColumn()) {
            return null;
        }

        return ($field['is_nullable'] === 'NO') ? 0 : null;
    }
}
