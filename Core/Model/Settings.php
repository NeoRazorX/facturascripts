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
        clear as clearTrait;
        loadFromData as loadFromDataTrait;
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
     * Conjunto de valores de configuración
     *
     * @var array
     */
    public $properties;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'fs_settings';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'name';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearTrait();
        $this->properties = [];
    }

    /**
     * Comprueba un array de datos para que tenga la estructura correcta del modelo
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
     * Carga los datos desde un array
     *
     * @param array $data
     */
    public function loadFromData($data)
    {
        $this->loadFromDataTrait($data, ['properties', 'action']);
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
