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
 * Controller to manage the data editing
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
abstract class EditController extends Base\Controller
{
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
     * View displayed by the controller
     *
     * @var EditView
     */
    public $view;

    /**
     * Initializes all the objects and properties
     *
     * @param Base\Cache $cache
     * @param Base\Translator $i18n
     * @param Base\MiniLog $miniLog
     * @param string $className
     */
    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);

        $this->setTemplate('Master/EditController');
        $this->codeModel = new CodeModel();
        $this->exportManager = new ExportManager();
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

        // Create the view to display
        $this->view = $this->createView();

        // Get any operations that have to be performed
        $action = $this->request->get('action', '');

        // Run operations on the data before reading it
        $this->execPreviousAction($action);

        // Load the model data
        $value = $this->request->get('code');
        $this->loadData($value);

        // General operations with the loaded data
        $this->execAfterAction($action);
    }

    /**
     * Create the view to display
     *
     * @return ExtendedController\EditView
     */
    protected function createView()
    {
        return new EditView(
            $this->getPageData()['title'], $this->getModelClassName(), $this->getClassName(), $this->user->nick);
    }

    /**
     * Load data of view from code
     *
     * @param string|array $code
     */
    protected function loadData($code)
    {
        $this->view->loadData($code);
    }

    /**
     * Run the actions that alter data before reading it
     *
     * @param string $action
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'save':
                $data = $this->request->request->all();
                $this->view->loadFromData($data);
                $this->editAction();
                break;
        }
    }

    /**
     * Run the controller actions
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'autocomplete':
                $this->autocompleteAction();
                break;

            case 'export':
                $this->setTemplate(false);
                $this->exportManager->newDoc($this->response, $this->request->get('option'));
                $this->view->export($this->exportManager);
                $this->exportManager->show($this->response);
                break;
            
            case 'insert':
                $this->insertAction();
                break;
        }
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
    
    private function autocompleteAction()
    {
        $this->setTemplate(false);
        $source = $this->request->get('source');
        $field = $this->request->get('field');
        $title = $this->request->get('title');
        $term = $this->request->get('term');
        
        $results = [];
        foreach($this->codeModel->search($source, $field, $title, $term) as $value) {
            $results[] = ['key' => $value->code, 'value' => $value->description];
        }
        $this->response->setContent(json_encode($results));
    }

    /**
     * Run the data edits
     *
     * @return bool
     */
    protected function editAction()
    {
        if (!$this->permissions->allowUpdate) {
            $this->miniLog->alert($this->i18n->trans('not-allowed-modify'));
            return false;
        }

        if ($this->view->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            return true;
        }

        return false;
    }

    protected function insertAction()
    {
        $this->view->setNewCode();
    }

    /**
     * Returns the text for the data main panel header
     *
     * @return string
     */
    public function getPanelHeader()
    {
        return $this->i18n->trans('general-data');
    }

    /**
     * Returns the text for the data main panel footer
     *
     * @return string
     */
    public function getPanelFooter()
    {
        return !empty($this->view->getPanelFooter()) ? $this->i18n->trans($this->view->getPanelFooter()) : '';
    }

    /**
     * Returns the class name of the model to use in the editView.
     */
    abstract public function getModelClassName();

    /**
     * Pointer to the data model
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->view->getModel();
    }

    /**
     * Descriptive identifier for humans of the main data editing record
     *
     * @return string
     */
    public function getPrimaryDescription()
    {
        $model = $this->view->getModel();
        return $model->primaryDescription();
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
        return $this->view->getURL($type);
    }
}
