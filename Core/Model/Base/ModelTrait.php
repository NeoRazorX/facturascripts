<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\ExtensionsTrait;

/**
 * The class from which all models inherit, connects to the database,
 * check the structure of the table and if necessary create or adapt.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait ModelTrait
{

    use ExtensionsTrait;

    /**
     * List of fields in the table.
     *
     * @var array
     */
    protected static $fields = [];

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
    protected function modelName()
    {
        return get_class($this);
    }

    /**
     * Loads table fields if is necessary.
     *
     * @param DataBase $dataBase
     * @param string   $tableName
     */
    protected function loadModelFields(DataBase &$dataBase, string $tableName)
    {
        if (empty(static::$fields)) {
            static::$fields = $dataBase->tableExists($tableName) ? $dataBase->getColumns($tableName) : [];
        }
    }
}
