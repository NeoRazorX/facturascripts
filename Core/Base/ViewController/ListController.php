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

use FacturaScripts\Core\Base as Base;
use FacturaScripts\Core\Model as Models;
use FacturaScripts\Core\Base\DataBase as DataBase;
    
/**
 * Controlador para listado de datos en modo tabla
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class ListController extends Base\Controller
{

    /**
     * Iconos para ordenación
     */
    const ICONO_ASC = 'glyphicon-sort-by-attributes';
    const ICONO_DESC = 'glyphicon-sort-by-attributes-alt';
    const FS_ITEM_LIMIT = 50;

    /**
     * Cursor con los datos a mostrar
     * @var array
     */
    public $cursor;

    /**
     * Configuración de columnas y filtros
     * @var Model\PageOption
     */
    private $pageOption;
    
    public $filters;
    public $fields;
    
    /**
     * Lista de campos disponibles en el order by
     * Ejemplo: orderby[key] = ["label" => "Etiqueta", "icon" => ICONO_ASC]
     *          key = field_asc | field_desc
     * @var array
     */
    public $orderby;

    /**
     * Elemento seleccionado en el lista de order by
     * @var string
     */
    public $selectedOrderBy;

    /**
     * Esta variable contiene el texto enviado como parámetro query
     * usado para el filtrado de datos del modelo
     * @var string|false
     */
    public $query;

    /**
     * Primer registro a seleccionar de la base de datos
     * @var int
     */
    protected $offset;

    /**
     * Número total de registros leídos
     * @var int
     */
    public $count;

    protected abstract function getColumns();

    /**
     * Inicia todos los objetos y propiedades.
     *
     * @param Cache $cache
     * @param Translator $i18n
     * @param MiniLog $miniLog
     * @param string $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->setTemplate("Master/ListController");

        $offset = $this->request->get('offset');
        $this->offset = $offset ? $offset : 0;
        $this->query = $this->request->get('query');
        $this->count = 0;
        $this->orderby = [];
        $this->filters = [];
        $this->fields = [];
        
        $this->pageOption = new Models\PageOption();
    }
    
    /**
     * Ejecuta la lógica privada del controlador.
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        // Cargamos configuración de columnas y filtros 
        $this->fields = $this->getColumns();
        
        $className = $this->getClassName();
        $this->pageOption->getForUser($className, $user->nick);
        
        // Establecemos el orderby seleccionado
        $orderKey = $this->request->get("order");
        $this->selectedOrderBy = empty($orderKey) ? (string) array_keys($this->orderby)[0] : $this->getSelectedOrder($orderKey);
    }
        
    /**
     * Devuelve la key del campo seleccionado en el order by
     * @param string $orderKey
     * @return string
     */
    private function getSelectedOrder($orderKey)
    {
        $result = '';
        $keys = array_keys($this->orderby);
        foreach ($keys as $item) {
            if ($item == $orderKey) {
                $result = $item;
                break;
            }
        }

        if ($result == '') {
            $result = $keys[0];
        }

        return $result;
    }


    /**
     * Establece la clausula WHERE según los filtros definidos
     * @return array
     */
    protected function getWhere()
    {
        $result = [];

        foreach (array_values($this->filters) as $value) {
            if ($value['value']) {
                switch ($value['type']) {
                    case 'datepicker':
                    case 'select':
                            $field = $value['options']['field'];
                            $result[] = new DataBase\DataBaseWhere($field, $value['value']);
                            break;

                    case 'checkbox':
                            $field = $value['options']['field'];
                            $value = $value['options']['inverse'] ? !$value['value'] : $value['value'];
                            $result[] = new DataBase\DataBaseWhere($field, $value);
                            break;
                }
            }
        }

        return $result;
    }

    /**
     * Devuelve el Order By indicado en formato array
     * @param type $orderKey
     */
    protected function getOrderBy($orderKey = '')
    {
        if ($orderKey == '') {
            $orderKey = array_keys($this->orderby)[0];
        }

        $orderby = explode('_', $orderKey);
        return [$orderby[0] => $orderby[1]];
    }

    /**
     * Construye un string con los parámetros pasados en la url
     * @return string
     */
    protected function getParams()
    {
        $result = "";
        if (!empty($this->query)) {
            $result = "&query=" . $this->query;
        }

        foreach ($this->filters as $key => $value) {
            if ($value['value'] != "") {
                $result .= "&" . $key . "=" . $value['value'];
            }
        }

        return $result;
    }

    /**
     * Añade un campo a la lista de Order By
     * @param string $field
     * @param string $label
     */
    protected function addOrderBy($field, $label = '')
    {
        $key1 = strtolower($field) . '_asc';
        $key2 = strtolower($field) . '_desc';

        if (empty($label)) {
            $label = ucfirst($field);
        }

        $this->orderby[$key1] = ['icon' => self::ICONO_ASC, 'label' => $label];
        $this->orderby[$key2] = ['icon' => self::ICONO_DESC, 'label' => $label];
    }

    /**
     * Define una nueva opción de filtrado para los datos
     * @param string $type    (opción: 'select', 'checkbox')
     * @param string $key     (identificador del filtro)
     * @param array  $options (opciones necesarias para aplicar el filtro)
     */
    private function addFilter($type, $key, $options)
    {
        if (empty($options['field'])) {
            $options['field'] = $key;
        }

        $value = $this->request->get($key);
        $this->filters[$key] = ['type' => $type, 'value' => $value, 'options' => $options];
    }

    /**
     * Add a filter type data table selection
     * Añade un filtro de tipo selección en tabla
     * @param string $key      (Filter identifier)
     * @param string $table    (Table name)
     * @param string $where    (Where condition for table)
     * @param string $field    (Field of the table with the data to show)
     */
    protected function addFilterSelect($key, $table, $where = '', $field = '')
    {
        $options = ['field' => $field, 'table' => $table, 'where' => $where];
        $this->addFilter('select', $key, $options);
    }

    /**
     * Añade un filtro del tipo condición boleana
     * @param string  $key     (Filter identifier)
     * @param string  $label   (Human reader description)
     * @param string  $field   (Field of the table to apply filter)
     * @param boolean $inverse (If you need to invert the selected value)
     */
    protected function addFilterCheckbox($key, $label, $field = '', $inverse = FALSE)
    {
        $options = ['label' => $label, 'field' => $field, 'inverse' => $inverse];
        $this->addFilter('checkbox', $key, $options);
    }

    /**
     * @param string $key
     * @param string $label
     */
    protected function addFilterDatePicker($key, $label, $field = '')
    {
        $options = ['label' => $label, 'field' => $field];
        $this->addFilter('datepicker', $key, $options);
    }

    /**
     * Carga una lista de datos desde una tabla
     * @param string $field : Field name to load
     * @param string $table : Table name from load
     * @param string $where : Where filter
     * @return array
     */
    public function optionlist($field, $table, $where)
    {
        $result = [];
        if ($this->dataBase->tableExists($table)) {
            $sql = "SELECT DISTINCT " . $field . " FROM " . $table . " WHERE COALESCE(" . $field . ", '')" . " <> ''";

            if ($where != "") {
                $sql .= " AND " . $where;
            }
            
            $sql = "SELECT DISTINCT " . $field 
                . " FROM " . $table 
                . " WHERE COALESCE(" . $field . ", '')" . " <> ''" . $where
                . " ORDER BY 1 ASC;";

            $data = $this->dataBase->select($sql);
            foreach ($data as $item) {
                $value = $item[$field];
                if ($value != "") {
                    $result[mb_strtolower($value, "UTF8")] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * Devuelve un item de paginación
     * @param string $url
     * @param int $page
     * @param int $offset
     * @param string $icon
     * @param boolean $active
     * @return array
     */
    private function addPaginationItem($url, $page, $offset, $icon = FALSE, $active = FALSE)
    {
        return [
            'url' => $url . "&offset=" . $offset,
            'icon' => $icon,
            'page' => $page,
            'active' => $active
        ];
    }

    /**
     * Calcula el navegador entre páginas.
     * Permite saltar a:
     *      primera,
     *      mitad anterior,
     *      pageMargin x páginas anteriores
     *      página actual
     *      pageMargin x páginas posteriores
     *      mitad posterior
     *      última
     *
     * @return array
     *      url    => link a la página
     *      icon   => icono específico de bootstrap en vez de núm. página
     *      page   => número de página
     *      active => Indica si es el indicador activo
     */
    public function pagination()
    {
        $result = [];
        $url = $this->url() . $this->getParams();
        $pageMargin = 5;
        $index = 0;

        $recordMin = $this->offset - (self::FS_ITEM_LIMIT * $pageMargin);
        if ($recordMin < 0) {
            $recordMin = 0;
        }

        $recordMax = $this->offset + (self::FS_ITEM_LIMIT * ($pageMargin + 1));
        if ($recordMax > $this->count) {
            $recordMax = $this->count;
        }

        // Añadimos la primera página, si no está incluida en el margen de páginas
        if ($this->offset > (self::FS_ITEM_LIMIT * $pageMargin)) {
            $result[$index] = $this->addPaginationItem($url, 1, 0, "glyphicon-step-backward");
            $index++;
        }

        // Añadimos la página de en medio entre la primera y la página seleccionada, 
        // si la página seleccionada es mayor que el margen de páginas
        $recordMiddleLeft = ($recordMin > self::FS_ITEM_LIMIT) ? ($this->offset / 2) : $recordMin;
        if ($recordMiddleLeft < $recordMin) {
            $page = floor($recordMiddleLeft / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($page + 1), ($page * self::FS_ITEM_LIMIT), "glyphicon-backward");
            $index++;
        }

        // Añadimos la página seleccionada y el margen de páginas a su izquierda y su derecha
        for ($record = $recordMin; $record < $recordMax; $record += self::FS_ITEM_LIMIT) {
            if (($record >= $recordMin && $record <= $this->offset) || ($record <= $recordMax && $record >= $this->offset)) {
                $page = ($record / self::FS_ITEM_LIMIT) + 1;
                $result[$index] = $this->addPaginationItem($url, $page, $record, FALSE, ($record == $this->offset));
                $index++;
            }
        }

        // Añadimos la página de en medio entre la página seleccionada y la última,
        // si la página seleccionada es más pequeña que el márgen entre páginas
        $recordMiddleRight = $this->offset + (($this->count - $this->offset) / 2);
        if ($recordMiddleRight > $recordMax) {
            $page = floor($recordMiddleRight / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($page + 1), ($page * self::FS_ITEM_LIMIT), "glyphicon-forward");
            $index++;
        }

        // Añadimos la última página, si no está incluida en el margen de páginas
        if ($recordMax < $this->count) {
            $pageMax = floor($this->count / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($pageMax + 1), ($pageMax * self::FS_ITEM_LIMIT), "glyphicon-step-forward");
        }

        return $result;
    }
}
