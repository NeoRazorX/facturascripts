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

/**
 * Controller to manage the data editing
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditController extends Base\Controller
{

    /**
     * Export data object
     *
     * @var Base\ExportManager
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
        $this->exportManager = new Base\ExportManager();
    }

    /**
     * Runs the controller's private logic
     *
     * @param mixed $response
     * @param mixed $user
     */
    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        // Create the view to display
        $viewName = $this->getClassName();
        $title = $this->getPageData()['title'];
        $this->view = new EditView($title, $this->getmodelName(), $viewName, $user->nick);

        // Get any operations that have to be performed
        $action = $this->request->get('action', '');

        // Run operations on the data before reading it
        $this->execPreviousAction($action);

        // Load the model data
        $value = $this->request->get('code');
        $this->view->loadData($value);

        // General operations with the loaded data
        $this->execAfterAction($action);
    }

    /**
     * Run the actions that alter data before reading it
     *
     * @param string $action
     */
    private function execPreviousAction($action)
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
    private function execAfterAction($action)
    {
        switch ($action) {
            case 'insert':
                $this->insertAction();
                break;

            case 'export':
                $this->setTemplate(false);
                $document = $this->view->export($this->exportManager, $this->response, $this->request->get('option'));
                $this->response->setContent($document);
                break;
        }
    }

    /**
     * Returns a field value for the loaded data model
     *
     * @param mixed $model
     * @param string $field
     *
     * @return mixed
     */
    public function getFieldValue($model, $field)
    {
        return $model->{$field};
    }

    /**
     * Run the data edits
     *
     * @return boolean
     */
    protected function editAction()
    {
        if ($this->view->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));
            return true;
        }
        return false;
    }

    /**
     * Prepare the insertion of a new row
     */
    protected function insertAction()
    {
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
     * Pointer to the data model
     *
     * @return mixed
     */
    public function getModel()
    {
        return $this->view->getModel();
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
