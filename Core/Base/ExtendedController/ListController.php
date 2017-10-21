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
     * Indica cual es la vista activa
     *
     * @var string
     */
    public $active;

    /**
     *
     * @var Base\ExportManager
     */
    public $exportManager;

    /**
     * Primer registro a seleccionar de la base de datos
     * @var int
     */
    protected $offset;

    /**
     * Esta variable contiene el texto enviado como parámetro query
     * usado para el filtrado de datos del modelo
     *
     * @var string|false
     */
    public $query;

    /**
     * Lista de vistas mostradas por el controlador
     *
     * @var ListView[]
     */
    public $views;

    /**
     * Procedimiento encargado de insertar las vistas a visualizar
     */
    abstract protected function createViews();

    /**
     * Inicia todos los objetos y propiedades.
     *
     * @param Cache      $cache
     * @param Translator $i18n
     * @param MiniLog    $miniLog
     * @param string     $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->setTemplate('Master/ListController');

        $this->active = $this->request->get('active', '');
        $this->exportManager = new Base\ExportManager();
        $this->offset = intval($this->request->get('offset', 0));
        $this->query = $this->request->get('query', '');
        $this->views = [];
    }

    /**
     * Ejecuta la lógica privada del controlador.
     *
     * @param mixed $response
     * @param mixed $user
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        // Creamos las vistas a visualizar
        $this->createViews();

        // Guardamos si hay operaciones por realizar
        $action = $this->request->get('action', '');

        // Operaciones sobre los datos antes de leerlos
        $this->execPreviousAction($action);

        // Lanzamos la carga de datos para cada una de las vistas
        foreach ($this->views as $key => $listView) {
            $where = [];
            $orderKey = '';

            // Si estamos procesando la vista seleccionada, calculamos el orden y los filtros
            if ($this->active == $key) {
                $orderKey = $this->request->get('order', '');
                $where = $this->getWhere();
            }

            // Establecemos el orderby seleccionado
            $this->views[$key]->setSelectedOrderBy($orderKey);

            // Cargamos los datos según filtro y orden
            $listView->loadData($where, $this->getOffSet($key), Base\Pagination::FS_ITEM_LIMIT);
        }

        // Operaciones generales con los datos cargados
        $this->execAfterAction($action);
    }

    /**
     * Ejecuta las acciones que alteran los datos antes de leerlos
     * 
     * @param string $action
     */
    private function execPreviousAction($action)
    {
        switch ($action) {
            case 'delete':
                $this->deleteAction($this->views[$this->active]);
                break;
        }
    }

    /**
     * Ejecuta las acciones del controlador
     * 
     * @param string $action
     */
    private function execAfterAction($action)
    {
        switch ($action) {
            case 'export':
                $this->setTemplate(false);
                $view = $this->views[$this->active];
                $document = $view->export($this->exportManager, $this->response, $this->request->get('option'));
                $this->response->setContent($document);
                break;

            case 'json':
                $this->jsonAction($this->views[$this->active]);
                break;
        }
    }

    /**
     * Acción de borrado de datos
     *
     * @param BaseView $view     Vista sobre la que se realiza la acción
     * @return boolean
     */
    protected function deleteAction($view)
    {
        $code = $this->request->get('code');
        if (strpos($code, ',') === false) {
            if ($view->delete($code)) {
                $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
                return TRUE;
            }

            return FALSE;
        }

        /// borrado múltiple
        $numDeletes = 0;
        foreach (explode(',', $code) as $cod) {
            if ($view->delete($cod)) {
                $numDeletes++;
            } else {
                $this->miniLog->warning($this->i18n->trans('record-deleted-error'));
            }
        }

        if ($numDeletes > 0) {
            $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
            return TRUE;
        }
        
        return FALSE;
    }

    protected function jsonAction($view)
    {
        $this->setTemplate(false);
        $cols = [];
        foreach ($view->getColumns() as $col) {
            if ($col->display != 'none' && $col->widget->type == 'text' && count($cols) < 4) {
                $cols[] = $col->widget->fieldName;
            }
        }
        $json = [];
        foreach ($view->getCursor() as $item) {
            $jItem = ['url' => $item->url()];
            foreach ($cols as $col) {
                $jItem[$col] = $item->{$col};
            }
            $json[] = $jItem;
        }
        if (!empty($json)) {
            \array_unshift($json, $cols);
        }
        $this->response->setContent(json_encode($json));
    }

    /**
     * Establece la clausula WHERE según los filtros definidos
     *
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
                        $checked = ($value['options']['inverse']) ? (boolean) !$value['value'] : (boolean) $value['value'];
                        $result[] = new DataBase\DataBaseWhere($field, $checked);
                        break;
                }
            }
        }

        return $result;
    }

    /**
     * Crea y añade una vista al controlador.
     *
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     */
    protected function addView($modelName, $viewName, $viewTitle = 'search')
    {
        $this->views[$viewName] = new ListView($viewTitle, $modelName, $viewName, $this->user->nick);
        if (empty($this->active)) {
            $this->active = $viewName;
        }
    }

    /**
     * Añade una lista de campos (separados por |) a lista de campos de búsqueda
     * para el filtrado de datos.
     *
     * @param string $indexView
     * @param string $fields
     */
    protected function addSearchFields($indexView, $fields)
    {
        $this->views[$indexView]->addSearchIn($fields);
    }

    /**
     * Añade un campo a la lista de Order By de una vista.
     *
     * @param string $indexView
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
     * Añade un filtro de tipo selección en tabla.
     *
     * @param string $indexView
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
     * Añade un filtro del tipo condición boleana.
     *
     * @param string $indexView
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
     * Añade un filtro del tipo fecha.
     *
     * @param string $indexView
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
     * Carga una lista de datos desde una tabla.
     *
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
                . " WHERE COALESCE(" . $options['field'] . ", '')" . " <> ''" . $options['where']
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
     * Devuelve el valor de offset para la vista indicada.
     *
     * @param string $indexView
     * @return int
     */
    private function getOffSet($indexView)
    {
        return ($indexView == $this->active) ? $this->offset : 0;
    }

    /**
     * Construye un string con los parámetros pasados en la url
     * de la llamada al controlador.
     *
     * @param string $indexView
     * @return string
     */
    private function getParams($indexView)
    {
        $result = "";
        if ($indexView == $this->active) {
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
     * Crea un array con los "saltos" disponibles para paginar los datos
     * del modelo de la vista indicada.
     *
     * @param string $indexView
     * @return array
     */
    public function pagination($indexView)
    {
        $offset = $this->getOffSet($indexView);
        $count = $this->views[$indexView]->count;
        $url = $this->views[$indexView]->getURL('list') . $this->getParams($indexView);

        $paginationObj = new Base\Pagination();
        $result = $paginationObj->getPages($url, $count, $offset);
        unset($paginationObj);

        return $result;
    }

    public function getStringURLs($type)
    {
        $result = '';
        $sep = '';
        foreach ($this->views as $key => $view) {
            $result .= $sep . $key . ': "' . $view->getURL($type) . '"';
            $sep = ', ';
        }
        return $result;
    }
}
