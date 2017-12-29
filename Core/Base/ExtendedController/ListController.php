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
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExportManager;
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Response;

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
     * @var ExportManager
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
     * @param Base\Cache $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog $miniLog
     * @param string $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->setTemplate('Master/ListController');

        $this->exportManager = new ExportManager();
        $this->active = $this->request->get('active', '');
        $this->offset = (int) $this->request->get('offset', 0);
        $this->query = $this->request->get('query', '');
        $this->views = [];
        $this->icons = [];
    }
    
    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Create views to show
        $this->createViews();

        // Store action to execute
        $action = $this->request->get('action', '');

        // Operations with data, before execute action
        $this->execPreviousAction($action);

        // Load data for every view
        foreach ($this->views as $key => $listView) {
            $where = [];
            $orderKey = '';

            // If processing the selected view, calculate order and filters
            if ($this->active == $key) {
                $orderKey = $this->request->get('order', '');
                $where = $this->getWhere();
            }

            // Set selected order by
            $this->views[$key]->setSelectedOrderBy($orderKey);

            // Load data using filter and order
            $listView->loadData($where, $this->getOffSet($key), Base\Pagination::FS_ITEM_LIMIT);
        }

        // Operations with data, after execute action
        $this->execAfterAction($action);
    }

    /**
     * Runs the actions that alter the data before reading it
     *
     * @param string $action
     */
    protected function execPreviousAction($action)
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
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'export':
                $this->setTemplate(false);
                $this->exportManager->newDoc($this->response, $this->request->get('option'));
                $this->views[$this->active]->export($this->exportManager);
                $this->exportManager->show($this->response);
                break;

            case 'megasearch':
                $this->megaSearchAction();
                break;
        }
    }

    /**
     * Delete data action
     *
     * @param BaseView $view View upon which the action is made
     *
     * @return bool
     */
    protected function deleteAction($view)
    {
        $code = $this->request->get('code');
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
     * Returns columns title for megaSearchAction function.
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
            if ($col->display !== 'none' && in_array($col->widget->type, ['text', 'money'], false)) {
                $result[] = $col->widget->fieldName;
                if (count($result) === $maxColumns) {
                    break;
                }
            }
        }
        return $result;
    }

    /**
     * Returns a JSON response to MegaSearch.
     */
    protected function megaSearchAction()
    {
        $this->setTemplate(false);
        $json = [
            $this->active => [
                'title' => $this->i18n->trans($this->title),
                'icon' => $this->getPageData()['icon'],
                'columns' => [],
                'results' => [],
            ]
        ];

        /// we search in all listviews
        foreach ($this->views as $key => $listView) {
            if (!isset($json[$key])) {
                $json[$key] = [
                    'title' => $listView->title,
                    'icon' => $this->icons[$key],
                    'columns' => [],
                    'results' => [],
                ];
            }

            $fields = $listView->getSearchIn();
            $where = [new DataBaseWhere($fields, $this->query, 'LIKE')];
            $listView->loadData($where, 0, Base\Pagination::FS_ITEM_LIMIT);

            $cols = $this->getTextColumns($listView, 6);
            $json[$key]['columns'] = $cols;

            foreach ($listView->getCursor() as $item) {
                $jItem = ['url' => $item->url()];
                foreach ($cols as $col) {
                    $jItem[$col] = $item->{$col};
                }
                $json[$key]['results'][] = $jItem;
            }
        }

        $this->response->setContent(json_encode($json));
    }

    /**
     * Establishes the WHERE clause according to the defined filters
     *
     * @return DataBaseWhere[]
     */
    protected function getWhere()
    {
        $result = [];

        if ($this->query !== '') {
            $fields = $this->views[$this->active]->getSearchIn();
            $result[] = new DataBaseWhere($fields, $this->query, 'LIKE');
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
     * Adds a list of fields (separated by "|") to the search fields list so that data can be filtered.
     * To use integer columns, use CAST(columnName AS CHAR(50)).
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
     * @param int $default (0 = None, 1 = ASC, 2 = DESC)
     */
    protected function addOrderBy($indexView, $field, $label = '', $default = 0)
    {
        $this->views[$indexView]->addOrderBy($field, $label, $default);
    }

    /**
     * Add a select type filter to a table
     *
     * @param string $indexView
     * @param string $key (Filter field name identifier)
     * @param string $table (Table name)
     * @param string $where (Where condition for table)
     * @param string $field (Field of the table with the data to show)
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
     * @param string $key (Filter identifier)
     * @param string $label (Human reader description)
     * @param string $field (Field of the table to apply filter)
     * @param bool $inverse (If you need to invert the selected value)
     * @param mixed $matchValue (Value to match)
     */
    protected function addFilterCheckbox($indexView, $key, $label, $field = '', $inverse = false, $matchValue = true)
    {
        $value = $this->request->get($key);
        $this->views[$indexView]->addFilter($key, ListFilter::newCheckboxFilter($field, $value, $label, $inverse, $matchValue));
    }

    /**
     * Adds a filter to a type of field.
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
     * @param string $key (Filter identifier)
     * @param string $label (Human reader description)
     * @param string $field (Field of the table to apply filter)
     */
    protected function addFilterDatePicker($indexView, $key, $label, $field = '')
    {
        $this->addFilterFromType($indexView, $key, 'datepicker', $label, $field);
    }

    /**
     * Adds a text type filter
     *
     * @param string $indexView
     * @param string $key (Filter identifier)
     * @param string $label (Human reader description)
     * @param string $field (Field of the table to apply filter)
     */
    protected function addFilterText($indexView, $key, $label, $field = '')
    {
        $this->addFilterFromType($indexView, $key, 'text', $label, $field);
    }

    /**
     * Adds a numeric type filter
     *
     * @param string $indexView
     * @param string $key (Filter identifier)
     * @param string $label (Human reader description)
     * @param string $field (Field of the table to apply filter)
     */
    protected function addFilterNumber($indexView, $key, $label, $field = '')
    {
        $this->addFilterFromType($indexView, $key, 'number', $label, $field);
    }

    /**
     * Creates a list of data from a table
     *
     * @param string $field : Field name with real value
     * @param array $options : Array with configuration values
     *                          [field = Field description, table = table name, where = SQL Where clausule]
     *
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
     *
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
     *
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
     *
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
