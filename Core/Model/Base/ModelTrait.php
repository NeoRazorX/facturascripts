<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Template\ExtensionsTrait;

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
    public function getModelFields(): array
    {
        return static::$fields;
    }

    /**
     * Returns the name of the class of the model.
     *
     * @return string
     */
    public function modelClassName(): string
    {
        $result = explode('\\', $this->modelName());
        return end($result);
    }

    /**
     * Returns the name of the model.
     *
     * @return string
     */
    protected function modelName(): string
    {
        return get_class($this);
    }

    /**
     * Loads table fields if is necessary.
     *
     * @param DataBase $dataBase
     * @param string $tableName
     */
    protected function loadModelFields(DataBase &$dataBase, string $tableName): void
    {
        if (static::$fields) {
            return;
        }

        // read from the cache
        $key = 'model-fields-' . $this->modelClassName();
        static::$fields = Cache::get($key);
        if (is_array(static::$fields) && static::$fields) {
            return;
        }

        // table exists?
        if (false === $dataBase->tableExists($tableName)) {
            static::$fields = [];
            return;
        }

        // get from the database and store on the cache
        static::$fields = $dataBase->getColumns($tableName);
        Cache::set($key, static::$fields);
    }
}
