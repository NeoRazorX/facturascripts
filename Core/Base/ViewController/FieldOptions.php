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
     * Constructor de la clase. Si se informa un array se cargan los datos
     * informados en el nuevo objeto
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Inicializa la clase con valores nulos
     */
    public function init()
    {
        $this->name = null;
        $this->required = FALSE;
        $this->readOnly = FALSE;
        $this->clickable = FALSE;
    }

    /**
     * 
     * @param SimpleXMLElement $column
     */
    public function loadFromXMLColumn($column)
    {
        $field_atributes = $column->field->attributes();
        $this->name = (string) $column->field;
        $this->required = (bool) $field_atributes->required;
        $this->readOnly = (bool) $field_atributes->readonly;
        $this->clickable = (bool) $field_atributes->clickable;
    }

    /**
     * 
     * @param array $column
     */
    public function loadFromJSONColumn($column)
    {
        $this->name = (string) $column['field']['name'];
        $this->required = (bool) $column['field']['required'];
        $this->readOnly = (bool) $column['field']['readOnly'];
        $this->clickable = (bool) $column['field']['clickable'];
    }
}
