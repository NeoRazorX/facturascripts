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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\DataBase;

/**
 * Controlador para listado de datos en modo tabla
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class ListController extends Base\Controller
{

    /**
     * Constantes para paginación
     */
    const FS_ITEM_LIMIT = 50;
    const FS_PAGE_MARGIN = 5;

    /**
     * Lista de vistas mostradas por el controlador
     * 
     * @var Array of DataView 
     */
    public $views;

    /**
     * Indica cual es la vista activa
     * 
     * @var int 
     */
    public $active;
    
    /**
     * Primer registro a seleccionar de la base de datos
     * @var int
     */
    protected $offset;
    
    /**
     * Esta variable contiene el texto enviado como parámetro query
     * usado para el filtrado de datos del modelo
     * @var string|false
     */
    public $query;
        
    /**
     * Procedimiento encargado de insertar las vistas a visualizar
     */
    abstract protected function createViews();
    
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

        $this->views = [];
        $this->active = intval($this->request->get('active', 0));
        $this->offset = intval($this->request->get('offset', 0));
        $this->query = $this->request->get('query', '');        
    }

    /**
     * Ejecuta la lógica privada del controlador.
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
        
        // Creamos las vistas a visualizar
        $this->createViews();
                        
        // Comprobamos si hay operaciones por realizar
        if ($this->request->isMethod('POST')) {
            $this->setActionForm();
        }

        // Lanzamos cada una de las vistas
        foreach ($this->views as $key => $dataView) {
            $where = [];   
            $orderKey = '';
            
            // Si estamos procesando la vista seleccionada, calculamos el orden y los filtros
            if ($this->active === $key) {
                $orderKey = $this->request->get('order', '');
                $where = $this->getWhere();
            }
            
            // Establecemos el orderby seleccionado
            $this->views[$key]->setSelectedOrderBy($orderKey);
            
            // Cargamos los datos según filtro y orden
            $dataView->loadData($where, $this->getOffSet($key), self::FS_ITEM_LIMIT);
        }        
    }
    
    protected function addView($modelName, $viewName, $viewTitle = 'search')
    {
        $this->views[] = new DataView($viewTitle, $modelName, $viewName, $this->user->nick);
        return (count($this->views) - 1);
    }
        
    /**
     * Establece la clausula WHERE según los filtros definidos
     * @return array
     */
    protected function getWhere()
    {
        $result = [];

        if ($this->query != '') {
            $fields = $this->views[$this->active]->getSearchIn();
            $result[] = new DataBase\DataBaseWhere($fields, $this->query, "LIKE");
        }
        
        $filters = $this->views[$this->active]->getFilters();
        foreach ($filters as $key => $value) {
            if ($value['value']) {
                switch ($value['type']) {
                    case 'datepicker':
                    case 'select':
                        $result[] = new DataBase\DataBaseWhere($key, $value['value']);
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
     * Aplica la acción solicitada por el usuario
     */
    private function setActionForm()
    {
        $data = $this->request->request->all();
        if (!isset($data['active']) || !isset($data['action'])) {
            return;
        }
        
        switch ($data['action']) {
            case 'delete':
                if ($this->views[$data['active']]->delete($data['code'])) {
                    $this->miniLog->notice($this->i18n->trans('Record deleted correctly!'));
                }

                break;

            default:
                break;
        }
    }
    
    
    protected function addSearchFields($indexView, $fields)
    {
        $this->views[$indexView]->addSearchIn($fields);
    }

    /**
     * Añade un campo a la lista de Order By de una vista
     * 
     * @param int $indexView
     * @param string $field
     * @param string $label
     * @param int $default    (0 = None, 1 = ASC, 2 = DESC)
     */
    protected function addOrderBy($indexView, $field, $label = '', $default = 0)
    {
        $this->views[$indexView]->addOrderBy($field, $label, $default); 
    }

    /**
     * Add a filter type data table selection
     * Añade un filtro de tipo selección en tabla
     * @param int $indexView
     * @param string $key      (Filter field name identifier)
     * @param string $table    (Table name)
     * @param string $where    (Where condition for table)
     * @param string $field    (Field of the table with the data to show)
     */
    protected function addFilterSelect($indexView, $key, $table, $where = '', $field = '')
    {
        $value = $this->request->get($key);
        $this->views[$indexView]->addFilterSelect($key, $value, $table, $where, $field);
    }

    /**
     * Añade un filtro del tipo condición boleana
     * @param int $indexView
     * @param string  $key     (Filter identifier)
     * @param string  $label   (Human reader description)
     * @param string  $field   (Field of the table to apply filter)
     * @param boolean $inverse (If you need to invert the selected value)
     */
    protected function addFilterCheckbox($indexView, $key, $label, $field = '', $inverse = FALSE)
    {
        $value = $this->request->get($key);
        $this->views[$indexView]->addFilterCheckBox($key, $value, $label, $field, $inverse);
    }

    /**
     * @param int $indexView
     * @param string  $key     (Filter identifier)
     * @param string  $label   (Human reader description)
     * @param string  $field   (Field of the table to apply filter)
     */
    protected function addFilterDatePicker($indexView, $key, $label, $field = '')
    {
        $value = $this->request->get($key);
        $this->views[$indexView]->addFilterDatePicker($key, $value, $label, $field);
    }

    /**
     * Carga una lista de datos desde una tabla
     * @param string $field : Field name with real value
     * @param array $options : Array with configuration values [field = Field description, table = table name, where = SQL Where clausule]
     * @return array
     */
    public function optionlist($field, $options)
    {
        $result = [];
        if ($this->dataBase->tableExists($options['table'])) {
            $fieldList = $field;
            if ($field !== $options['field']) {
                $fieldList = $fieldList . ', ' . $options['field'];
            }
            
            $sql = "SELECT DISTINCT " . $fieldList
                . " FROM " . $options['table']
                . " WHERE COALESCE(" . $options['field'] . ", '')" . " <> ''" . $where
                . " ORDER BY " . $options['field'] . " ASC;";

            $data = $this->dataBase->select($sql);
            foreach ($data as $item) {
                $value = $item[$options['field']];
                if ($value != "") {
                    $result[mb_strtolower($item[$field], "UTF8")] = $value;
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
     * Devuelve el offset para el primer elemento del margen especificado
     * para la paginación
     * 
     * @param int $indexView
     * @return int
     */
    private function getRecordMin($indexView)
    {
        $result = 0;
        if ($indexView === $this->active) {
            $result = $this->offset - (self::FS_ITEM_LIMIT * self::FS_PAGE_MARGIN);
            if ($result < 0) {
                $result = 0;
            }
        }
        return $result;
    }

    /**
     * Devuelve el offset para el último elemento del margen especificado
     * para la paginación
     * 
     * @param int $indexView
     * @return int
     */
    private function getRecordMax($indexView)
    {
        $result = $this->views[$indexView]->count;
        if ($indexView === $this->active) {
            $result = $this->offset + (self::FS_ITEM_LIMIT * (self::FS_PAGE_MARGIN + 1));
            if ($result > $this->views[$indexView]->count) {
                $result = $this->views[$indexView]->count;
            }
        }
        return $result;
    }

    private function getOffSet($indexView)
    {
        return ($indexView === $this->active)
            ? $this->offset
            : 0;
    }
    
    /**
     * Construye un string con los parámetros pasados en la url
     * 
     * @param string $indexView
     * @return string
     */
    protected function getParams($indexView)
    {
        $result = "";
        if ($indexView === $this->active) {        
            if (!empty($this->query)) {
                $result = "&query=" . $this->query;
            }

            $filters = $this->views[$this->active]->getFilters();        
            foreach ($filters as $key => $value) {
                if ($value['value'] != "") {
                    $result .= "&" . $key . "=" . $value['value'];
                }
            }
        }
        
        return $result;
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
     * @param int $indexView
     * @return array
     *      url    => link a la página
     *      icon   => icono específico de bootstrap en vez de núm. página
     *      page   => número de página
     *      active => Indica si es el indicador activo
     */
    public function pagination($indexView)
    {
        $result = [];
        $url = $this->views[$indexView]->getURL('list') . $this->getParams($indexView);

        $recordMin = $this->getRecordMin($indexView);
        $recordMax = $this->getRecordMax($indexView);
        $offset = $this->getOffSet($indexView);
        $index = 0;

        // Añadimos la primera página, si no está incluida en el margen de páginas
        if ($offset > (self::FS_ITEM_LIMIT * self::FS_PAGE_MARGIN)) {
            $result[$index] = $this->addPaginationItem($url, 1, 0, "fa-step-backward");
            $index++;
        }

        // Añadimos la página de en medio entre la primera y la página seleccionada,
        // si la página seleccionada es mayor que el margen de páginas
        $recordMiddleLeft = ($recordMin > self::FS_ITEM_LIMIT) ? ($offset / 2) : $recordMin;
        if ($recordMiddleLeft < $recordMin) {
            $page = floor($recordMiddleLeft / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($page + 1), ($page * self::FS_ITEM_LIMIT), "fa-backward");
            $index++;
        }

        // Añadimos la página seleccionada y el margen de páginas a su izquierda y su derecha
        for ($record = $recordMin; $record < $recordMax; $record += self::FS_ITEM_LIMIT) {
            if (($record >= $recordMin && $record <= $offset) || ($record <= $recordMax && $record >= $offset)) {
                $page = ($record / self::FS_ITEM_LIMIT) + 1;
                $result[$index] = $this->addPaginationItem($url, $page, $record, FALSE, ($record == $offset));
                $index++;
            }
        }

        // Añadimos la página de en medio entre la página seleccionada y la última,
        // si la página seleccionada es más pequeña que el márgen entre páginas
        $recordMiddleRight = $offset + (($this->views[$indexView]->count - $offset) / 2);
        if ($recordMiddleRight > $recordMax) {
            $page = floor($recordMiddleRight / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($page + 1), ($page * self::FS_ITEM_LIMIT), "fa-forward");
            $index++;
        }

        // Añadimos la última página, si no está incluida en el margen de páginas
        if ($recordMax < $this->views[$indexView]->count) {
            $pageMax = floor($this->views[$indexView]->count / self::FS_ITEM_LIMIT);
            $result[$index] = $this->addPaginationItem($url, ($pageMax + 1), ($pageMax * self::FS_ITEM_LIMIT), "fa-step-forward");
        }

        /// si solamente hay una página, no merece la pena mostrar un único botón
        return (count($result) > 1) ? $result : [];
    }
}
