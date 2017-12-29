<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Base\ExtendedController;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Lib\ExportManager;
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller to edit data through the vertical panel
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class PanelController extends Base\Controller
{

    /**
     * Indicates the active view
     *
     * @var string
     */
    public $active;

    /**
     * Export data object
     *
     * @var ExportManager
     */
    public $exportManager;

    /**
     * List of icons for each of the views
     *
     * @var array
     */
    public $icons;

    /**
     * Tabs position in page: left, bottom.
     * @var string
     */
    public $tabsPosition;

    /**
     * List of views displayed by the controller
     *
     * @var BaseView[]
     */
    public $views;

    /**
     * Inserts the views to display
     */
    abstract protected function createViews();

    /**
     * Loads the data to display
     *
     * @param string $keyView
     * @param BaseView $view
     */
    abstract protected function loadData($keyView, $view);

    /**
     * Starts all the objects and properties
     *
     * @param Base\Cache $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog $miniLog
     * @param string $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->exportManager = new ExportManager();
        $this->setTemplate('Master/PanelController');
        $this->active = $this->request->get('active', '');
        $this->tabsPosition = 'left';
        $this->icons = [];
        $this->views = [];
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
     * Runs the controller's private logic.
     *
     * @param Response $response
     * @param User $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        // Create the views to display
        $this->createViews();

        // Get any operations that have to be performed
        $view = empty($this->active) ? null : $this->views[$this->active];
        $action = empty($view) ? '' : $this->request->get('action', '');

        // Run operations on the data before reading it
        $this->execPreviousAction($view, $action);

        // Load the model data for each view
        foreach ($this->views as $keyView => $dataView) {
            $this->loadData($keyView, $dataView);
        }

        // General operations with the loaded data
        $this->execAfterAction($view, $action);
    }

    /**
     * Returns a field value for the loaded data model
     *
     * @param mixed $model
     * @param string $fieldName
     *
     * @return mixed
     */
    public function getFieldValue($model, $fieldName)
    {
        if (isset($model->{$fieldName})) {
            return $model->{$fieldName};
        }

        return null;
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
        $model = $this->views[$viewName]->getModel();
        return $this->getFieldValue($model, $fieldName);
    }

    /**
     * Returns the url for a specified type
     *
     * @param string $type
     *
     * @return string
     */
    public function getURL($type)
    {
        $view = array_values($this->views)[0];
        return $view->getURL($type);
    }

    /**
     * Run the actions that alter data before reading it
     *
     * @param BaseView $view
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($view, $action)
    {
        $status = true;
        switch ($action) {
            case 'save':
                $data = $this->request->request->all();
                $view->loadFromData($data);
                $status = $this->editAction($view);
                break;

            case 'delete':
                $status = $this->deleteAction($view);
                break;
        }

        return $status;
    }

    /**
     * Run the controller after actions
     *
     * @param EditView $view
     * @param string $action
     */
    protected function execAfterAction($view, $action)
    {
        switch ($action) {
            case 'export':
                $this->setTemplate(false);
                $this->exportManager->newDoc($this->response, $this->request->get('option'));
                foreach ($this->views as $selectedView) {
                    $selectedView->export($this->exportManager);
                }
                $this->exportManager->show($this->response);
                break;
        }
    }

    /**
     * Run the data edits
     *
     * @param BaseView $view
     *
     * @return bool
     */
    protected function editAction($view)
    {
        if ($view->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            return true;
        }
        return false;
    }

    /**
     * Action to delete data
     *
     * @param BaseView $view
     *
     * @return bool
     */
    protected function deleteAction($view)
    {
        $fieldKey = $view->getModel()->primaryColumn();
        if ($view->delete($this->request->get($fieldKey))) {
            $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
            return true;
        }
        return false;
    }

    /**
     * Adds a view to the controller and loads its data
     *
     * @param string $keyView
     * @param BaseView $view
     * @param string $icon
     */
    private function addView($keyView, $view, $icon)
    {
        $this->views[$keyView] = $view;
        $this->icons[$keyView] = $icon;

        if (empty($this->active)) {
            $this->active = $keyView;
        }
    }

    /**
     * Adds a EditList type view to the controller
     *
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addEditListView($modelName, $viewName, $viewTitle, $viewIcon = 'fa-bars')
    {
        $view = new EditListView($viewTitle, $modelName, $viewName, $this->user->nick);
        $this->addView($viewName, $view, $viewIcon);
    }

    /**
     * Adds a List type view to the controller
     *
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addListView($modelName, $viewName, $viewTitle, $viewIcon = 'fa-bars')
    {
        $view = new ListView($viewTitle, $modelName, $viewName, $this->user->nick);
        $this->addView($viewName, $view, $viewIcon);
    }

    /**
     * Adds a Edit type view to the controller
     *
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addEditView($modelName, $viewName, $viewTitle, $viewIcon = 'fa-list-alt')
    {
        $view = new EditView($viewTitle, $modelName, $viewName, $this->user->nick);
        $this->addView($viewName, $view, $viewIcon);
    }

    /**
     * Adds a HTML type view to the controller
     *
     * @param string $fileName
     * @param string $modelName
     * @param string $viewName
     * @param string $viewTitle
     * @param string $viewIcon
     */
    protected function addHtmlView($fileName, $modelName, $viewName, $viewTitle, $viewIcon = 'fa-html5')
    {
        $view = new HtmlView($viewTitle, $modelName, $fileName);
        $this->addView($viewName, $view, $viewIcon);
    }

    /**
     * Returns the view class
     *
     * @param string $view
     *
     * @return string
     */
    public function viewClass($view)
    {
        $result = explode('\\', get_class($view));
        return end($result);
    }
}
