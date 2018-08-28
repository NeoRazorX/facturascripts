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
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Lib\ExportManager;

/**
 * View definition for its use in ListController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListView extends BaseView
{

    /**
     * Order constants
     */
    const ICON_ASC = 'fa-sort-amount-up';
    const ICON_DESC = 'fa-sort-amount-down';

    /**
     *
     * @var DivisaTools
     */
    public $divisaTools;

    /**
     * Filter configuration preset by the user
     *
     * @var ListFilter[]
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
     * List of fields where to search in when a search is made
     *
     * @var array
     */
    public $searchFields;

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
        $this->divisaTools = new DivisaTools();
        $this->filters = [];
        $this->orderOptions = [];
        $this->searchFields = [];
        $this->template = 'Master/ListView.html.twig';
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
     * Loads the data in the cursor property, according to the where filter specified.
     *
     * @param mixed           $code
     * @param DataBaseWhere[] $where
     * @param array           $order
     * @param int             $offset
     * @param int             $limit
     */
    public function loadData($code = false, $where = [], $order = [], $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $this->order = empty($order) ? $this->order : $order;
        $this->count = is_null($this->model) ? 0 : $this->model->count($where);

        /// needed when megasearch force data reload
        $this->cursor = [];
        if ($this->count > 0) {
            $this->cursor = $this->model->all($where, $this->order, $offset, $limit);
        }

        /// store values where & offset for exportation
        $this->offset = $offset;
        $this->where = $where;
    }

    /**
     * Checks and establishes the selected value in the Order By
     *
     * @param string $orderKey
     */
    public function setSelectedOrderBy($orderKey)
    {
        if (!isset($this->orderOptions[$orderKey])) {
            return;
        }

        $option = $this->orderOptions[$orderKey];
        foreach ($option['fields'] as $field) {
            $this->order[$field] = $option['type'];
        }

        $this->orderKey = $orderKey;
    }
}
