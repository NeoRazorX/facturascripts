<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\Widget\ColumnItem;
use FacturaScripts\Dinamic\Model\TotalModel;
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

    use ListViewFiltersTrait;

    const DEFAULT_TEMPLATE = 'Master/ListView.html.twig';

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
     * @var array
     */
    public $totalAmounts = [];

    /**
     * Adds a field to the Order By list
     *
     * @param array  $fields
     * @param string $label
     * @param int    $default (0 = None, 1 = ASC, 2 = DESC)
     */
    public function addOrderBy(array $fields, $label, $default = 0)
    {
        $key1 = \strtolower(\implode('|', $fields)) . '_asc';
        $this->orderOptions[$key1] = [
            'fields' => $fields,
            'label' => $this->toolBox()->i18n()->trans($label),
            'type' => 'ASC'
        ];

        $key2 = \strtolower(\implode('|', $fields)) . '_desc';
        $this->orderOptions[$key2] = [
            'fields' => $fields,
            'label' => $this->toolBox()->i18n()->trans($label),
            'type' => 'DESC'
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

        return empty($params) ? $url : $url . '?' . \implode('&', $params);
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
        $this->where = \array_merge($where, $this->where);
        $this->count = \is_null($this->model) ? 0 : $this->model->count($this->where);

        /// avoid overflow
        if ($this->offset > $this->count) {
            $this->offset = 0;
        }

        /// needed when megasearch force data reload
        $this->cursor = [];
        if ($this->count > 0) {
            $this->cursor = $this->model->all($this->where, $this->order, $this->offset, $limit);
            $this->loadTotalAmounts();
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
        $where = $this->getPageWhere($user);
        $this->loadSavedFilters($where);
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
                $this->sortFilters();
                $this->processFormDataLoad($request);
                break;

            case 'preload':
                $this->sortFilters();
                foreach ($this->filters as $filter) {
                    $filter->getDataBaseWhere($this->where);
                }
                break;
        }
    }

    /**
     * Adds assets to the asset manager.
     */
    protected function assets()
    {
        AssetManager::add('js', \FS_ROUTE . '/Dinamic/Assets/JS/ListView.js');
    }

    private function loadTotalAmounts()
    {
        $tableName = \count($this->cursor) > 1 && \method_exists($this->model, 'tableName') ? $this->model->tableName() : '';
        if (empty($tableName)) {
            return;
        }

        foreach ($this->getColumns() as $col) {
            if ($col->hidden() || false === $col->widget->showTableTotals()) {
                continue;
            }

            $pageTotalAmount = 0;
            foreach ($this->cursor as $model) {
                $pageTotalAmount += $model->{$col->widget->fieldname};
            }

            $this->totalAmounts[$col->widget->fieldname] = [
                'title' => $col->title,
                'page' => $pageTotalAmount,
                'total' => TotalModel::sum($tableName, $col->widget->fieldname, $this->where)
            ];
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
            $fields = \implode('|', $this->searchFields);
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
     * Checks and establishes the selected value in the Order By
     *
     * @param string $orderKey
     */
    protected function setSelectedOrderBy($orderKey)
    {
        if (isset($this->orderOptions[$orderKey])) {
            $this->order = [];
            $option = $this->orderOptions[$orderKey];
            foreach ($option['fields'] as $field) {
                $this->order[$field] = $option['type'];
            }

            $this->orderKey = $orderKey;
        }
    }
}
