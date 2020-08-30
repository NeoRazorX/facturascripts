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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\ExportManager;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Response;

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
     * @var BaseView[]|ListView[]
     */
    public $views = [];

    /**
     * Inserts the views or tabs to display.
     */
    abstract protected function createViews();

    /**
     * Loads the data to display.
     *
     * @param string   $viewName
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
     * @param array  $btnArray
     */
    public function addButton($viewName, $btnArray)
    {
        $rowType = isset($btnArray['row']) ? 'footer' : 'actions';
        $row = $this->views[$viewName]->getRow($rowType);
        if ($row) {
            $row->addButton($btnArray);
        }
    }

    /**
     *
     * @param string            $viewName
     * @param BaseView|ListView $view
     */
    public function addCustomView($viewName, $view)
    {
        if ($viewName !== $view->getViewName()) {
            $this->toolBox()->log()->error('$viewName must be equals to $view->name');
            return;
        }

        $view->loadPageOptions($this->user);
        $this->views[$viewName] = $view;
        if (empty($this->active)) {
            $this->active = $viewName;
        }
    }

    /**
     *
     * @return BaseView|ListView
     */
    public function getCurrentView()
    {
        return $this->views[$this->current];
    }

    /**
     * Returns the name assigned to the main view
     *
     * @return string
     */
    public function getMainViewName()
    {
        foreach (\array_keys($this->views) as $key) {
            return $key;
        }

        return '';
    }

    /**
     * Returns the configuration value for the indicated view.
     *
     * @param string $viewName
     * @param string $property
     *
     * @return mixed
     */
    public function getSettings($viewName, $property)
    {
        return isset($this->views[$viewName]->settings[$property]) ? $this->views[$viewName]->settings[$property] : null;
    }

    /**
     * Return the value for a field in the model of the view.
     *
     * @param string $viewName
     * @param string $fieldName
     *
     * @return mixed
     */
    public function getViewModelValue($viewName, $fieldName)
    {
        $model = $this->views[$viewName]->model;
        return isset($model->{$fieldName}) ? $model->{$fieldName} : null;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Create the views to display
        $this->createViews();
        $this->pipe('createViews');
    }

    /**
     *
     * @param string $viewName
     */
    public function setCurrentView($viewName)
    {
        $this->current = $viewName;
    }

    /**
     * Set value for setting of a view
     *
     * @param string $viewName
     * @param string $property
     * @param mixed  $value
     */
    public function setSettings($viewName, $property, $value)
    {
        $this->views[$viewName]->settings[$property] = $value;
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
        $utils = $this->toolBox()->utils();
        foreach ($this->codeModel->search($data['source'], $data['fieldcode'], $data['fieldtitle'], $data['term'], $where) as $value) {
            $results[] = ['key' => $utils->fixHtml($value->code), 'value' => $utils->fixHtml($value->description)];
        }

        if (empty($results) && '0' == $data['strict']) {
            $results[] = ['key' => $data['term'], 'value' => $data['term']];
        } elseif (empty($results)) {
            $results[] = ['key' => null, 'value' => $this->toolBox()->i18n()->trans('no-data')];
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
        if (false === $this->permissions->allowDelete || false === $this->views[$this->active]->settings['btnDelete']) {
            $this->toolBox()->i18nLog()->warning('not-allowed-delete');
            return false;
        }

        $model = $this->views[$this->active]->model;
        $codes = $this->request->request->get('code', '');

        if (empty($codes)) {
            // no selected item
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return false;
        } elseif (\is_array($codes)) {
            $this->dataBase->beginTransaction();

            // deleting multiples rows
            $numDeletes = 0;
            foreach ($codes as $cod) {
                if ($model->loadFromCode($cod) && $model->delete()) {
                    ++$numDeletes;
                    continue;
                }

                /// error?
                $this->dataBase->rollback();
                break;
            }

            $model->clear();
            $this->dataBase->commit();
            if ($numDeletes > 0) {
                $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
                return true;
            }
        } elseif ($model->loadFromCode($codes) && $model->delete()) {
            // deleting a single row
            $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
            $model->clear();
            return true;
        }

        $this->toolBox()->i18nLog()->warning('record-deleted-error');
        $model->clear();
        return false;
    }

    protected function exportAction()
    {
        $this->setTemplate(false);
        $this->exportManager->newDoc($this->request->get('option', ''), $this->title);
        foreach ($this->views as $selectedView) {
            if (false === $selectedView->settings['active']) {
                continue;
            }

            if (false === $selectedView->export($this->exportManager)) {
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
                $result[] = ['key' => $this->toolBox()->i18n()->trans($value['title']), 'value' => $value['value']];
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
    protected function requestGet($keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->request->get($key);
        }
        return $result;
    }
}
