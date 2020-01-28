<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ListFilter;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\Widget\ColumnItem;
use FacturaScripts\Dinamic\Model\PageFilter;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * View definition for its use in ListController
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ListView extends BaseView
{

    const DEFAULT_TEMPLATE = 'Master/ListView.html.twig';
    const MINI_TEMPLATE = 'Master/ListViewMin.html.twig';

    /**
     * Filter configuration preset by the user
     *
     * @var ListFilter\BaseFilter[]
     */
    public $filters = [];

    /**
     *
     * @var string
     */
    public $orderKey = '';

    /**
     * List of fields available to order by.
     *
     * @var array
     */
    public $orderOptions = [];

    /**
     * Predefined filter values selected
     *
     * @var int
     */
    public $pageFilterKey = 0;

    /**
     * List of predefined filter values
     *
     * @var PageFilter[]
     */
    public $pageFilters = [];

    /**
     *
     * @var string
     */
    public $query = '';

    /**
     * List of fields where to search in when a search is made
     *
     * @var array
     */
    public $searchFields = [];

    /**
     *
     * @var bool
     */
    public $showFilters = false;

    /**
     * ListView constructor and initialization.
     *
     * @param string $name
     * @param string $title
     * @param string $modelName
     * @param string $icon
     */
    public function __construct($name, $title, $modelName, $icon)
    {
        parent::__construct($name, $title, $modelName, $icon);
        $this->template = self::DEFAULT_TEMPLATE;
    }

    /**
     * Add an autocomplete type filter to the ListView.
     *
     * @param string $key
     * @param string $label
     * @param string $field
     * @param string $table
     * @param string $fieldcode
     * @param string $fieldtitle
     * @param array  $where
     */
    public function addFilterAutocomplete($key, $label, $field, $table, $fieldcode = '', $fieldtitle = '', $where = [])
    {
        $this->filters[$key] = new ListFilter\AutocompleteFilter($key, $field, $label, $table, $fieldcode, $fieldtitle, $where);
    }

    /**
     * Adds a boolean condition type filter to the ListView.
     *
     * @param string          $key
     * @param string          $label
     * @param string          $field
     * @param string          $operation
     * @param mixed           $matchValue
     * @param DataBaseWhere[] $default
     */
    public function addFilterCheckbox($key, $label = '', $field = '', $operation = '=', $matchValue = true, $default = [])
    {
        $this->filters[$key] = new ListFilter\CheckboxFilter($key, $field, $label, $operation, $matchValue, $default);
    }

    /**
     * Adds a date type filter to the ListView.
     *
     * @param string $key
     * @param string $label
     * @param string $field
     * @param string $operation
     */
    public function addFilterDatePicker($key, $label = '', $field = '', $operation = '>=')
    {
        $this->filters[$key] = new ListFilter\DateFilter($key, $field, $label, $operation);
    }

    /**
     * Adds a numeric type filter to the ListView.
     *
     * @param string $key
     * @param string $label
     * @param string $field
     * @param string $operation
     */
    public function addFilterNumber($key, $label = '', $field = '', $operation = '>=')
    {
        $this->filters[$key] = new ListFilter\NumberFilter($key, $field, $label, $operation);
    }

    /**
     * Adds a period type filter to the ListView.
     * (period + start date + end date)
     *
     * @param string $key
     * @param string $label
     * @param string $field
     */
    public function addFilterPeriod($key, $label, $field)
    {
        $this->filters[$key] = new ListFilter\PeriodFilter($key, $field, $label);
    }

    /**
     * Add a select type filter to a ListView.
     *
     * @param string $key
     * @param string $label
     * @param string $field
     * @param array  $values
     */
    public function addFilterSelect($key, $label, $field, $values = [])
    {
        $this->filters[$key] = new ListFilter\SelectFilter($key, $field, $label, $values);
    }

    /**
     * Add a select where type filter to a ListView.
     *
     * @param string $key
     * @param array  $values
     *
     * Example of values:
     *   [
     *    ['label' => 'Only active', 'where' => [ new DataBaseWhere('suspended', 'FALSE') ]]
     *    ['label' => 'Only suspended', 'where' => [ new DataBaseWhere('suspended', 'TRUE') ]]
     *    ['label' => 'All records', 'where' => []],
     *   ]
     */
    public function addFilterSelectWhere($key, $values)
    {
        $this->filters[$key] = new ListFilter\SelectWhereFilter($key, $values);
    }

    /**
     * Adds a field to the Order By list
     *
     * @param array  $fields
     * @param string $label
     * @param int    $default (0 = None, 1 = ASC, 2 = DESC)
     */
    public function addOrderBy(array $fields, $label, $default = 0)
    {
        $key1 = strtolower(implode('|', $fields)) . '_asc';
        $this->orderOptions[$key1] = [
            'fields' => $fields,
            'label' => $this->toolBox()->i18n()->trans($label),
            'type' => 'ASC',
        ];

        $key2 = strtolower(implode('|', $fields)) . '_desc';
        $this->orderOptions[$key2] = [
            'fields' => $fields,
            'label' => $this->toolBox()->i18n()->trans($label),
            'type' => 'DESC',
        ];

        if ($default === 2) {
            $this->setSelectedOrderBy($key2);
        } elseif ($default === 1 || empty($this->order)) {
            $this->setSelectedOrderBy($key1);
        }
    }

    /**
     * Adds a list of fields to the search in the ListView.
     * To use integer columns, use CAST(columnName AS CHAR(50)).
     *
     * @param array $fields
     */
    public function addSearchFields(array $fields)
    {
        foreach ($fields as $field) {
            $this->searchFields[] = $field;
        }
    }

    /**
     *
     * @return string
     */
    public function btnNewUrl()
    {
        $url = empty($this->model) ? '' : $this->model->url('new');
        $params = [];
        foreach (DataBaseWhere::getFieldsFilter($this->where) as $key => $value) {
            if ($value !== false) {
                $params[] = $key . '=' . $value;
            }
        }

        return empty($params) ? $url : $url . '?' . implode('&', $params);
    }

    /**
     * Removes a saved user filter.
     *
     * @param string $idfilter
     *
     * @return boolean
     */
    public function deletePageFilter($idfilter)
    {
        $pageFilter = new PageFilter();
        if ($pageFilter->loadFromCode($idfilter) && $pageFilter->delete()) {
            /// remove form the list
            unset($this->pageFilters[$idfilter]);

            return true;
        }

        return false;
    }

    /**
     * Method to export the view data.
     *
     * @param ExportManager $exportManager
     *
     * @return bool
     */
    public function export(&$exportManager): bool
    {
        if ($this->count > 0) {
            return $exportManager->addListModelPage(
                    $this->model, $this->where, $this->order, $this->offset, $this->getColumns(), $this->title
            );
        }

        return true;
    }

    /**
     *
     * @return ColumnItem[]
     */
    public function getColumns()
    {
        foreach ($this->columns as $group) {
            return $group->columns;
        }

        return [];
    }

    /**
     * Loads the data in the cursor property, according to the where filter specified.
     *
     * @param string          $code
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param int             $limit
     */
    public function loadData($code = '', $where = [], $order = [], $offset = -1, $limit = \FS_ITEM_LIMIT)
    {
        $this->offset = $offset < 0 ? $this->offset : $offset;
        $this->order = empty($order) ? $this->order : $order;
        $this->where = array_merge($where, $this->where);
        $this->count = is_null($this->model) ? 0 : $this->model->count($this->where);

        /// avoid overflow
        if ($this->offset > $this->count) {
            $this->offset = 0;
        }

        /// needed when megasearch force data reload
        $this->cursor = [];
        if ($this->count > 0) {
            $this->cursor = $this->model->all($this->where, $this->order, $this->offset, $limit);
        }
    }

    /**
     *
     * @param User|false $user
     */
    public function loadPageOptions($user = false)
    {
        parent::loadPageOptions($user);

        // load saved filters
        $orderby = ['nick' => 'ASC', 'description' => 'ASC'];
        $where = $this->getPageWhere($user);
        $pageFilter = new PageFilter();
        foreach ($pageFilter->all($where, $orderby) as $filter) {
            $this->pageFilters[$filter->id] = $filter;
        }
    }

    /**
     * Process form data needed.
     *
     * @param Request $request
     * @param string  $case
     */
    public function processFormData($request, $case)
    {
        switch ($case) {
            case 'edit':
                $name = $this->settings['modalInsert'] ?? '';
                if (empty($name)) {
                    break;
                }
                $modals = $this->getModals();
                foreach ($modals[$name]->columns as $group) {
                    $group->processFormData($this->model, $request);
                }
                break;

            case 'load':
                $this->processFormDataLoad($request);
                break;

            case 'preload':
                foreach ($this->filters as $filter) {
                    $filter->getDataBaseWhere($this->where);
                }
                break;
        }
    }

    /**
     * 
     * @param Request $request
     */
    private function processFormDataLoad($request)
    {
        $this->offset = (int) $request->request->get('offset', 0);
        $this->setSelectedOrderBy($request->request->get('order', ''));

        /// query
        $this->query = $request->request->get('query', '');
        if ('' !== $this->query) {
            $fields = implode('|', $this->searchFields);
            $this->where[] = new DataBaseWhere($fields, $this->toolBox()->utils()->noHtml($this->query), 'XLIKE');
        }

        /// select saved filter
        $this->pageFilterKey = $request->request->get('loadfilter', 0);
        if (!empty($this->pageFilterKey)) {
            // Load saved filter into page parameters
            foreach ($this->pageFilters as $item) {
                if ($item->id == $this->pageFilterKey) {
                    $request->request->add($item->filters);
                    break;
                }
            }
        }

        /// filters
        foreach ($this->filters as $filter) {
            $filter->setValueFromRequest($request);
            if ($filter->getDataBaseWhere($this->where)) {
                $this->showFilters = true;
            }
        }
    }

    /**
     * Save filter values for user/s.
     *
     * @param Request $request
     * @param User    $user
     *
     * @return int
     */
    public function savePageFilter($request, $user)
    {
        $pageFilter = new PageFilter();

        // Set values data filter
        foreach ($this->filters as $filter) {
            $name = $filter->name();
            $value = $request->request->get($name, null);
            if (!empty($value)) {
                $pageFilter->filters[$name] = $value;
            }
        }

        // If filters values its empty, don't save filter
        if (empty($pageFilter->filters)) {
            return 0;
        }

        // Set basic data and save filter
        $pageFilter->id = $request->request->get('filter-id', null);
        $pageFilter->description = $request->request->get('filter-description', '');
        $pageFilter->name = explode('-', $this->getViewName())[0];
        $pageFilter->nick = $user->nick;

        // Save and return it's all ok
        if ($pageFilter->save()) {
            $this->pageFilters[] = $pageFilter;
            return $pageFilter->id;
        }

        return 0;
    }

    /**
     * Adds assets to the asset manager.
     */
    protected function assets()
    {
        AssetManager::add('js', \FS_ROUTE . '/Dinamic/Assets/JS/ListView.js');
    }

    /**
     * Checks and establishes the selected value in the Order By
     *
     * @param string $orderKey
     */
    protected function setSelectedOrderBy($orderKey)
    {
        if (!isset($this->orderOptions[$orderKey])) {
            return;
        }

        $this->order = [];
        $option = $this->orderOptions[$orderKey];
        foreach ($option['fields'] as $field) {
            $this->order[$field] = $option['type'];
        }

        $this->orderKey = $orderKey;
    }
}
