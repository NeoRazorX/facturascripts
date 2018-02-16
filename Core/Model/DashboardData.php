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
 * Components data to show into the Dashboard of FacturaScripts.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class DashboardData extends Base\ModelClass
{

    use Base\ModelTrait {
        loadFromData as traitLoadFromData;
    }

    /**
     * Primary key.
     *
     * @var int
     */
    public $id;

    /**
     * Name of visual component
     *
     * @var string
     */
    public $component;

    /**
     * Nick of the user to whom the card is addressed.
     *
     * @var string
     */
    public $nick;

    /**
     * Date creation of the card.
     *
     * @var string
     */
    public $creationdate;

    /**
     * Date from which to show the card.
     *
     * @var string
     */
    public $displaydate;

    /**
     * Set of configuration values
     *
     * @var array
     */
    public $properties;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'dashboard_data';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'id';
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
        new User();
        new Dashboard();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->creationdate = date('d-m-Y');
        $this->displaydate = date('d-m-Y');
        $this->properties = [];
    }

    /**
     * Check an array of data so that it has the correct structure of the model.
     *
     * @param array $data
     */
    public function checkArrayData(&$data)
    {
        $properties = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, ['id', 'nick', 'creationdate', 'displaydate', 'action'])) {
                $properties[$key] = $value;
                unset($data[$key]);
            }
        }
        $data['properties'] = json_encode($properties);
        unset($properties);
    }

    /**
     * Assign the values of the $data array to the model properties.
     *
     * @param array    $data
     * @param string[] $exclude
     */
    public function loadFromData(array $data = [], array $exclude = [])
    {
        $this->traitLoadFromData($data, ['properties']);
        $this->properties = isset($data['properties']) ? json_decode($data['properties'], true) : [];
    }

    /**
     * Insert the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert($values = [])
    {
        $values = ['properties' => json_encode($this->properties)];

        return parent::saveInsert($values);
    }

    /**
     * Update the model data in the database.
     *
     * @param array $values
     *
     * @return bool
     */
    protected function saveUpdate($values = [])
    {
        $values = ['properties' => json_encode($this->properties)];

        return parent::saveUpdate($values);
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
        $result = '';

        switch ($type) {
            case 'edit':
                $result .= 'Edit' . $model . '?code=' . $value;
                break;

            case 'new':
                $result .= 'Edit' . $model;
                break;

            default:
                $result .= 'Dashboard';
        }

        return $result;
    }
}
