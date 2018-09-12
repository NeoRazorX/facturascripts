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
use FacturaScripts\Core\Lib\ListFilter;
use FacturaScripts\Dinamic\Model\User;
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
        foreach ($this->views as $viewName => $view) {
            if ($this->active == $viewName) {
                $view->processRequest($this->request);
            }

            $this->loadData($viewName, $view);
        }

        // Operations with data, after execute action
        $this->execAfterAction($action);

        // final operations, like assets merge
        $this->finalStep();
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
     * @param array  $where      (Extra where conditions)
     */
    protected function addFilterAutocomplete($viewName, $key, $label, $field, $table, $fieldcode = '', $fieldtitle = '', $where = [])
    {
        $filter = new ListFilter\AutocompleteFilter($key, $field, $label, $table, $fieldcode, $fieldtitle, $where);
        $this->views[$viewName]->filters[$key] = $filter;
    }

    /**
     * Adds a boolean condition type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key        (Filter identifier)
     * @param string $label      (Human reader description)
     * @param string $field      (Field of the model to apply filter)
     * @param string $operation  (operation to perform with match value)
     * @param mixed  $matchValue (Value to match)
     */
    protected function addFilterCheckbox($viewName, $key, $label = '', $field = '', $operation = '=', $matchValue = true)
    {
        $filter = new ListFilter\CheckboxFilter($key, $field, $label, $operation, $matchValue);
        $this->views[$viewName]->filters[$key] = $filter;
    }

    /**
     * Adds a date type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     * @param string $operation (Operation to perform)
     */
    protected function addFilterDatePicker($viewName, $key, $label = '', $field = '', $operation = '>=')
    {
        $filter = new ListFilter\DateFilter($key, $field, $label, $operation);
        $this->views[$viewName]->filters[$key] = $filter;
    }

    /**
     * Adds a numeric type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key       (Filter identifier)
     * @param string $label     (Human reader description)
     * @param string $field     (Field of the table to apply filter)
     * @param string $operation (Operation to perform)
     */
    protected function addFilterNumber($viewName, $key, $label = '', $field = '', $operation = '>=')
    {
        $filter = new ListFilter\NumberFilter($key, $field, $label, $operation);
        $this->views[$viewName]->filters[$key] = $filter;
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
        $filter = new ListFilter\SelectFilter($key, $field, $label, $values);
        $this->views[$viewName]->filters[$key] = $filter;
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
        foreach ($fields as $field) {
            $this->views[$viewName]->searchFields[] = $field;
        }
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
        $view = new ListView($viewName, $title, self::MODEL_NAMESPACE . $modelName, $icon);
        $this->addCustomView($viewName, $view);
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
                break;
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

            $result[] = $col->widget->fieldname;
            if (count($result) === $maxColumns) {
                break;
            }
        }

        return $result;
    }

    /**
     * 
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $view->loadData();
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
                    'icon' => $listView->icon,
                    'columns' => [],
                    'results' => [],
                ];
            }

            $fields = $listView->getSearchIn();
            $where = [new DataBaseWhere($fields, $this->query, 'LIKE')];
            $listView->loadData(false, $where, [], 0, FS_ITEM_LIMIT);

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
