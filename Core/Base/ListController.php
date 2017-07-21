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
namespace FacturaScripts\Core\Base;

/**
 * Controlador para listado de datos en modo tabla
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListController extends Controller
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
     * Lista de campos cargados en el cursor y su parametrización
     * Ejemplo: ["label" => "Etiqueta", "field" => "Nombre del campo", "display" => "left/center/right/none"]
     * @var array
     */
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
     * @var string
     */
    public $query;
    
    /**
     * Lista de filtros disponibles y su parametrización
     * @var array
     */
    public $filters;

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
     * Comprueba el valor de un parámetro pasado en una url
     * @param string $field
     * @return string|FALSE
     */
    protected function getParamValue($field)
    {
        $result = (isset($_REQUEST[$field])) ? $_REQUEST[$field] : FALSE;
        if (!$result) {
            $result = (filter_input(INPUT_POST, $field)) ? filter_input(INPUT_POST, $field) : FALSE;
        }

        if (!$result) {
            $result = (filter_input(INPUT_GET, $field)) ? filter_input(INPUT_GET, $field) : FALSE;
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
            switch ($value['type']) {
                case 'select': {
                        if ($value['value'] != "") {
                            $field = $value['options']['field'];
                            $value = $value['value'];
                            $result[] = new DataBase\DatabaseWhere($field, $value); 
                        }
                        break;
                    }

                case 'checkbox': {
                        if ($value['value']) {
                            $field = $value['options']['field'];
                            $value = !$value['options']['inverse'];
                            $result[] = new DataBase\DatabaseWhere($field, $value); 
                        }

                        break;
                    }

                default: {
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
        if ($this->query != '') {
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
        
        $this->filters[$key] = ['type' => $type, 'value' => $this->getParamValue($key), 'options' => $options];
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

    protected function addFilterDatePicker($key, $label, $field = '')
    {
        $options = ['label' => $label, 'field' => $field];
        $this->addFilter('datepicker', $key, $options);
    }
    
    /**
     * Ejecuta la lógica pública del controlador.
     */
    public function publicCore()
    {
        parent::publiCore();
    }

    /**
     * Ejecuta la lógica privada del controlador.
     */
    public function privateCore()
    {
        parent::privateCore();

        // Establecemos el orderby seleccionado
        $orderKey = $this->getParamValue("order");
        $this->selectedOrderBy = ($orderKey == TRUE) ? $this->getSelectedOrder($orderKey) : array_keys($this->orderby)[0];
    }

    /**
     * Inicia todos los objetos y propiedades.
     *
     * @param Cache $cache
     * @param Translator $i18n
     * @param MiniLog $miniLog
     * @param Response $response
     * @param Models\User $user
     * @param string $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, &$response, $user, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $response, $user, $className);

        $this->setTemplate("ListController");

        $this->offset = isset($_GET["offset"]) ? intval($_GET["offset"]) : 0;
        $this->query = $this->getParamValue('query');
        $this->count = 0;
        $this->orderby = [];
        $this->filters = [];
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
        if ($this->database->tableExists($table)) {
            $sql = "SELECT DISTINCT " . $field . " FROM " . $table . " WHERE COALESCE(" . $field . ", '')" . " <> ''";

            if ($where != "") {
                $sql .= " AND " . $where;
            }

            $sql .= " ORDER BY 1 ASC;";

            $data = $this->database->select($sql);
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

        // Add first page, if not included in pagMargin
        if ($this->offset > (self::FS_ITEM_LIMIT * $pageMargin)) {
            $result[$index] = $this->addPaginationItem($url, 1, 0, "glyphicon-step-backward");
            $index++;
        }

        // Add middle left page, if offset is greater than pageMargin
        $recordMiddleLeft = ($recordMin > self::FS_ITEM_LIMIT) ? ($this->offset / 2) : $recordMin;
        if ($recordMiddleLeft < $recordMin) {
            $page = floor($recordMiddleLeft / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($page + 1), ($page * self::FS_ITEM_LIMIT), "glyphicon-backward");
            $index++;
        }

        // Add -pagination / offset / +pagination
        for ($record = $recordMin; $record < $recordMax; $record += self::FS_ITEM_LIMIT) {
            if (($record >= $recordMin AND $record <= $this->offset) OR ($record <= $recordMax AND $record >= $this->offset)) {
                $page = ($record / self::FS_ITEM_LIMIT) + 1;
                $result[$index] = $this->addPaginationItem($url, $page, $record, FALSE, ($record == $this->offset));
                $index++;
            }
        }

        // Add middle right page, if offset is lesser than pageMargin   
        $recordMiddleRight = $this->offset + (($this->count - $this->offset) / 2);
        if ($recordMiddleRight > $recordMax) {
            $page = floor($recordMiddleRight / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($page + 1), ($page * self::FS_ITEM_LIMIT), "glyphicon-forward");
            $index++;
        }

        // Add last page, if not include in pagMargin
        if ($recordMax < $this->count) {
            $pageMax = floor($this->count / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($pageMax + 1), ($pageMax * self::FS_ITEM_LIMIT), "glyphicon-step-forward");
        }

        return $result;
    }
}
