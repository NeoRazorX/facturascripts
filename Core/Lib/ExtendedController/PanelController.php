<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Lib\ExtendedController;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Lib\ExportManager;
use FacturaScripts\Core\Model\CodeModel;
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
     * Model to use with select and autocomplete filters.
     *
     * @var CodeModel
     */
    private $codeModel;

    /**
     * Export data object
     *
     * @var ExportManager
     */
    public $exportManager;

    /**
     * List of configuration options for each of the views
     * [
     *   'keyView1' => ['icon' => 'fa-icon1', 'active' => TRUE],
     *   'keyView2' => ['icon' => 'fa-icon2', 'active' => TRUE]
     * ]
     *
     * @var array
     */
    public $settings;

    /**
     * Tabs position in page: left, bottom.
     *
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
     * @param string   $keyView
     * @param BaseView $view
     */
    abstract protected function loadData($keyView, $view);

    /**
     * Starts all the objects and properties
     *
     * @param Base\Cache      $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog    $miniLog
     * @param string          $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->active = $this->request->get('active', '');
        $this->codeModel = new CodeModel();
        $this->exportManager = new ExportManager();
        $this->settings = [];
        $this->views = [];

        $this->setTabsPosition('left');
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
        $view = empty($this->active) ? null : $this->views[$this->active];
        $action = empty($view) ? '' : $this->request->get('action', '');

        // Run operations on the data before reading it
        $this->execPreviousAction($view, $action);

        // Load the model data for each view
        $mainView = array_keys($this->views)[0];
        $hasData = false;
        foreach ($this->views as $keyView => $dataView) {
            $this->loadData($keyView, $dataView);

            // check if we are processing the main view
            if ($keyView == $mainView) {
                $hasData = $dataView->count > 0;
                continue;
            }
            // check if the view should be active
            $this->settings[$keyView]['active'] = $this->checkActiveView($dataView, $hasData);
        }

        // General operations with the loaded data
        $this->execAfterAction($view, $action);
    }

    /**
     * Returns the configuration value for the indicated view
     *
     * @param string $keyView
     * @param string $property
     *
     * @return mixed
     */
    public function getSettings($keyView, $property)
    {
        return $this->settings[$keyView][$property];
    }

    /**
     * Returns a field value for the loaded data model
     *
     * @param mixed  $model
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
     * Descriptive identifier for humans of the main data editing record
     *
     * @return string
     */
    public function getPrimaryDescription()
    {
        $viewName = array_keys($this->views)[0];
        $model = $this->views[$viewName]->getModel();

        return $model->primaryDescription();
    }

    /**
     * Run the actions that alter data before reading it
     *
     * @param BaseView $view
     * @param string   $action
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
     * @param string   $action
     */
    protected function execAfterAction($view, $action)
    {
        switch ($action) {
            case 'autocomplete':
                $this->autocompleteAction();
                break;

            case 'export':
                $this->setTemplate(false);
                $this->exportManager->newDoc($this->request->get('option'));
                foreach ($this->views as $selectedView) {
                    $selectedView->export($this->exportManager);
                }
                $this->exportManager->show($this->response);
                break;

            case 'insert':
                $this->insertAction($view);
                break;
        }
    }

    /**
     * Run the autocomplete action.
     * Returns a JSON string for the searched values.
     */
    private function autocompleteAction()
    {
        $this->setTemplate(false);
        $source = $this->request->get('source');
        $field = $this->request->get('field');
        $title = $this->request->get('title');
        $term = $this->request->get('term');

        $results = [];
        foreach ($this->codeModel->search($source, $field, $title, $term) as $value) {
            $results[] = ['key' => $value->code, 'value' => $value->description];
        }
        $this->response->setContent(json_encode($results));
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
        if (!$this->permissions->allowDelete) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-delete'));

            return false;
        }

        $fieldKey = $view->getModel()->primaryColumn();
        if ($view->delete($this->request->get($fieldKey))) {
            $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));

            return true;
        }

        return false;
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
        if (!$this->permissions->allowUpdate) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));

            return false;
        }

        if ($view->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));

            return true;
        }

        $this->miniLog->error($this->i18n->trans('record-save-error'));

        return false;
    }

    /**
     * Run the data insert action.
     *
     * @param EditView $view
     */
    protected function insertAction($view)
    {
        $view->setNewCode();
    }

    /**
     * Check if the view should be active
     *
     * @param BaseView $view
     * @param bool     $mainViewHasData
     *
     * @return bool
     */
    protected function checkActiveView(&$view, $mainViewHasData)
    {
        return $mainViewHasData;
    }

    /**
     * Adds a view to the controller and loads its data
     *
     * @param string   $keyView
     * @param BaseView $view
     * @param string   $icon
     */
    protected function addView($keyView, $view, $icon)
    {
        $this->views[$keyView] = $view;
        $this->settings[$keyView] = ['active' => true, 'icon' => $icon];

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
