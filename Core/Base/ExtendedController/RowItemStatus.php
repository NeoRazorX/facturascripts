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
namespace FacturaScripts\Core\Base\ExtendedController;

/**
 * Description of RowItemStatus
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class RowItemStatus extends RowItem
{
    /**
     * Nombre del campo que al que se aplica las opciones
     *
     * @var string
     */
    public $fieldName;

    /**
     * Opciones para configurar la fila
     *
     * @var array
     */
    public $options;
    
    /**
     * Constructor de la clase
     */
    public function __construct()
    {
        parent::__construct('status');
        $this->fieldName = '';
        $this->options = [];
    }
    
    /**
     * Carga la estructura de atributos en base a un archivo XML
     *
     * @param \SimpleXMLElement $row
     */
    public function loadFromXML($row)
    {
        $row_atributes = $row->attributes();
        $this->fieldName = (string) $row_atributes->fieldname;

        foreach ($row->option as $option) {
            $values = $this->getAttributesFromXML($option);
            $values['actions'] = isset($option->action) ? $this->getActionsFromXML($option->action) : [];            
            $this->options[] = $values;
            unset($values);
        }
    }    
    
    /**
     * Carga la estructura de atributos en base un archivo JSON
     *
     * @param array $row
     */
    public function loadFromJSON($row)
    {
        $this->type = (string) $row['type'];
        $this->fieldName = (string) $row['fieldName'];
        $this->options = (array) $row['options'];
    }    
    
    /**
     * Devuelve el estado del valor
     *
     * @param string $value
     *
     * @return string
     */
    public function getStatus($value)
    {
        foreach ($this->options as $option) {
            if ($option['value'] == $value) {
                return $option['color'];
            }

            $operator = $option['value'][0];
            $value2 = (float) substr($option['value'], 1);
            if ($operator == '>' && $value > $value2) {
                return $option['color'];
            }

            if ($operator == '<' && $value < $value2) {
                return $option['color'];
            }
        }

        return 'table-light';
    }    
}
