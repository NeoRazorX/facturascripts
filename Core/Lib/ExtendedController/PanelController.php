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
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to edit data through the vertical panel
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
abstract class PanelController extends BaseController
{

    /**
     * Indicates if the main view has data or is empty.
     *
     * @var bool
     */
    public $hasData;

    /**
     * Tabs position in page: left, bottom.
     *
     * @var string
     */
    public $tabsPosition;

    /**
     * Starts all the objects and properties.
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
        $this->hasData = false;
        $this->setTabsPosition('left');
    }

    /**
     *
     * @return string
     */
    public function getImageUrl()
    {
        return '';
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
     * @param Response                   $response
     * @param User                       $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Create the views to display
        $this->createViews();

        // Get any operations that have to be performed
        $action =  $this->request->request->get('action', $this->request->query->get('action'));

        // Run operations on the data before reading it
        if (!$this->execPreviousAction($action)) {
            return;
        }

        // Load the model data for each view
        $mainViewName = array_keys($this->views)[0];
        foreach ($this->views as $viewName => $view) {
            if ($this->active == $viewName) {
                $view->processFormData($this->request, 'load');
            } else {
                $view->processFormData($this->request, 'preload');
            }

            $this->loadData($viewName, $view);

            // check if we are processing the main view
            if ($viewName == $mainViewName) {
                $this->hasData = $view->count > 0;
                continue;
            }

            // check if the view should be active
            if ($view->settings['active']) {
                $this->setSettings($viewName, 'active', $this->hasData);
            }
        }

        // General operations with the loaded data
        $this->execAfterAction($action);

        // final operations, like assets merge
        $this->finalStep();
    }

    /**
     * Sets the tabs position, by default is setted to 'left', also supported 'bottom' and 'top'.
     *
     * @param string $position
     */
    public function setTabsPosition($position)
    {
        $this->tabsPosition = $position;

        switch ($position) {
            case 'bottom':
                $this->setTemplate('Master/PanelControllerBottom');
                break;

            case 'top':
                $this->setTemplate('Master/PanelControllerTop');
                break;

            default:
                $this->tabsPosition = 'left';
                $this->setTemplate('Master/PanelController');
                break;
        }
    }

    /**
     * Adds a EditList type view to the controller.
     *
     * @param string $viewName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addEditListView($viewName, $modelName, $viewTitle, $viewIcon = 'fas fa-bars')
    {
        $view = new EditListView($viewName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $viewIcon);
        $this->addCustomView($viewName, $view);
    }

    /**
     * Adds a Edit type view to the controller.
     *
     * @param string $viewName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addEditView($viewName, $modelName, $viewTitle, $viewIcon = 'fas fa-edit')
    {
        $view = new EditView($viewName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $viewIcon);
        $this->addCustomView($viewName, $view);
    }

    /**
     * Adds a Grid type view to the controller.
     * Master/Detail params:
     *   ['name' = 'viewName', 'model' => 'modelName']
     *
     * @param array  $master
     * @param array  $detail
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addGridView($master, $detail, $viewTitle, $viewIcon = 'fas fa-list-alt')
    {
        // Create master and detail views
        $master['model'] = self::MODEL_NAMESPACE . $master['model'];
        $detail['model'] = self::MODEL_NAMESPACE . $detail['model'];
        $view = new GridView($master, $detail, $viewTitle, $viewIcon);

        // load columns definition for detail view
        $view->detailView->loadPageOptions($this->user);

        // Add view to views container
        $this->addCustomView($master['name'], $view);
    }

    /**
     * Adds a HTML type view to the controller.
     *
     * @param string $viewName
     * @param string $fileName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addHtmlView($viewName, $fileName, $modelName, $viewTitle, $viewIcon = 'fab fa-html5')
    {
        $view = new HtmlView($viewName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $fileName, $viewIcon);
        $this->addCustomView($viewName, $view);
    }

    /**
     * Adds a List type view to the controller.
     *
     * @param string $viewName
     * @param string $modelName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addListView($viewName, $modelName, $viewTitle, $viewIcon = 'fas fa-bars')
    {
        $view = new ListView($viewName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $viewIcon);
        $this->addCustomView($viewName, $view);
    }

    /**
     * Runs the data edit action.
     *
     * @return bool
     */
    protected function editAction()
    {
        if (!$this->permissions->allowUpdate) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            return false;
        }

        // loads model data
        $code = $this->request->request->get('code', '');
        if (!$this->views[$this->active]->model->loadFromCode($code)) {
            $this->miniLog->error($this->i18n->trans('record-not-found'));
            return false;
        }

        // loads form data
        $this->views[$this->active]->processFormData($this->request, 'edit');

        // has PK value been changed?
        $this->views[$this->active]->newCode = $this->views[$this->active]->model->primaryColumnValue();
        if ($code != $this->views[$this->active]->newCode) {
            $pkColumn = $this->views[$this->active]->model->primaryColumn();
            $this->views[$this->active]->model->{$pkColumn} = $code;
            // change in database
            if (!$this->views[$this->active]->model->changePrimaryColumnValue($this->views[$this->active]->newCode)) {
                $this->miniLog->error($this->i18n->trans('record-save-error'));
                return false;
            }
        }

        // save in database
        if ($this->views[$this->active]->model->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            $this->views[$this->active]->model->clear();
            return true;
        }

        $this->miniLog->error($this->i18n->trans('record-save-error'));
        return false;
    }

    /**
     * Run the controller after actions.
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'export':
                $this->setTemplate(false);
                $this->exportManager->newDoc($this->request->get('option', ''));
                foreach ($this->views as $selectedView) {
                    $selectedView->export($this->exportManager);
                }
                $this->exportManager->show($this->response);
                break;
        }
    }

    /**
     * Run the actions that alter data before reading it.
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

            case 'edit':
                $this->editAction();
                break;

            case 'delete':
            case 'delete-document':
                $this->deleteAction();
                break;

            case 'insert':
                $this->insertAction();
                break;

            case 'save-document':
                $viewName = $this->searchGridView();
                if (!empty($viewName)) {
                    $this->setTemplate(false);
                    $data = $this->request->request->all();
                    $result = $this->views[$viewName]->saveData($data);
                    $this->response->setContent(json_encode($result, JSON_FORCE_OBJECT));
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * Runs data insert action.
     */
    protected function insertAction()
    {
        if (!$this->permissions->allowUpdate) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            return false;
        }

        // loads form data
        $this->views[$this->active]->processFormData($this->request, 'edit');
        if ($this->views[$this->active]->model->exists()) {
            $this->miniLog->error($this->i18n->trans('duplicate-record'));
            return false;
        }

        // empty primary key?
        if (empty($this->views[$this->active]->model->primaryColumnValue())) {
            $model = $this->views[$this->active]->model;
            // assign a new value
            $this->views[$this->active]->model->{$model->primaryColumn()} = $model->newCode();
        }

        // save in database
        if ($this->views[$this->active]->model->save()) {
            $this->views[$this->active]->newCode = $this->views[$this->active]->model->primaryColumnValue();
            $this->views[$this->active]->model->clear();
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            return true;
        }

        $this->miniLog->error($this->i18n->trans('record-save-error'));
        return false;
    }

    /**
     * Returns the key of the first GridView.
     *
     * @return string
     */
    private function searchGridView(): string
    {
        foreach ($this->views as $viewName => $view) {
            if ($view instanceof GridView) {
                return $viewName;
            }
        }

        return '';
    }
}
