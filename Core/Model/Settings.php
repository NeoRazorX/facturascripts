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
 * Description of Settings
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class Settings
{

    use Base\ModelTrait {
        clear as traitClear;
        loadFromData as traitLoadFromData;
    }

    /**
     * Identificador del grupo de valores
     *
     * @var string
     */
    public $name;

    /**
     * Descripción del contenido y valor del grupo
     *
     * @var string
     */
    public $description;

    /**
     * Icono a visualizar
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
        return 'fs_settings';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'name';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->traitClear();
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
            if (!in_array($key, ['name', 'action', 'active'])) {
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
        $this->traitLoadFromData($data, ['properties', 'action']);
        $this->properties = isset($data['properties']) ? json_decode($data['properties'], true) : [];
    }

    /**
     * Actualiza los datos del modelo en la base de datos.
     *
     * @return bool
     */
    public function save()
    {
        $this->properties = json_encode($this->properties);

        if ($this->test()) {
            if ($this->exists()) {
                return $this->saveUpdate();
            }

            return $this->saveInsert();
        }

        return false;
    }
}
