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
 * Description of ListFilter
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListFilter
{

    /**
     * Indica el tipo de filtro
     *
     * @var string
     */
    public $type;

    /**
     * Valor actual del filtro
     *
     * @var string
     */
    public $value;

    /**
     * Opciones de configuración del filtro
     *
     * @var array
     */
    public $options;

    /**
     * Constructor de la clase
     *
     * @param string $type
     * @param string $value
     * @param array $options
     */
    public function __construct($type, $value, $options)
    {
        $this->type = $type;
        $this->value = $value;
        $this->options = $options;
    }

    /**
     * Lista de operadores disponibles
     * 
     * @return array
     */
    public function getFilterOperators()
    {
        return [
          'like-than' => '=',
          'greater-than' => '>=',
          'smaller-than' => '<=',
          'different-than' => '<>'
        ];        
    }
    
    /**
     * Devuelve la clase especial a aplicar al input del formulario de filtros
     * 
     * @return string
     */
    public function getSpecialClass()
    {
        switch ($this->type) {
            case 'datepicker':
                return 'datepicker';

            default:
                return '';
        }        
    }
    
    /**
     * Devuelve la función onkeypress (JavaScript)
     * en caso de que el input acepte sólo un conjunto de valores
     * 
     * @return string
     */
    public function getKeyboardFilter()
    {
        switch ($this->type) {
            case 'number':
                /// enter + number + ','
                return 'onkeypress="return event.charCode == 13 || '
                    . '(event.charCode >= 48 && event.charCode <= 57) || '
                    . ' event.charCode == 46"';

            case 'datepicker':
                /// enter + number + '-' + '/'
                return 'onkeypress="return event.charCode == 13 || '
                    . '(event.charCode >= 48 && event.charCode <= 57) || '
                    . ' event.charCode == 45 || '
                    . ' event.charCode == 47"';
                
            default:
                return '';
        }
    }
    
    /**
     * Crea y devuelve un filtro de tipo select
     *
     * @param string $field
     * @param string $value
     * @param string $table
     * @param DatabaseWhere $where
     * @return ListFilter
     */
    public static function newSelectFilter($field, $value, $table, $where)
    {
        $options = ['field' => $field, 'table' => $table, 'where' => $where];
        $result = new ListFilter('select', $value, $options);
        return $result;
    }

    /**
     * Crea y devuelve un filtro de tipo checkbox
     *
     * @param string $field
     * @param string $value
     * @param string $label
     * @param boolean $inverse
     * @return ListFilter
     */
    public static function newCheckboxFilter($field, $value, $label, $inverse)
    {
        $options = ['label' => $label, 'field' => $field, 'inverse' => $inverse];
        $result = new ListFilter('checkbox', $value, $options);
        return $result;
    }

    /**
     * Crea y devuelve un filtro de tipo indicado [text|number|datepicker]
     *
     * @param string $type
     * @param string $field
     * @param string $value
     * @param string $label
     * @param string $operator
     * @return ListFilter
     */
    public static function newStandardFilter($type, $field, $value, $label, $operator)
    {
        if ($type === 'number') {
            $values = explode(',', $value, 1);
            $value = count($values) === 1 ? $values[0] : $values[0] . '.' . $values[1];
        }
        
        $options = ['label' => $label, 'field' => $field, 'operator' => $operator];
        $result = new ListFilter($type, $value, $options);
        return $result;
    }
}
