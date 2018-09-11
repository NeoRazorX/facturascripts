<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller that lists the data in table mode
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Cristo M. Estévez Hernández <cristom.estevez@gmail.com>
 */
abstract class ListController extends BaseController
{

    /**
     * Tools to work with numbers.
     *
     * @var Base\NumberTools
     */
    public $numberTools;

    /**
     * First row to select from the database.
     *
     * @var int
     */
    protected $offset;

    /**
     * This string contains the text sent as a query parameter, used to filter the model data.
     *
     * @var string
     */
    public $query;

    /**
     * Initializes all the objects and properties.
     *
     * @param Base\Cache      $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog    $miniLog
     * @param string          $className
     * @param string          $uri
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className, $uri = '')
    {
        parent::__construct($cache, $i18n, $miniLog, $className, $uri);

        $this->setTemplate('Master/ListController');

        $this->numberTools = new Base\NumberTools();
        $this->offset = (int) $this->request->get('offset', 0);
        $this->query = $this->request->get('query', '');
    }

    /**
     * Returns an array for JS of URLs for the elements in a view.
     *
     * @param string $type
     *
     * @return string
     */
    public function getStringURLs(string $type): string
    {
        $result = '';
        $sep = '';
        foreach ($this->views as $viewName => $view) {
            $result .= $sep . $viewName . ': "' . $view->getURL($type) . '"';
            $sep = ', ';
        }

        return $result;
    }

    /**
     * Creates an array with the available "jumps" to paginate the model data with the specified view.
     *
     * @param string $viewName
     *
     * @return array
     */
    public function pagination(string $viewName)
    {
        $offset = $this->getOffSet($viewName);
        $count = $this->views[$viewName]->count;
        $url = $this->views[$viewName]->getURL('list');

        $extra = $this->getParams($viewName);
        if (strpos($url, '?') === false) {
            $url .= $extra;
        } elseif (!empty($extra)) {
            $url .= '&' . substr($extra, 1);
        }

        $paginationObj = new Base\Pagination($url);
        return $paginationObj->getPages($count, $offset);
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response                   $response
     * @param User                       $user
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
        if (!$this->execPreviousAction($action)) {
            return;
        }

        // Load data for every view
        foreach (array_keys($this->views) as $viewName) {
            $where = [];
            $orderKey = '';

            // If processing the selected view, calculate order and filters
            if ($this->active == $viewName) {
                $orderKey = $this->request->get('order', '');
                $where = $this->getWhere();
            }

            // Set selected order by
            $this->views[$viewName]->setSelectedOrderBy($orderKey);

            // Load data using filter and order
            $this->loadData($viewName, $where, $this->getOffSet($viewName));
        }

        // Operations with data, after execute action
        $this->execAfterAction($action);
    }

    /**
     * Add an autocomplete type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key        (Filter identifier)
     * @param string $label      (Human reader description)
     * @param string $field      (Field of the model to apply filter)
     * @param string $table      (Table to search)
     * @param string $fieldcode  (Primary column of the table to search and match)
     * @param string $fieldtitle (Column to show name or description)
     * @param array  $where      (Estra where conditions)
     */
    protected function addFilterAutocomplete($viewName, $key, $label, $field, $table, $fieldcode = '', $fieldtitle = '', $where = [])
    {
        $value = ($viewName == $this->active) ? $this->request->get($key, '') : '';
        $fcode = empty($fieldcode) ? $field : $fieldcode;
        $ftitle = empty($fieldtitle) ? $fcode : $fieldtitle;
        $this->views[$viewName]->addFilter($key, BaseFilter::newAutocompleteFilter($label, $field, $table, $fcode, $ftitle, $value, $where));
    }

    /**
     * Adds a boolean condition type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key        (Filter identifier)
     * @param string $label      (Human reader description)
     * @param string $field      (Field of the model to apply filter)
     * @param bool   $inverse    (If you need to invert the selected value)
     * @param mixed  $matchValue (Value to match)
     */
    protected function addFilterCheckbox($viewName, $key, $label, $field, $inverse = false, $matchValue = true)
    {
        $value = ($viewName == $this->active) ? $this->request->get($key, '') : '';
        $this->views[$viewName]->addFilter($key, BaseFilter::newCheckboxFilter($field, $value, $label, $inverse, $matchValue));
    }

    /**
     * Adds a date type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     */
    protected function addFilterDatePicker($viewName, $key, $label, $field)
    {
        $this->addFilterFromType($viewName, $key, $label, $field, 'datepicker');
    }

    /**
     * Adds a filter to a type of field to the ListView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     * @param string $type
     */
    private function addFilterFromType($viewName, $key, $label, $field, $type)
    {
        $config = [
            'field' => $field,
            'label' => $label,
            'valueFrom' => ($viewName == $this->active) ? $this->request->get($key . '-from', '') : '',
            'operatorFrom' => $this->request->get($key . '-from-operator', '>='),
            'valueTo' => ($viewName == $this->active) ? $this->request->get($key . '-to', '') : '',
            'operatorTo' => $this->request->get($key . '-to-operator', '<='),
        ];

        $this->views[$viewName]->addFilter($key, BaseFilter::newStandardFilter($type, $config));
    }

    /**
     * Adds a numeric type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     */
    protected function addFilterNumber($viewName, $key, $label, $field)
    {
        $this->addFilterFromType($viewName, $key, $label, $field, 'number');
    }

    /**
     * Add a select type filter to a ListView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     * @param array  $values    (Values to show)
     */
    protected function addFilterSelect($viewName, $key, $label, $field, $values = [])
    {
        $value = ($viewName == $this->active) ? $this->request->get($key, '') : '';
        $this->views[$viewName]->addFilter($key, BaseFilter::newSelectFilter($label, $field, $values, $value));
    }

    /**
     * Adds a text type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     */
    protected function addFilterText($viewName, $key, $label, $field)
    {
        $this->addFilterFromType($viewName, $key, $label, $field, 'text');
    }

    /**
     * Adds an order field to the ListView.
     *
     * @param string       $viewName
     * @param array        $fields
     * @param string       $label
     * @param int          $default   (0 = None, 1 = ASC, 2 = DESC)
     */
    protected function addOrderBy(string $viewName, array $fields, string $label = '', int $default = 0)
    {
        $orderLabel = empty($label) ? $fields[0] : $label;
        $this->views[$viewName]->addOrderBy($fields, $orderLabel, $default);
    }

    /**
     * Adds a list of fields to the search in the ListView.
     * To use integer columns, use CAST(columnName AS CHAR(50)).
     *
     * @param string $viewName
     * @param array  $fields
     */
    protected function addSearchFields(string $viewName, array $fields)
    {
        $this->views[$viewName]->addSearchIn($fields);
    }

    /**
     * Creates and adds a ListView to the controller.
     *
     * @param string $viewName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $icon
     */
    protected function addView($viewName, $modelName, $viewTitle = '', $icon = 'fa-search')
    {
        $title = empty($viewTitle) ? $this->title : $viewTitle;
        $this->views[$viewName] = new ListView($title, self::MODEL_NAMESPACE . $modelName, $viewName, $this->user->nick);
        $this->setSettings($viewName, 'icon', $icon);
        $this->setSettings($viewName, 'insert', true);
        $this->setSettings($viewName, 'megasearch', true);
        if (empty($this->active)) {
            $this->active = $viewName;
        }
    }

    /**
     * Delete data action method.
     *
     * @return bool
     */
    protected function deleteAction()
    {
        if (!$this->permissions->allowDelete) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-delete'));
            return false;
        }

        $model = $this->views[$this->active]->model;
        $code = $this->request->get('code');
        $numDeletes = 0;
        foreach (explode(',', $code) as $cod) {
            if ($model->loadFromCode($cod) && $model->delete()) {
                ++$numDeletes;
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
     * Runs the controller actions after data read.
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'export':
                $this->setTemplate(false);
                $this->exportManager->newDoc($this->request->get('option', ''));
                $this->views[$this->active]->export($this->exportManager);
                $this->exportManager->show($this->response);
                break;

            case 'megasearch':
                $this->megaSearchAction();
                break;
        }
    }

    /**
     * Runs the actions that alter the data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'autocomplete':
                $this->setTemplate(false);
                $results = $this->autocompleteAction();
                $this->response->setContent(json_encode($results));
                return false;

            case 'delete':
                $this->deleteAction();
                break;
        }

        return true;
    }

    /**
     * Returns the offset value for the specified view.
     *
     * @param string $viewName
     *
     * @return int
     */
    private function getOffSet($viewName)
    {
        return ($viewName === $this->active) ? $this->offset : 0;
    }

    /**
     * Returns a string with the parameters in the controller call url.
     *
     * @param string $viewName
     *
     * @return string
     */
    private function getParams($viewName)
    {
        $result = '';
        if ($viewName === $this->active) {
            $join = '?';
            if (!empty($this->query)) {
                $result = $join . 'query=' . $this->query;
                $join = '&';
            }

            foreach ($this->views[$this->active]->getFilters() as $key => $filter) {
                $result .= $filter->getParams($key, $join);
                $join = '&';
            }
        }

        return $result;
    }

    /**
     * Returns columns title for megaSearchAction function.
     *
     * @param ListView $view
     * @param int      $maxColumns
     *
     * @return array
     */
    private function getTextColumns($view, $maxColumns)
    {
        $result = [];
        foreach ($view->getColumns() as $col) {
            if ($col->display === 'none' || !in_array($col->widget->type, ['text', 'money'], false)) {
                continue;
            }

            $result[] = $col->widget->fieldName;
            if (count($result) === $maxColumns) {
                break;
            }
        }

        return $result;
    }

    /**
     * Establishes the WHERE clause according to the defined filters.
     *
     * @return DataBaseWhere[]
     */
    protected function getWhere()
    {
        $result = [];

        if ($this->query !== '') {
            $fields = $this->views[$this->active]->getSearchIn();
            $result[] = new DataBaseWhere($fields, Base\Utils::noHtml($this->query), 'LIKE');
        }

        foreach ($this->views[$this->active]->getFilters() as $filter) {
            $filter->getDataBaseWhere($result);
        }

        return $result;
    }

    /**
     * Load data of list view
     *
     * @param string $viewName
     * @param array  $where
     * @param int    $offset
     */
    protected function loadData($viewName, $where, $offset)
    {
        $this->views[$viewName]->loadData(false, $where, [], $offset, Base\Pagination::FS_ITEM_LIMIT);
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
            ],
        ];

        /// we search in all listviews
        foreach ($this->views as $viewName => $listView) {
            if (!$this->getSettings($viewName, 'megasearch')) {
                continue;
            }

            if (!isset($json[$viewName])) {
                $json[$viewName] = [
                    'title' => $listView->title,
                    'icon' => $this->getSettings($viewName, 'icon'),
                    'columns' => [],
                    'results' => [],
                ];
            }

            $fields = $listView->getSearchIn();
            $where = [new DataBaseWhere($fields, $this->query, 'LIKE')];
            $listView->loadData(false, $where, [], 0, Base\Pagination::FS_ITEM_LIMIT);

            $cols = $this->getTextColumns($listView, 6);
            $json[$viewName]['columns'] = $cols;

            foreach ($listView->getCursor() as $item) {
                $jItem = ['url' => $item->url()];
                foreach ($cols as $col) {
                    $jItem[$col] = $item->{$col};
                }
                $json[$viewName]['results'][] = $jItem;
            }
        }

        $this->response->setContent(json_encode($json));
    }
}
