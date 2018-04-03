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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model\Base;

use FacturaScripts\Core\Base\DataBase;

/**
 * The class from which all models inherit, connects to the database,
 * check the structure of the table and if necessary create or adapt.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait ModelTrait
{

    /**
     * List of fields in the table.
     *
     * @var array
     */
    protected static $fields;

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    abstract public static function primaryColumn();

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
            } elseif (isset($data[$field]) && $data[$field] === '---null---') {
                /// ---null--- text comes from widgetItemSelect.
                $data[$field] = null;
            }
        }
    }

    /**
     * Returns the list of fields in the table.
     *
     * @return array
     */
    public function getModelFields()
    {
        return static::$fields;
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
        return get_class($this);
    }

    /**
     * Loads table fields if is necessary.
     *
     * @param DataBase  $dataBase
     * @param string    $tableName
     */
    protected function loadModelFields(&$dataBase, $tableName)
    {
        if (empty(self::$fields)) {
            self::$fields = ($dataBase->tableExists($tableName) ? $dataBase->getColumns($tableName) : []);
        }
    }
}
