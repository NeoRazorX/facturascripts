<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Lib\Widget\ColumnItem;
use FacturaScripts\Dinamic\Lib\Widget\RowStatus;
use FacturaScripts\Dinamic\Model\User;

/**
 * View definition for its use in ListController
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListView extends BaseView
{
    use ListViewFiltersTrait;

    const DEFAULT_TEMPLATE = 'Master/ListView.html.twig';

    /** @var string */
    public $orderKey = '';

    /** @var array */
    public $orderOptions = [];

    /** @var string */
    public $query = '';

    /** @var array */
    public $searchFields = [];

    /** @var array */
    public $totalAmounts = [];

    public function addColor(string $fieldName, $value, string $color, string $title = ''): ListView
    {
        if (false === isset($this->rows['status'])) {
            $this->rows['status'] = new RowStatus([]);
        }

        $this->rows['status']->options[] = [
            'tag' => 'option',
            'children' => [],
            'color' => $color,
            'fieldname' => $fieldName,
            'text' => $value,
            'title' => $title
        ];

        return $this;
    }

    /**
     * Adds a field to the Order By list
     *
     * @param array $fields
     * @param string $label
     * @param int $default (0 = None, 1 = ASC, 2 = DESC)
     * @return ListView
     */
    public function addOrderBy(array $fields, string $label, int $default = 0): ListView
    {
        $key1 = count($this->orderOptions);
        $this->orderOptions[$key1] = [
            'fields' => $fields,
            'label' => Tools::lang()->trans($label),
            'type' => 'ASC'
        ];

        $key2 = count($this->orderOptions);
        $this->orderOptions[$key2] = [
            'fields' => $fields,
            'label' => Tools::lang()->trans($label),
            'type' => 'DESC'
        ];

        if ($default === 2) {
            $this->setSelectedOrderBy($key2);
        } elseif ($default === 1 || empty($this->order)) {
            $this->setSelectedOrderBy($key1);
        }

        return $this;
    }

    /**
     * Adds a list of fields to the search in the ListView.
     * To use integer columns, use CAST(columnName AS CHAR(50)).
     *
     * @param array $fields
     * @return ListView
     */
    public function addSearchFields(array $fields): ListView
    {
        foreach ($fields as $field) {
            $this->searchFields[] = $field;
        }

        return $this;
    }

    public function btnNewUrl(): string
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
     * Method to export the view data.
     *
     * @param ExportManager $exportManager
     * @param mixed $codes
     *
     * @return bool
     */
    public function export(&$exportManager, $codes): bool
    {
        // no data
        if ($this->count < 1) {
            return true;
        }

        // selected items?
        if (is_array($codes) && count($codes) > 0 && $this->model instanceof ModelClass) {
            foreach ($this->cursor as $model) {
                if (false === in_array($model->primaryColumnValue(), $codes)) {
                    continue;
                }

                if ($model instanceof BusinessDocument) {
                    $exportManager->addBusinessDocPage($model);
                    continue;
                }

                $exportManager->addModelPage($model, $this->getColumns(), $this->title);
            }
            return false;
        }

        // print list
        $exportManager->addListModelPage(
            $this->model, $this->where, $this->order, $this->offset, $this->getColumns(), $this->title
        );

        // print totals
        if ($this->totalAmounts) {
            $total = [];
            foreach ($this->totalAmounts as $key => $value) {
                $total[$key] = $value['total'];
            }
            $exportManager->addTablePage(array_keys($total), [$total]);
        }

        return true;
    }

    /**
     * @return ColumnItem[]
     */
    public function getColumns(): array
    {
        foreach ($this->columns as $group) {
            return $group->columns;
        }

        return [];
    }

    /**
     * Loads the data in the cursor property, according to the where filter specified.
     *
     * @param string $code
     * @param DataBaseWhere[] $where
     * @param array $order
     * @param int $offset
     * @param int $limit
     */
    public function loadData($code = '', $where = [], $order = [], $offset = -1, $limit = -1)
    {
        $this->offset = $offset < 0 ? $this->offset : $offset;
        $this->order = empty($order) ? $this->order : $order;
        $this->where = array_merge($where, $this->where);
        $this->count = is_null($this->model) ? 0 : $this->model->count($this->where);

        // avoid overflow
        if ($this->offset > $this->count) {
            $this->offset = 0;
        }

        // check limit
        if ($limit < 0) {
            $limit = $this->settings['itemLimit'];
        } elseif ($limit != $this->settings['itemLimit']) {
            $this->settings['itemLimit'] = $limit;
        }

        // needed when mega-search force data reload
        $this->cursor = [];
        if ($this->count > 0) {
            $this->cursor = $this->model->all($this->where, $this->order, $this->offset, $limit);
            $this->loadTotalAmounts();
        }
    }

    /**
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
     * @param string $case
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
        AssetManager::addJs(FS_ROUTE . '/Dinamic/Assets/JS/ListView.js?v=2');
    }

    private function loadTotalAmounts(): void
    {
        if (count($this->cursor) <= 1) {
            return;
        }

        $modelFields = $this->model->getModelFields();
        foreach ($this->getColumns() as $col) {
            // si la columna está oculta o es de un tipo que no hay que mostrar totales, entonces no se procesa
            if ($col->hidden() || false === $col->widget->showTableTotals()) {
                continue;
            }

            // si la columna no pertenece al modelo, entonces no se procesa
            if (false === array_key_exists($col->widget->fieldname, $modelFields)) {
                continue;
            }

            // calculamos el total de la página
            $pageTotalAmount = 0;
            foreach ($this->cursor as $model) {
                $pageTotalAmount += $model->{$col->widget->fieldname};
            }

            $this->totalAmounts[$col->widget->fieldname] = [
                'title' => $col->title,
                'page' => $pageTotalAmount,
                'total' => $this->model->totalSum($col->widget->fieldname, $this->where)
            ];
        }
    }

    private function processFormDataLoad(Request $request)
    {
        $this->offset = (int)$request->request->get('offset', 0);
        $this->setSelectedOrderBy($request->request->get('order', ''));

        // query
        $this->query = $request->request->get('query', '');
        if ('' !== $this->query) {
            $fields = implode('|', $this->searchFields);
            $this->where[] = new DataBaseWhere($fields, Tools::noHtml($this->query), 'XLIKE');
        }

        // filtro guardado seleccionado?
        $this->pageFilterKey = $request->request->get('loadfilter', 0);
        if ($this->pageFilterKey) {
            $filterLoad = [];
            // cargamos los valores en la request
            foreach ($this->pageFilters as $item) {
                if ($item->id == $this->pageFilterKey) {
                    $request->request->add($item->filters);
                    $filterLoad = $item->filters;
                    break;
                }
            }
            // aplicamos los valores de la request a los filtros
            foreach ($this->filters as $filter) {
                $key = 'filter' . $filter->key;
                $filter->readonly = true;
                if (array_key_exists($key, $filterLoad)) {
                    $filter->setValueFromRequest($request);
                    if ($filter->getDataBaseWhere($this->where)) {
                        $this->showFilters = true;
                    }
                }
            }
            return;
        }

        // filters
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
    protected function setSelectedOrderBy(string $orderKey): void
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
