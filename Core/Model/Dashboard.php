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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model;

/**
 * Components to use into the Dashboard of FacturaScripts.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Dashboard extends Base\ModelClass
{
    use Base\ModelTrait;

    /**
     * Name of visual component
     *
     * @var string
     */
    public $component;

    /**
     * id version of component.
     *
     * @var string
     */
    public $version;

    /**
     * Position into dashboard.
     *
     * @var integer
     */
    public $location;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'dashboard';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'component';
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
        return parent::url($type, '');
    }

    /**
     * This function is called when creating the model table.
     * Returns the SQL that will be executed after the creation of the table,
     * useful to insert default values.
     *
     * @return string
     */
    public function install()
    {
        return 'INSERT INTO ' . static::tableName() . " (component, version, location) VALUES ('Messages','1.0 (Beta)', 0);"
            . 'INSERT INTO ' . static::tableName() . " (component, version, location) VALUES ('Tasks','1.0 (Beta)', 0);"
            . 'INSERT INTO ' . static::tableName() . " (component, version, location) VALUES ('InfoState','1.0 (Beta)', 0);";
    }
}
