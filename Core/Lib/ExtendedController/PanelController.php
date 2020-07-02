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

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Dinamic\Model\User;
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
    public $hasData = false;

    /**
     * Tabs position in page: left, bottom.
     *
     * @var string
     */
    public $tabsPosition;

    /**
     * Starts all the objects and properties.
     *
     * @param string $className
     * @param string $uri
     */
    public function __construct(string $className, string $uri = '')
    {
        parent::__construct($className, $uri);
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
     * Runs the controller's private logic.
     *
     * @param Response              $response
     * @param User                  $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        /// Get any operations that have to be performed
        $action = $this->request->request->get('action', $this->request->query->get('action', ''));

        /// Runs operations before reading data
        if ($this->execPreviousAction($action) === false || $this->pipe('execPreviousAction', $action) === false) {
            return;
        }

        /// Load the data for each view
        $mainViewName = $this->getMainViewName();
        foreach ($this->views as $viewName => $view) {
            /// disable views if main view has no data
            if ($viewName != $mainViewName && false === $this->hasData) {
                $this->setSettings($viewName, 'active', false);
            }

            if (false === $view->settings['active']) {
                /// exclude inactive views
                continue;
            } elseif ($this->active == $viewName) {
                $view->processFormData($this->request, 'load');
            } else {
                $view->processFormData($this->request, 'preload');
            }

            $this->loadData($viewName, $view);
            $this->pipe('loadData', $viewName, $view);

            if ($viewName === $mainViewName && $view->model->exists()) {
                $this->hasData = true;
            }
        }

        /// General operations with the loaded data
        $this->execAfterAction($action);
        $this->pipe('execAfterAction', $action);
    }

    /**
     * Sets the tabs position, by default is setted to 'left', also supported 'bottom' and 'top'.
     *
     * @param string $position
     */
    public function setTabsPosition($position)
    {
        $this->tabsPosition = $position;
        switch ($this->tabsPosition) {
            case 'bottom':
                $this->setTemplate('Master/PanelControllerBottom');
                break;

            case 'top':
                $this->setTemplate('Master/PanelControllerTop');
                break;

            default:
                $this->tabsPosition = 'left';
                $this->setTemplate('Master/PanelController');
        }

        foreach (\array_keys($this->views) as $viewName) {
            $this->views[$viewName]->settings['card'] = $this->tabsPosition !== 'top';
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
        $view->settings['card'] = $this->tabsPosition !== 'top';
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
        $view->settings['card'] = $this->tabsPosition !== 'top';
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
    protected function addListView($viewName, $modelName, $viewTitle, $viewIcon = 'fas fa-list')
    {
        $view = new ListView($viewName, $viewTitle, self::MODEL_NAMESPACE . $modelName, $viewIcon);
        $view->settings['card'] = $this->tabsPosition !== 'top';
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
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return false;
        }

        // duplicated request?
        if ($this->multiRequestProtection->tokenExist($this->request->request->get('multireqtoken', ''))) {
            $this->toolBox()->i18nLog()->warning('duplicated-request');
            return false;
        }

        // loads model data
        $code = $this->request->request->get('code', '');
        if (!$this->views[$this->active]->model->loadFromCode($code)) {
            $this->toolBox()->i18nLog()->error('record-not-found');
            return false;
        }

        // loads form data
        $this->views[$this->active]->processFormData($this->request, 'edit');

        // has PK value been changed?
        $this->views[$this->active]->newCode = $this->views[$this->active]->model->primaryColumnValue();
        if ($code != $this->views[$this->active]->newCode && $this->views[$this->active]->model->test()) {
            $pkColumn = $this->views[$this->active]->model->primaryColumn();
            $this->views[$this->active]->model->{$pkColumn} = $code;
            // change in database
            if (!$this->views[$this->active]->model->changePrimaryColumnValue($this->views[$this->active]->newCode)) {
                $this->toolBox()->i18nLog()->error('record-save-error');
                return false;
            }
        }

        // save in database
        if ($this->views[$this->active]->model->save()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
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
                $this->exportAction();
                break;

            case 'save-ok':
                $this->toolBox()->i18nLog()->notice('record-updated-correctly');
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

            case 'delete':
            case 'delete-document':
                $this->deleteAction();
                break;

            case 'edit':
                if ($this->editAction()) {
                    $this->views[$this->active]->model->clear();
                }
                break;

            case 'insert':
                if ($this->insertAction() || !empty($this->views[$this->active]->model->primaryColumnValue())) {
                    /// wee need to clear model in these scenarios
                    $this->views[$this->active]->model->clear();
                }
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
     * 
     * @return bool
     */
    protected function insertAction()
    {
        if (!$this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return false;
        }

        // duplicated request?
        if ($this->multiRequestProtection->tokenExist($this->request->request->get('multireqtoken', ''))) {
            $this->toolBox()->i18nLog()->warning('duplicated-request');
            return false;
        }

        // loads form data
        $this->views[$this->active]->processFormData($this->request, 'edit');
        if ($this->views[$this->active]->model->exists()) {
            $this->toolBox()->i18nLog()->error('duplicate-record');
            return false;
        }

        // save in database
        if ($this->views[$this->active]->model->save()) {
            /// redir to new model url only if this is the first view
            if ($this->active === $this->getMainViewName()) {
                $this->redirect($this->views[$this->active]->model->url() . '&action=save-ok');
            }

            $this->views[$this->active]->newCode = $this->views[$this->active]->model->primaryColumnValue();
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
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
