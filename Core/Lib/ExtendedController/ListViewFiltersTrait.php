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
use FacturaScripts\Core\Lib\ListFilter\BaseFilter;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Request;
use FacturaScripts\Dinamic\Lib\ListFilter\AutocompleteFilter;
use FacturaScripts\Dinamic\Lib\ListFilter\CheckboxFilter;
use FacturaScripts\Dinamic\Lib\ListFilter\DateFilter;
use FacturaScripts\Dinamic\Lib\ListFilter\NumberFilter;
use FacturaScripts\Dinamic\Lib\ListFilter\PeriodFilter;
use FacturaScripts\Dinamic\Lib\ListFilter\SelectFilter;
use FacturaScripts\Dinamic\Lib\ListFilter\SelectWhereFilter;
use FacturaScripts\Dinamic\Model\PageFilter;

/**
 * Description of ListViewFiltersTrait
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
trait ListViewFiltersTrait
{
    /**
     * Filter configuration preset by the user
     *
     * @var BaseFilter[]
     */
    public $filters = [];

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
     * @var bool
     */
    public $showFilters = false;

    abstract public function getViewName(): string;

    /**
     * Add an autocomplete type filter to the ListView.
     *
     * @param string $key
     * @param string $label
     * @param string $field
     * @param string $table
     * @param string $field_code
     * @param string $field_title
     * @param array $where
     * @return ListView
     */
    public function addFilterAutocomplete(string $key, string $label, string $field, string $table, string $field_code = '', string $field_title = '', array $where = []): ListView
    {
        $this->filters[$key] = new AutocompleteFilter($key, $field, $label, $table, $field_code, $field_title, $where);

        return $this;
    }

    /**
     * Adds a boolean condition type filter to the ListView.
     *
     * @param string $key
     * @param string $label
     * @param string $field
     * @param string $operation
     * @param mixed $match_value
     * @param array $default
     * @return ListView
     */
    public function addFilterCheckbox(string $key, string $label = '', string $field = '', string $operation = '=', $match_value = true, array $default = []): ListView
    {
        $this->filters[$key] = new CheckboxFilter($key, $field, $label, $operation, $match_value, $default);

        return $this;
    }

    /**
     * Adds a date type filter to the ListView.
     *
     * @param string $key
     * @param string $label
     * @param string $field
     * @param string $operation
     * @param bool $dateTime
     * @return ListView
     */
    public function addFilterDatePicker(string $key, string $label = '', string $field = '', string $operation = '>=', bool $dateTime = false): ListView
    {
        $this->filters[$key] = new DateFilter($key, $field, $label, $operation, $dateTime);

        return $this;
    }

    /**
     * Adds a numeric type filter to the ListView.
     *
     * @param string $key
     * @param string $label
     * @param string $field
     * @param string $operation
     * @return ListView
     */
    public function addFilterNumber(string $key, string $label = '', string $field = '', string $operation = '>='): ListView
    {
        $this->filters[$key] = new NumberFilter($key, $field, $label, $operation);

        return $this;
    }

    /**
     * Adds a period type filter to the ListView.
     * (period + start date + end date)
     *
     * @param string $key
     * @param string $label
     * @param string $field
     * @param bool $dateTime
     * @return ListView
     */
    public function addFilterPeriod(string $key, string $label, string $field, bool $dateTime = false): ListView
    {
        $this->filters[$key] = new PeriodFilter($key, $field, $label, $dateTime);

        return $this;
    }

    /**
     * Add a select type filter to a ListView.
     *
     * @param string $key
     * @param string $label
     * @param string $field
     * @param array $values
     * @return ListView
     */
    public function addFilterSelect(string $key, string $label, string $field, array $values = []): ListView
    {
        $this->filters[$key] = new SelectFilter($key, $field, $label, $values);

        return $this;
    }

    /**
     * Add a select where type filter to a ListView.
     *
     * @param string $key
     * @param array $values
     * @param string $label
     *
     * Example of values:
     *   [
     *    ['label' => 'Only active', 'where' => [new DataBaseWhere('suspended', false)]]
     *    ['label' => 'Only suspended', 'where' => [new DataBaseWhere('suspended', true)]]
     *    ['label' => 'All records', 'where' => []],
     *   ]
     * @return ListView
     */
    public function addFilterSelectWhere(string $key, array $values, string $label = ''): ListView
    {
        $this->filters[$key] = new SelectWhereFilter($key, $values, $label);

        return $this;
    }

    /**
     * Removes a saved user filter.
     *
     * @param string $id_filter
     *
     * @return bool
     */
    public function deletePageFilter(string $id_filter): bool
    {
        $pageFilter = new PageFilter();
        if ($pageFilter->loadFromCode($id_filter) && $pageFilter->delete()) {
            // remove form the list
            unset($this->pageFilters[$id_filter]);

            return true;
        }

        return false;
    }

    /**
     * Save filter values for user/s.
     *
     * @param Request $request
     * @param User $user
     *
     * @return int
     */
    public function savePageFilter(Request $request, User $user): int
    {
        $pageFilter = new PageFilter();
        // Set values data filter
        foreach ($this->filters as $filter) {
            $name = $filter->name();
            $value = $request->request->get($name);
            if ($value !== null) {
                $pageFilter->filters[$name] = $value;
            }
        }

        // If filters values its empty, don't save filter
        if (empty($pageFilter->filters)) {
            return 0;
        }

        // Set basic data and save filter
        $pageFilter->id = $request->request->get('filter-id');
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
     * @param DataBaseWhere[] $where
     */
    private function loadSavedFilters(array $where): void
    {
        $pageFilter = new PageFilter();
        $orderBy = ['nick' => 'ASC', 'description' => 'ASC'];
        foreach ($pageFilter->all($where, $orderBy) as $filter) {
            $this->pageFilters[$filter->id] = $filter;
        }
    }

    private function sortFilters(): void
    {
        uasort($this->filters, function ($filter1, $filter2) {
            if ($filter1->ordernum === $filter2->ordernum) {
                return 0;
            }

            return $filter1->ordernum > $filter2->ordernum ? 1 : -1;
        });
    }
}
