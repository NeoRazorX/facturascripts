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
namespace FacturaScripts\Core\Base\ViewController;

/**
 * Description of FieldOptions
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class FieldOptions
{
    /**
     * Nombre del campo del modelo
     * @var string
     */
    public $name;
    
    /**
     * Indica que el campo es obligatorio y debe contener un valor
     * @var boolean
     */
    public $required;
    
    /**
     * Indica que el campos es no editable
     * @var boolean
     */
    public $readOnly;
 
    /**
     * Indica si se puede hacer click en el valor del campo
     * @var boolean
     */
    public $clickable;
    
    /**
     * Icono que se usa como sustitutivo del valor del campo
     * o como acompañante del widget de edición
     * @var string
     */
    public $icon;
    
    /**
     * Constructor de la clase. Si se informa un array se cargan los datos
     * informados en el nuevo objeto
     * @param array $data
     */
    public function __construct($data = [])
    {
        if (empty($data)) {
            $this->init();
        } else {
            $this->loadFromData($data);
        }
    }
    
    /**
     * Inicializa la clase con valores nulos
     */
    private function init() {
        $this->name = null;
        $this->required = FALSE;
        $this->readOnly = FALSE;
        $this->clickable = FALSE;
        $this->icon = null;
    }
    
    /**
     * Inicializa la clase con los datos de un array
     * @param array $data
     */
    private function loadFromData($data) {
        $this->name = $data['name'];
        $this->required = $data['required'];
        $this->readOnly = $data['readonly'];
        $this->clickable = $data['clickable'];
        $this->icon = $data['icon'];
    }
}
