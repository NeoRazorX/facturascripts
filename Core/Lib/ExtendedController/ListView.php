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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Lib\ExportManager;
use FacturaScripts\Core\Lib\ListFilter\BaseFilter;
use Symfony\Component\HttpFoundation\Request;

/**
 * View definition for its use in ListController
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ListView extends BaseView
{

    /**
     * Order constants
     */
    const ICON_ASC = 'fa-arrow-up';
    const ICON_DESC = 'fa-arrow-down';

    /**
     * Filter configuration preset by the user
     *
     * @var BaseFilter[]
     */
    public $filters;

    /**
     *
     * @var string
     */
    public $orderKey;

    /**
     * List of fields available to order by.
     *
     * @var array
     */
    public $orderOptions;

    /**
     *
     * @var string
     */
    public $query;

    /**
     * List of fields where to search in when a search is made
     *
     * @var array
     */
    public $searchFields;

    /**
     *
     * @var bool
     */
    public $showFilters;

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
        $this->filters = [];
        $this->orderOptions = [];
        $this->query = '';
        $this->searchFields = [];
        $this->showFilters = false;
        $this->template = 'Master/ListView.html.twig';
        static::$assets['js'][] = FS_ROUTE . '/Dinamic/Assets/JS/ListView.js';
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
            'icon' => self::ICON_ASC,
            'label' => static::$i18n->trans($label),
            'type' => 'ASC',
        ];

        $key2 = strtolower(implode('|', $fields)) . '_desc';
        $this->orderOptions[$key2] = [
            'fields' => $fields,
            'icon' => self::ICON_DESC,
            'label' => static::$i18n->trans($label),
            'type' => 'DESC',
        ];

        switch ($default) {
            case 1:
                $this->setSelectedOrderBy($key1);
                break;

            case 2:
                $this->setSelectedOrderBy($key2);
                break;

            default:
                if (empty($this->order)) {
                    $this->setSelectedOrderBy($key1);
                }
        }
    }

    /**
     * Method to export the view data.
     *
     * @param ExportManager $exportManager
     */
    public function export(&$exportManager)
    {
        if ($this->count > 0) {
            $exportManager->generateListModelPage(
                $this->model, $this->where, $this->order, $this->offset, $this->getColumns(), $this->title
            );
        }
    }

    /**
     *
     * @return array
     */
    public function getColumns()
    {
        $keys = array_keys($this->pageOption->columns);
        if (empty($keys)) {
            return [];
        }

        $key = $keys[0];
        return $this->pageOption->columns[$key]->columns;
    }

    /**
     * Loads the data in the cursor property, according to the where filter specified.
     *
     * @param mixed           $code
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param int             $limit
     */
    public function loadData($code = false, $where = [], $order = [], $offset = -1, $limit = FS_ITEM_LIMIT)
    {
        $this->offset = ($offset < 0) ? $this->offset : $offset;
        $this->order = empty($order) ? $this->order : $order;

        $finalWhere = empty($where) ? $this->where : $where;
        $this->count = is_null($this->model) ? 0 : $this->model->count($finalWhere);

        /// needed when megasearch force data reload
        $this->cursor = [];
        if ($this->count > 0) {
            $this->cursor = $this->model->all($finalWhere, $this->order, $this->offset, $limit);
        }

        $this->where = $finalWhere;
    }

    /**
     * Process form data needed.
     *
     * @param Request $request
     * @param string  $case
     */
    public function processFormData($request, $case)
    {
        if ($case !== 'load') {
            return;
        }

        $this->offset = (int) $request->request->get('offset', 0);
        $this->setSelectedOrderBy($request->request->get('order', ''));

        /// query
        $this->query = $request->request->get('query', '');
        if ('' !== $this->query) {
            $fields = implode('|', $this->searchFields);
            $this->where[] = new DataBaseWhere($fields, Utils::noHtml($this->query), 'LIKE');
        }

        /// filters
        foreach ($this->filters as $filter) {
            $filter->value = $request->request->get($filter->name());
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
