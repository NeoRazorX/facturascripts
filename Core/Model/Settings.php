<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
     * Conjunto de valores de configuración
     * 
     * @var json 
     */
    public $properties;
    
    public function tableName()
    {
        return 'fs_settings';
    }

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
     * Carga los datos desde un array
     *
     * @param array $data
     */
    public function loadFromData($data)
    {
        $this->loadFromDataTrait($data, ['properties']);
        $this->properties = empty($data['properties']) ? [] : json_decode($data['properties'], true);
    }
    
    /**
     * Actualiza los datos del modelo en la base de datos.
     *
     * @return bool
     */
    private function saveUpdate()
    {
        $properties = json_encode($this->properties);

        $sql = 'UPDATE ' . $this->tableName() . ' SET '
            . '  properties = ' . $this->var2str($properties)
            . ' WHERE ' . $this->primaryColumn() . ' = ' . $this->var2str($this->name) . ';';

        return $this->dataBase->exec($sql);
    }
    
    /**
     * Crea la consulta necesaria para crear configuración base en la base de datos.
     *
     * @return string
     */
    public function install()
    {
//        $description = 'General application settings. In this section you can customize the different visual aspects and behaviors of the application depending on the needs of the installation, such as currency, numerical formats and dates, etc.';
        $default = [
                'decimals' => 2, 'product_decimals' => 2, 'decimal_separator' => ',', 'thousands_separator' => '.',
                'dateshort' => 'dd-mm-yy', 'datelong' => 'dd mmm yyyy', 'datemaxtoday' => 3
            ];
        $properties = json_encode($default);
        
        return "INSERT INTO " . $this->tableName() . " (name, properties) VALUES ('default', " . $this->var2str($properties) . ");";
    }    
}
