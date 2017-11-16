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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\DataBase;

/**
 * Controller that lists the data in table mode
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class ListController extends Base\Controller
{
    /**
     * Indicates the active view
     *
     * @var string
     */
    public $active;

    /**
     * Object to export data
     *
     * @var Base\ExportManager
     */
    public $exportManager;

    /**
     * First row to select from the database
     * @var int
     */
    protected $offset;

    /**
     * This string contains the text sent as a query parameter, used to filter the model data
     *
     * @var string|false
     */
    public $query;

    /**
     * List of views displayed by the controller
     *
     * @var ListView[]
     */
    public $views;

    /**
     * List of icons for each of the views
     *
     * @var array
     */
    public $icons;

    /**
     * Inserts the views to display
     */
    abstract protected function createViews();

    /**
     * Initializes all the objects and properties
     *
     * @param Base\Cache      $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog    $miniLog
     * @param string     $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->setTemplate('Master/ListController');

        $this->active = $this->request->get('active', '');
        $this->exportManager = new Base\ExportManager();
        $this->offset = (int) $this->request->get('offset', 0);
        $this->query = $this->request->get('query', '');
        $this->views = [];
        $this->icons = [];
    }

    /**
     * Runs the controller's private logic
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
     * Runs the actions that alter the data before reading it
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
     * Runs the controller actions
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
     * Delete data action
     *
     * @param BaseView $view     View upon which the action is made
     * @return boolean
     */
    protected function deleteAction($view)
    {
        $code = $this->request->get('code');
        if (strpos($code, ',') === false) {
            if ($view->delete($code)) {
                $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
                return true;
            }

            return false;
        }

        /// multiple delete
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
            return true;
        }

        return false;
    }

    /**
     * Devuelve el texto de las columnas.
     *
     * @param ListView $view
     * @param int $maxColumns
     *
     * @return array
     */
    private function getTextColumns($view, $maxColumns)
    {
        $result = [];
        foreach ($view->getColumns() as $col) {
            if ($col->display !== 'none' && $col->widget->type === 'text') {
                $result[] = $col->widget->fieldName;
                if (count($result) === $maxColumns) {
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Returns a JSON response
     *
     * @param ListView $view
     */
    protected function jsonAction($view)
    {
        $this->setTemplate(false);
        $cols = $this->getTextColumns($view, 4);
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
     * Establishes the WHERE clause according to the defined filters
     *
     * @return array
     */
    protected function getWhere()
    {
        $result = [];

        if ($this->query !== '') {
            $fields = $this->views[$this->active]->getSearchIn();
            $result[] = new DataBase\DataBaseWhere($fields, $this->query, "LIKE");
        }

        foreach ($this->views[$this->active]->getFilters() as $key => $filter) {
            $filter->getDataBaseWhere($result, $key);
        }

        return $result;
    }

    /**
     * Creates and adds a view to the controller
     *
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     * @param string $icon
     */
    protected function addView($modelName, $viewName, $viewTitle = 'search', $icon = 'fa-search')
    {
        $this->views[$viewName] = new ListView($viewTitle, $modelName, $viewName, $this->user->nick);
        $this->icons[$viewName] = $icon;
        if (empty($this->active)) {
            $this->active = $viewName;
        }
    }

    /**
     * Adds a list of fields (separated by "|") to the search fields list so that data can be filtered
     *
     * @param string $indexView
     * @param string[] $fields
     */
    protected function addSearchFields($indexView, $fields)
    {
        $this->views[$indexView]->addSearchIn($fields);
    }

    /**
     * Adds a field to a view's Order By list
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
     * Add a select type filter to a table
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
        $this->views[$indexView]->addFilter($key, ListFilter::newSelectFilter($field, $value, $table, $where));
    }

    /**
     * Adds a boolean condition type filter
     *
     * @param string $indexView
     * @param string $key     (Filter identifier)
     * @param string $label   (Human reader description)
     * @param string $field   (Field of the table to apply filter)
     * @param bool $inverse   (If you need to invert the selected value)
     */
    protected function addFilterCheckbox($indexView, $key, $label, $field = '', $inverse = false)
    {
        $value = $this->request->get($key);
        $this->views[$indexView]->addFilter($key, ListFilter::newCheckboxFilter($field, $value, $label, $inverse));
    }

    /**
     * Añade un filtro a un tipo de campo.
     *
     * @param string $indexView
     * @param string $key
     * @param string $type
     * @param string $label
     * @param string $field
     */
    private function addFilterFromType($indexView, $key, $type, $label, $field)
    {
        $config = [
            'field' => $field,
            'label' => $label,
            'valueFrom' => $this->request->get($key . '-from'),
            'operatorFrom' => $this->request->get($key . '-from-operator', '>='),
            'valueTo' => $this->request->get($key . '-to'),
            'operatorTo' => $this->request->get($key . '-to-operator', '<=')
        ];

        $this->views[$indexView]->addFilter($key, ListFilter::newStandardFilter($type, $config));
    }

    /**
     * Adds a date type filter
     *
     * @param string $indexView
     * @param string $key     (Filter identifier)
     * @param string $label   (Human reader description)
     * @param string $field   (Field of the table to apply filter)
     */
    protected function addFilterDatePicker($indexView, $key, $label, $field = '')
    {
        $this->addFilterFromType($indexView, $key, 'datepicker', $label, $field);
    }

    /**
     * Adds a text type filter
     *
     * @param string $indexView
     * @param string $key     (Filter identifier)
     * @param string $label   (Human reader description)
     * @param string $field   (Field of the table to apply filter)
     */
    protected function addFilterText($indexView, $key, $label, $field = '')
    {
        $this->addFilterFromType($indexView, $key, 'text', $label, $field);
    }

    /**
     * Adds a numeric type filter
     *
     * @param string $indexView
     * @param string $key     (Filter identifier)
     * @param string $label   (Human reader description)
     * @param string $field   (Field of the table to apply filter)
     */
    protected function addFilterNumber($indexView, $key, $label, $field = '')
    {
        $this->addFilterFromType($indexView, $key, 'number', $label, $field);
    }

    /**
     * Creates a list of data from a table
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

            $sql = 'SELECT DISTINCT ' . $fieldList
                . ' FROM ' . $options['table']
                . ' WHERE COALESCE(' . $options['field'] . ", '')" . " <> ''" . $options['where']
                . ' ORDER BY ' . $options['field'] . ' ASC;';

            $data = $this->dataBase->select($sql);
            foreach ($data as $item) {
                $value = $item[$options['field']];
                if ($value !== '') {
                    /**
                     * If the key is  mb_strtolower($item[$field], 'UTF8') then we can't filter by codserie, codalmacen,
                     * etc.
                     */
                    $result[$item[$field]] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the offset value for the specified view
     *
     * @param string $indexView
     * @return int
     */
    private function getOffSet($indexView)
    {
        return ($indexView === $this->active) ? $this->offset : 0;
    }

    /**
     * Returns a string with the parameters in the controller call url
     *
     * @param string $indexView
     * @return string
     */
    private function getParams($indexView)
    {
        $result = '';
        if ($indexView === $this->active) {
            if (!empty($this->query)) {
                $result = '&query=' . $this->query;
            }

            foreach ($this->views[$this->active]->getFilters() as $key => $filter) {
                $result .= $filter->getParams($key);
            }
        }

        return $result;
    }

    /**
     * Creates an array with the available "jumps" to paginate the model data with the specified view
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

    /**
     * Returns an array for JS of URLs for the elements in a view
     *
     * @param string $type
     *
     * @return string
     */
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
