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
 * Description of Settings
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Settings extends Base\ModelClass
{

    use Base\ModelTrait {
        loadFromData as traitLoadFromData;
    }

    /**
     * Identifier of the group of values.
     *
     * @var string
     */
    public $name;

    /**
     * Description of the content and value of the group.
     *
     * @var string
     */
    public $description;

    /**
     * Icon to visualize.
     *
     * @var string
     */
    public $icon;

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
        return 'settings';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'name';
    }

    /**
     * Returns no description.
     *
     * @return string
     */
    public function primaryDescription()
    {
        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
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
            if (!in_array($key, ['name', 'action', 'active'])) {
                $properties[$key] = $value;
                unset($data[$key]);
            }
        }
        $data['properties'] = json_encode($properties);
    }

    /**
     * Load data from array
     *
     * @param array $data
     * @param array $exclude
     */
    public function loadFromData(array $data = [], array $exclude = [])
    {
        $this->traitLoadFromData($data, ['properties', 'action']);
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
}
