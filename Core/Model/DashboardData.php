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
namespace FacturaScripts\Core\Model;

/**
 * Components data to show into the Dashboard of FacturaScripts.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class DashboardData
{

    use Base\ModelTrait {
        clear as private traitClear;
        loadFromData as traitLoadFromData;
        saveInsert as traitSaveInsert;
        saveUpdate as traitSaveUpdate;
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
        return 'fs_dashboard_data';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
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
        $this->traitClear();
        $this->creationdate = date('d-m-Y');
        $this->displaydate = date('d-m-Y');
        $this->properties = [];
    }

    /**
     * Check that a data array have correct struct of model
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
     * Load data from array
     *
     * @param array $data
     */
    public function loadFromData($data)
    {
        $this->traitLoadFromData($data, ['properties']);
        $this->properties = isset($data['properties']) ? json_decode($data['properties'], true) : [];
    }

    /**
     * Insert the model data in the database.
     *
     * @return bool
     */
    private function saveInsert()
    {
        $values = ['properties' => json_encode($this->properties)];
        return $this->traitSaveInsert($values);
    }

    /**
     * Update the model data in the database.
     *
     * @return bool
     */
    private function saveUpdate()
    {
        $values = ['properties' => json_encode($this->properties)];
        return $this->traitSaveUpdate($values);
    }

    /**
     * Returns the url where to see/modify the data.
     *
     * @param string $type
     *
     * @return string
     */
    public function url($type = 'auto')
    {
        $value = $this->primaryColumnValue();
        $model = $this->modelClassName();
        $result = 'index.php?page=';

        switch ($type) {
            case 'edit':
                $result .= 'Edit' . $model . '&code=' . $value;
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
