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

use Exception;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Widget\VisualItem;
use FacturaScripts\Core\Model\Base\ModelClass;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\User;

/**
 * Description of BaseController
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class BaseController extends Controller
{
    const MODEL_NAMESPACE = '\\FacturaScripts\\Dinamic\\Model\\';

    /**
     * Indicates the active view.
     *
     * @var string
     */
    public $active;

    /**
     * Model to use with select and autocomplete filters.
     *
     * @var CodeModel
     */
    public $codeModel;

    /**
     * Indicates current view, when drawing.
     *
     * @var string
     */
    private $current;

    /**
     * Object to export data.
     *
     * @var ExportManager
     */
    public $exportManager;

    /**
     * List of views displayed by the controller.
     *
     * @var BaseView[]
     */
    public $views = [];

    /**
     * Inserts the views or tabs to display.
     */
    abstract protected function createViews();

    /**
     * Loads the data to display.
     *
     * @param string $viewName
     * @param BaseView $view
     */
    abstract protected function loadData($viewName, $view);

    /**
     * Initializes all the objects and properties.
     *
     * @param string $className
     * @param string $uri
     */
    public function __construct(string $className, string $uri = '')
    {
        parent::__construct($className, $uri);
        $activeTabGet = $this->request->query->get('activetab', '');
        $this->active = $this->request->request->get('activetab', $activeTabGet);
        $this->codeModel = new CodeModel();
        $this->exportManager = new ExportManager();
    }

    /**
     * Adds a new button to the tab.
     *
     * @param string $viewName
     * @param array $btnArray
     * @return BaseView
     */
    public function addButton(string $viewName, array $btnArray): BaseView
    {
        if (!array_key_exists($viewName, $this->views)) {
            throw new Exception('View not found: ' . $viewName);
        }

        $rowType = isset($btnArray['row']) ? 'footer' : 'actions';
        $row = $this->views[$viewName]->getRow($rowType);
        if ($row) {
            $row->addButton($btnArray);
        }

        return $this->tab($viewName);
    }

    /**
     * @param string $viewName
     * @param BaseView $view
     * @return BaseView
     */
    public function addCustomView(string $viewName, BaseView $view): BaseView
    {
        if ($viewName !== $view->getViewName()) {
            throw new Exception('$viewName must be equals to $view->name');
        }

        $view->loadPageOptions($this->user);

        $this->views[$viewName] = $view;
        if (empty($this->active)) {
            $this->active = $viewName;
        }

        return $view;
    }

    public function getCurrentView(): BaseView
    {
        return $this->tab($this->current);
    }

    /**
     * Returns the name assigned to the main view
     *
     * @return string
     */
    public function getMainViewName(): string
    {
        foreach (array_keys($this->views) as $key) {
            return $key;
        }

        return '';
    }

    /**
     * Returns the configuration value for the indicated view.
     *
     * @param string $viewName
     * @param string $property
     * @return mixed
     */
    public function getSettings(string $viewName, string $property)
    {
        return $this->tab($viewName)->settings[$property] ?? null;
    }

    /**
     * Return the value for a field in the model of the view.
     *
     * @param string $viewName
     * @param string $fieldName
     * @return mixed
     */
    public function getViewModelValue(string $viewName, string $fieldName)
    {
        return $this->tab($viewName)->model->{$fieldName} ?? null;
    }

    public function listView(string $viewName): ListView
    {
        if (isset($this->views[$viewName]) && $this->views[$viewName] instanceof ListView) {
            return $this->views[$viewName];
        }

        throw new Exception('ListView not found: ' . $viewName);
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

        VisualItem::setToken($this->multiRequestProtection->newToken());

        // Create the views to display
        $this->createViews();
        $this->pipe('createViews');
    }

    public function setCurrentView(string $viewName): void
    {
        $this->current = $viewName;
    }

    /**
     * Set value for setting of a view
     *
     * @param string $viewName
     * @param string $property
     * @param mixed $value
     * @return BaseView
     */
    public function setSettings(string $viewName, string $property, $value): BaseView
    {
        return $this->tab($viewName)->setSettings($property, $value);
    }

    public function tab(string $viewName): BaseView
    {
        if (isset($this->views[$viewName])) {
            return $this->views[$viewName];
        }

        throw new Exception('View not found: ' . $viewName);
    }

    /**
     * Run the autocomplete action.
     * Returns a JSON string for the searched values.
     *
     * @return array
     */
    protected function autocompleteAction(): array
    {
        $data = $this->requestGet(['field', 'fieldcode', 'fieldfilter', 'fieldtitle', 'formname', 'source', 'strict', 'term']);
        if ($data['source'] == '') {
            return $this->getAutocompleteValues($data['formname'], $data['field']);
        }

        $where = [];
        foreach (DataBaseWhere::applyOperation($data['fieldfilter'] ?? '') as $field => $operation) {
            $value = $this->request->get($field);
            $where[] = new DataBaseWhere($field, $value, '=', $operation);
        }

        $results = [];
        foreach ($this->codeModel->search($data['source'], $data['fieldcode'], $data['fieldtitle'], $data['term'], $where) as $value) {
            $results[] = ['key' => Tools::fixHtml($value->code), 'value' => Tools::fixHtml($value->description)];
        }

        if (empty($results) && '0' == $data['strict']) {
            $results[] = ['key' => $data['term'], 'value' => $data['term']];
        } elseif (empty($results)) {
            $results[] = ['key' => null, 'value' => Tools::lang()->trans('no-data')];
        }

        return $results;
    }

    /**
     * Returns true if the active user has permission to view the information
     * of the active record in the informed model.
     *
     * @param ModelClass $model
     *
     * @return bool
     */
    protected function checkOwnerData($model): bool
    {
        if (false === $this->permissions->onlyOwnerData || empty($model->primaryColumnValue())) {
            return true;
        }

        // si el modelo tiene nick, comprobamos nick
        if (property_exists($model, 'nick')) {
            if (null === $model->nick || $model->nick === $this->user->nick) {
                return true;
            }
            if (property_exists($model, 'codagente') && $this->user->codagente) {
                return $model->codagente === $this->user->codagente;
            }
            return false;
        }

        // si el modelo tiene agente, comprobamos agente
        if (property_exists($model, 'codagente')) {
            return $model->codagente === $this->user->codagente;
        }

        // si no hay nada en que apoyarse, permitimos
        return true;
    }

    /**
     * Run the datalist action.
     * Returns a JSON string for the searched values.
     *
     * @return array
     */
    protected function datalistAction(): array
    {
        $data = $this->requestGet(['field', 'fieldcode', 'fieldfilter', 'fieldtitle', 'formname', 'source', 'term']);

        $where = [];
        foreach (DataBaseWhere::applyOperation($data['fieldfilter'] ?? '') as $field => $operation) {
            $where[] = new DataBaseWhere($field, $data['term'], '=', $operation);
        }

        $results = [];
        foreach ($this->codeModel->all($data['source'], $data['fieldcode'], $data['fieldtitle'], false, $where) as $value) {
            $results[] = ['key' => $value->code, 'value' => $value->description];
        }
        return $results;
    }

    /**
     * Action to delete data.
     *
     * @return bool
     */
    protected function deleteAction()
    {
        // check user permissions
        if (false === $this->permissions->allowDelete || false === $this->views[$this->active]->settings['btnDelete']) {
            Tools::log()->warning('not-allowed-delete');
            return false;
        } elseif (false === $this->validateFormToken()) {
            return false;
        }

        $model = $this->views[$this->active]->model;
        $codes = $this->request->request->getArray('codes');
        $code = $this->request->request->get('code');
        if (empty($codes) && empty($code)) {
            Tools::log()->warning('no-selected-item');
            return false;
        }

        if (false === empty($codes) && is_array($codes)) {
            $this->dataBase->beginTransaction();

            // deleting multiples rows
            $numDeletes = 0;
            foreach ($codes as $cod) {
                if ($model->loadFromCode($cod) && $model->delete()) {
                    ++$numDeletes;
                    continue;
                }

                // ¿error?
                $this->dataBase->rollback();
                break;
            }

            $model->clear();
            $this->dataBase->commit();
            if ($numDeletes > 0) {
                Tools::log()->notice('record-deleted-correctly');
                return true;
            }
        } elseif ($model->loadFromCode($code) && $model->delete()) {
            // deleting a single row
            Tools::log()->notice('record-deleted-correctly');
            $model->clear();
            return true;
        }

        Tools::log()->warning('record-deleted-error');
        $model->clear();
        return false;
    }

    protected function exportAction()
    {
        if (false === $this->views[$this->active]->settings['btnPrint'] ||
            false === $this->permissions->allowExport) {
            Tools::log()->warning('no-print-permission');
            return;
        }

        $this->setTemplate(false);
        $this->exportManager->newDoc(
            $this->request->get('option', ''),
            $this->title,
            (int)$this->request->request->get('idformat', ''),
            $this->request->request->get('langcode', '')
        );

        foreach ($this->views as $selectedView) {
            if (false === $selectedView->settings['active']) {
                continue;
            }

            $codes = $this->request->request->getArray('codes');
            if (false === $selectedView->export($this->exportManager, $codes)) {
                break;
            }
        }
        $this->exportManager->show($this->response);
    }

    /**
     * Return values from Widget Values for autocomplete action
     *
     * @param string $viewName
     * @param string $fieldName
     *
     * @return array
     */
    protected function getAutocompleteValues(string $viewName, string $fieldName): array
    {
        $result = [];
        $column = $this->views[$viewName]->columnForField($fieldName);
        if (!empty($column)) {
            foreach ($column->widget->values as $value) {
                $result[] = ['key' => Tools::lang()->trans($value['title']), 'value' => $value['value']];
            }
        }
        return $result;
    }

    /**
     * Return array with parameters values
     *
     * @param array $keys
     *
     * @return array
     */
    protected function requestGet(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->request->get($key);
        }
        return $result;
    }

    /**
     * Run the select action.
     * Returns a JSON string for the searched values.
     *
     * @return array
     */
    protected function selectAction(): array
    {
        $required = (bool)$this->request->get('required', false);
        $data = $this->requestGet(['field', 'fieldcode', 'fieldfilter', 'fieldtitle', 'formname', 'source', 'term']);

        $return = $this->pipe('selectAction', $data, $required);
        if ($return) {
            return $return;
        }

        $where = [];
        foreach (DataBaseWhere::applyOperation($data['fieldfilter'] ?? '') as $field => $operation) {
            $where[] = new DataBaseWhere($field, $data['term'], '=', $operation);
        }

        $results = [];
        foreach ($this->codeModel->all($data['source'], $data['fieldcode'], $data['fieldtitle'], !$required, $where) as $value) {
            // no usar fixHtml() aquí porque compromete la seguridad
            $results[] = ['key' => $value->code, 'value' => $value->description];
        }
        return $results;
    }
}
