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
namespace FacturaScripts\Core\Base\ExtendedController;

use FacturaScripts\Core\Model as Model;

/**
 * Description of DataView
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class DataView
{
    /**
     * Constantes para ordenación
     */
    const ICON_ASC = 'fa-sort-amount-asc';
    const ICON_DESC = 'fa-sort-amount-desc';
    
    /**
     * Modelo principal de datos.
     * Necesario para llamadas a los métodos del modelo.
     * 
     * @var mixed
     */
    private $model;
    
    /**
     * Cursor con los datos del modelo a mostrar
     * 
     * @var array
     */
    private $cursor;
        
    /**
     * Configuración de columnas por usuario
     * @var Model\PageOption
     */
    private $pageOption;    

    /**
     * Configuración de filtros predefinidos por usuario
     * 
     * @var array
     */
    private $filters;
    
    /**
     * Lista de campos donde buscar cuando se aplica una búsqueda
     * 
     * @var array
     */
    private $searchIn;

    /**
     * Lista de campos disponibles en el order by
     * Ejemplo: orderby[key] = ["label" => "Etiqueta", "icon" => ICON_ASC]
     *          key = field_asc | field_desc
     * @var array
     */
    private $orderby;

    /**
     * Elemento seleccionado en el lista de order by
     * @var string
     */
    public $selectedOrderBy;
    
    /**
     * Número total de registros leídos
     * @var int
     */
    public $count;
    
    /**
     * Título identificativo de la vista
     * 
     * @var string
     */
    public $title;
    
    /**
     * Constructor e inicializador de la clase
     * 
     * @param string $title
     * @param string $modelName
     * @param string $viewName
     * @param string $userNick
     */
    public function __construct($title, $modelName, $viewName, $userNick)
    {
        $this->title = $title;
        $this->model = new $modelName;
        $this->cursor = NULL;
        $this->orderby = [];
        $this->filters = [];
        $this->searchIn = [];
        $this->count = 0;
        $this->selectedOrderBy = '';
        
        // Carga configuración de la vista para el usuario
        $this->pageOption = new Model\PageOption();
        $this->pageOption->getForUser($viewName, $userNick);        
    }
        
    /**
     * Devuelve la lista de datos leidos en formato Model
     * 
     * @return array
     */
    public function getCursor()
    {
        return $this->cursor;
    }
    
    /**
     * Devuelve la lista de filtros definidos
     * 
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }
    
    /**
     * Devuelve la lista de campos para la búsqueda en formato para WhereDatabase
     * 
     * @return string
     */
    public function getSearchIn()
    {
        return implode("|", $this->searchIn);
    }
        
    /**
     * Devuelve la lista de order by definidos
     * 
     * @return array
     */
    public function getOrderBy()
    {
        return $this->orderby;
    }
    
    /**
     * Lista de columnas y su configuración
     * (Array of ColumnItem)
     * @return array
     */
    public function getColumns()
    {
        return $this->pageOption->columns[0]->columns;
    }

    /**
     * 
     * @param string $key
     * @return array
     */
    public function getRow($key)
    {
        return empty($this->pageOption->rows) ? NULL : $this->pageOption->rows[$key];
    }
        
    /**
     * Devuelve la url del modelo del tipo solicitado
     * 
     * @param string $type      (edit / list / auto)
     * @return string
     */
    public function getURL($type)
    {
        return $this->model->url($type);
    }
    
    /**
     * Devuelve el identificador del modelo
     * 
     * @return string
     */
    public function getModelID()
    {
        return $this->model->modelClassName();
    }
    
    /**
     * Devuelve el Order By indicado en formato array
     * @param string $orderKey
     */
    public function getSQLOrderBy($orderKey = '')
    {
        if ($orderKey == '') {
            $orderKey = array_keys($this->orderby)[0];
        }

        $orderby = explode('_', $orderKey);
        return [$orderby[0] => $orderby[1]];
    }
    
    /**
     * Comprueba y establece el valor seleccionado en el order by
     * @param string $orderKey
     * @return string
     */
    public function setSelectedOrderBy($orderKey)
    {
        $keys = array_keys($this->orderby);
        if (empty($orderKey) || !in_array($orderKey, $keys)) {
            if (empty($this->selectedOrderBy)) {
                $this->selectedOrderBy = $keys[0]; // Forzamos el primer elemento cuando no hay valor por defecto
            }
        } else {
            $this->selectedOrderBy = $orderKey;
        }
    }
    
    /**
     * Añade a la lista de campos para la búsqueda los campos informados
     * 
     * @param array $fields
     */
    public function addSearchIn($fields)
    {
        if (is_array($fields)) {
            $this->searchIn += $fields;
        }
    }
    
    /**
     * Añade un campo a la lista de Order By
     * @param string $field
     * @param string $label
     * @param int $default    (0 = None, 1 = ASC, 2 = DESC)
     */
    public function addOrderBy($field, $label = '', $default = 0)
    {
        $key1 = strtolower($field) . '_asc';
        $key2 = strtolower($field) . '_desc';
        if (empty($label)) {
            $label = ucfirst($field);
        }

        $this->orderby[$key1] = ['icon' => self::ICON_ASC, 'label' => $label];
        $this->orderby[$key2] = ['icon' => self::ICON_DESC, 'label' => $label];
        
        switch ($default) {
            case 1:
                $this->setSelectedOrderBy($key1);
                break;
            
            case 2:
                $this->setSelectedOrderBy($key2);
                break;

            default:
                break;
        }
    } 
    
    /**
     * Define una nueva opción de filtrado para los datos
     * @param string $type    (option: 'select', 'checkbox')
     * @param string $key     (Filter identification)
     * @param string $value   (Value introduced by user, if there are)
     * @param array  $options (Filter options needed for run)
     */
    private function addFilter($type, $key, $value, $options)
    {
        if (empty($options['field'])) {
            $options['field'] = $key;
        }

        $this->filters[$key] = ['type' => $type, 'value' => $value, 'options' => $options];
    }

    /**
     * Add a filter type data table selection
     * Añade un filtro de tipo selección en tabla
     * @param string $key      (Filter identifier)
     * @param string $value    (Value introduced by user, if there are)
     * @param string $table    (Table name)
     * @param string $where    (Where condition for table)
     * @param string $field    (Field of the table with the data to show)
     */
    public function addFilterSelect($key, $value, $table, $where = '', $field = '')
    {
        $options = ['field' => $field, 'table' => $table, 'where' => $where];
        $this->addFilter('select', $key, $value, $options);
    }

    /**
     * Añade un filtro del tipo condición boleana
     * @param string  $key     (Filter identifier)
     * @param string  $value    (Value introduced by user, if there are)
     * @param string  $label   (Human reader description)
     * @param string  $field   (Field of the table to apply filter)
     * @param boolean $inverse (If you need to invert the selected value)
     */
    public function addFilterCheckbox($key, $value, $label, $field = '', $inverse = FALSE)
    {
        $options = ['label' => $label, 'field' => $field, 'inverse' => $inverse];
        $this->addFilter('checkbox', $key, $value, $options);
    }

    /**
     * @param string $key
     * @param string $label
     */
    public function addFilterDatePicker($key, $value, $label, $field = '')
    {
        $options = ['label' => $label, 'field' => $field];
        $this->addFilter('datepicker', $key, $value, $options);
    }
    
    public function loadData($where, $offset = 0, $limit = 50)
    {
        $order = $this->getSQLOrderBy($this->selectedOrderBy);
        $this->count = $this->model->count($where);
        if ($this->count > 0) {
            $this->cursor = $this->model->all($where, $order, $offset, $limit);
        }        
    }
    
    public function delete($code)
    {
        if ($this->model->loadFromCode($code)) {
            return $this->model->delete();
        }
        
        return FALSE;
    }
}
