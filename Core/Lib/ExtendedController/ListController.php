<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller that lists the data in table mode
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Cristo M. Estévez Hernández   <cristom.estevez@gmail.com>
 */
abstract class ListController extends BaseController
{
    /**
     * Initializes all the objects and properties.
     *
     * @param string $className
     * @param string $uri
     */
    public function __construct(string $className, string $uri = '')
    {
        parent::__construct($className, $uri);
        $this->setTemplate('Master/ListController');
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Get action to execute
        $action = $this->request->request->get('action', $this->request->query->get('action', ''));

        // Execute actions before loading data
        if (false === $this->execPreviousAction($action) || false === $this->pipeFalse('execPreviousAction', $action)) {
            return;
        }

        // Load filter saved and data for every view
        foreach ($this->views as $viewName => $view) {
            if ($this->active == $viewName) {
                $view->processFormData($this->request, 'load');
            } else {
                $view->processFormData($this->request, 'preload');
            }

            $this->loadData($viewName, $view);
            $this->pipeFalse('loadData', $viewName, $view);
        }

        // Execute actions after loading data
        $this->execAfterAction($action);
        $this->pipeFalse('execAfterAction', $action);
    }

    /**
     * Adds a new color option to the list.
     *
     * @param string $viewName
     * @param string $fieldName
     * @param mixed $value
     * @param string $color
     * @param string $title
     */
    protected function addColor(string $viewName, string $fieldName, $value, string $color, string $title = '')
    {
        $this->views[$viewName]->addColor($fieldName, $value, $color, $title);
    }

    /**
     * Add an autocomplete type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key (Filter identifier)
     * @param string $label (Human reader description)
     * @param string $field (Field of the model to apply filter)
     * @param string $table (Table to search)
     * @param string $fieldcode (Primary column of the table to search and match)
     * @param string $fieldtitle (Column to show name or description)
     * @param array $where (Extra where conditions)
     */
    protected function addFilterAutocomplete(string $viewName, string $key, string $label, string $field, string $table, string $fieldcode = '', string $fieldtitle = '', array $where = [])
    {
        $this->views[$viewName]->addFilterAutocomplete($key, $label, $field, $table, $fieldcode, $fieldtitle, $where);
    }

    /**
     * Adds a boolean condition type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key (Filter identifier)
     * @param string $label (Human reader description)
     * @param string $field (Field of the model to apply filter)
     * @param string $operation (operation to perform with match value)
     * @param mixed $matchValue (Value to match)
     * @param DataBaseWhere[] $default (where to apply when filter is empty)
     */
    protected function addFilterCheckbox(string $viewName, string $key, string $label = '', string $field = '', string $operation = '=', $matchValue = true, array $default = [])
    {
        $this->views[$viewName]->addFilterCheckbox($key, $label, $field, $operation, $matchValue, $default);
    }

    /**
     * Adds a date type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key (Filter identifier)
     * @param string $label (Human reader description)
     * @param string $field (Field of the table to apply filter)
     * @param string $operation (Operation to perform)
     */
    protected function addFilterDatePicker(string $viewName, string $key, string $label = '', string $field = '', string $operation = '>=')
    {
        $this->views[$viewName]->addFilterDatePicker($key, $label, $field, $operation);
    }

    /**
     * Adds a numeric type filter to the ListView.
     *
     * @param string $viewName
     * @param string $key (Filter identifier)
     * @param string $label (Human reader description)
     * @param string $field (Field of the table to apply filter)
     * @param string $operation (Operation to perform)
     */
    protected function addFilterNumber(string $viewName, string $key, string $label = '', string $field = '', string $operation = '>=')
    {
        $this->views[$viewName]->addFilterNumber($key, $label, $field, $operation);
    }

    /**
     * Adds a period type filter to the ListView.
     * (period + start date + end date)
     *
     * @param string $viewName
     * @param string $key (Filter identifier)
     * @param string $label (Human reader description)
     * @param string $field (Field of the table to apply filter)
     */
    protected function addFilterPeriod(string $viewName, string $key, string $label, string $field)
    {
        $this->views[$viewName]->addFilterPeriod($key, $label, $field);
    }

    /**
     * Add a select type filter to a ListView.
     *
     * @param string $viewName
     * @param string $key (Filter identifier)
     * @param string $label (Human reader description)
     * @param string $field (Field of the table to apply filter)
     * @param array $values (Values to show)
     */
    protected function addFilterSelect(string $viewName, string $key, string $label, string $field, array $values = [])
    {
        $this->views[$viewName]->addFilterSelect($key, $label, $field, $values);
    }

    /**
     * Add a select where type filter to a ListView.
     *
     * @param string $viewName
     * @param string $key (Filter identifier)
     * @param array $values (Values to show)
     * @param string $label (Human reader description)
     *
     * Example of values:
     *   [
     *    ['label' => 'Only active', 'where' => [ new DataBaseWhere('suspended', 'FALSE') ]]
     *    ['label' => 'Only suspended', 'where' => [ new DataBaseWhere('suspended', 'TRUE') ]]
     *    ['label' => 'All records', 'where' => []],
     *   ]
     */
    protected function addFilterSelectWhere(string $viewName, string $key, array $values, string $label = '')
    {
        $this->views[$viewName]->addFilterSelectWhere($key, $values, $label);
    }

    /**
     * Adds an order field to the ListView.
     *
     * @param string $viewName
     * @param array $fields
     * @param string $label
     * @param int $default (0 = None, 1 = ASC, 2 = DESC)
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
     * @param array $fields
     */
    protected function addSearchFields(string $viewName, array $fields)
    {
        $this->views[$viewName]->addSearchFields($fields);
    }

    /**
     * Creates and adds a ListView to the controller.
     *
     * @param string $viewName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $icon
     */
    protected function addView(string $viewName, string $modelName, string $viewTitle = '', string $icon = 'fas fa-search')
    {
        $title = empty($viewTitle) ? $this->title : $viewTitle;
        $view = new ListView($viewName, $title, self::MODEL_NAMESPACE . $modelName, $icon);
        $this->addCustomView($viewName, $view);
        $this->setSettings($viewName, 'btnPrint', true);
        $this->setSettings($viewName, 'card', false);
        $this->setSettings($viewName, 'megasearch', true);
    }

    /**
     * Removes the selected page filter.
     */
    protected function deleteFilterAction()
    {
        $idfilter = $this->request->request->get('loadfilter', 0);
        if ($this->views[$this->active]->deletePageFilter($idfilter)) {
            $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
            $this->request->request->remove('loadfilter');
            return;
        }

        $this->toolBox()->i18nLog()->warning('record-deleted-error');
    }

    /**
     * Runs the controller actions after data read.
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'delete-ok':
                $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
                break;

            case 'export':
                $this->exportAction();
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

            case 'delete-filter':
                $this->deleteFilterAction();
                break;

            case 'save-filter':
                $this->saveFilterAction();
                break;
        }

        return true;
    }

    protected function exportAction()
    {
        if (false === $this->views[$this->active]->settings['btnPrint']
            || false === $this->permissions->allowExport) {
            $this->toolBox()->i18nLog()->warning('no-print-permission');
            return;
        }

        $this->setTemplate(false);
        $codes = $this->request->request->get('code');
        $option = $this->request->get('option', '');
        $this->exportManager->newDoc($option);
        $this->views[$this->active]->export($this->exportManager, $codes);
        $this->exportManager->show($this->response);
    }

    /**
     * Returns the where filter to apply to obtain the data created by the active user.
     *
     * @param ModelClass $model
     *
     * @return DataBaseWhere[]
     */
    protected function getOwnerFilter($model): array
    {
        $where = [];

        if (property_exists($model, 'nick')) {
            // DatabaseWhere applies parentheses grouping the ORs
            // result: (`nick` = 'username' OR `nick` IS NULL OR `codagente` = 'agent') AND [... user filters]
            $where[] = new DataBaseWhere('nick', $this->user->nick);
            $where[] = new DataBaseWhere('nick', null, 'IS', 'OR');
            if (property_exists($model, 'codagente') && $this->user->codagente) {
                $where[] = new DataBaseWhere('codagente', $this->user->codagente, '=', 'OR');
            }
            return $where;
        }

        if (property_exists($model, 'codagente')) {
            $where[] = new DataBaseWhere('codagente', $this->user->codagente);
        }
        return $where;
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        $where = $this->permissions->onlyOwnerData ? $this->getOwnerFilter($view->model) : [];
        $view->loadData('', $where);
    }

    /**
     * Returns a JSON response to MegaSearch.
     */
    protected function megaSearchAction()
    {
        $this->setTemplate(false);
        $json = [];

        // we search in all listviews
        foreach ($this->views as $viewName => $listView) {
            if (false === $this->getSettings($viewName, 'megasearch') || empty($listView->searchFields)) {
                continue;
            }

            $json[$viewName] = [
                'title' => $listView->title,
                'icon' => $listView->icon,
                'columns' => $this->megaSearchColumns($listView),
                'results' => []
            ];

            $fields = implode('|', $listView->searchFields);
            $where = [new DataBaseWhere($fields, $this->request->get('query', ''), 'LIKE')];
            $listView->loadData(false, $where);
            foreach ($listView->cursor as $model) {
                $item = ['url' => $model->url()];
                foreach ($listView->getColumns() as $col) {
                    if (false === $col->hidden()) {
                        $item[$col->widget->fieldname] = $col->widget->plainText($model);
                    }
                }

                $json[$viewName]['results'][] = $item;
            }
        }

        $this->response->setContent(json_encode($json));
    }

    /**
     * Returns columns title for megaSearchAction function.
     *
     * @param ListView $view
     *
     * @return array
     */
    private function megaSearchColumns($view): array
    {
        $result = [];
        foreach ($view->getColumns() as $col) {
            if (false === $col->hidden()) {
                $result[] = $this->toolBox()->i18n()->trans($col->title);
            }
        }

        return $result;
    }

    /**
     * Saves filter values for active view and user.
     */
    protected function saveFilterAction()
    {
        $idFilter = $this->views[$this->active]->savePageFilter($this->request, $this->user);
        if (!empty($idFilter)) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');

            // load filters in request
            $this->request->request->set('loadfilter', $idFilter);
        }
    }
}
