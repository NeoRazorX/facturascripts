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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\PageOption;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Edit option for any page.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Artex Trading sa             <jcuello@artextrading.com>
 * @author Fco. Antonio Moreno Pérez    <famphuelva@gmail.com>
 */
class EditPageOption extends Controller
{

    /**
     * Contains the url to go back.
     *
     * @var string
     */
    public $backPage;

    /**
     *
     * @var array
     */
    public $columns = [];

    /**
     *
     * @var array
     */
    public $modals = [];

    /**
     * Details of the view configuration
     *
     * @var PageOption
     */
    public $model;

    /**
     *
     * @var array
     */
    public $rows = [];

    /**
     * Selected user, for which the controller columns are created or modified
     *
     * @var string
     */
    public $selectedUser;

    /**
     * Selected view, for which columns are created or modified
     *
     * @var string
     */
    public $selectedViewName;

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['showonmenu'] = false;
        $data['title'] = 'options';
        $data['icon'] = 'fas fa-wrench';
        return $data;
    }

    /**
     * Get the list of users, excluding the user admin
     *
     * @return array
     */
    public function getUserList()
    {
        $result = [];
        $users = CodeModel::all(User::tableName(), 'nick', 'nick', false);
        foreach ($users as $codeModel) {
            $result[$codeModel->code] = $codeModel->description;
        }

        return $result;
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
        $this->model = new PageOption();
        $this->loadSelectedViewName();
        $this->backPage = $this->request->get('url') ?: $this->selectedViewName;
        $this->selectedUser = $this->user->admin ? $this->request->get('nick', $this->user->nick) : $this->user->nick;
        $this->loadPageOptions();

        $action = $this->request->get('action', '');
        switch ($action) {
            case 'delete':
                $this->deleteAction();
                break;

            case 'save':
                $this->saveAction();
                break;
        }
    }

    /**
     * Delete configuration for view
     */
    protected function deleteAction()
    {
        if (false === $this->permissions->allowDelete) {
            $this->toolBox()->i18nLog()->warning('not-allowed-delete');
            return;
        }

        if ($this->model->delete()) {
            $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
            $this->loadPageOptions();
            return;
        }

        $this->toolBox()->i18nLog()->warning('default-not-deletable');
    }

    /**
     *
     */
    protected function loadPageOptions()
    {
        $order = ['nick' => 'ASC'];
        $where = [
            new DataBaseWhere('name', $this->selectedViewName),
            new DataBaseWhere('nick', $this->selectedUser),
            new DataBaseWhere('nick', null, 'IS', 'OR')
        ];
        if (false === $this->model->loadFromCode('', $where, $order)) {
            VisualItemLoadEngine::installXML($this->selectedViewName, $this->model);
        }

        VisualItemLoadEngine::loadArray($this->columns, $this->modals, $this->rows, $this->model);
    }

    protected function loadSelectedViewName()
    {
        $code = $this->request->get('code', '');
        if (false === \strpos($code, '-')) {
            $this->selectedViewName = $code;
            return;
        }

        $parts = \explode('-', $code);
        $this->selectedViewName = empty($parts) ? $code : $parts[0];
    }

    /**
     * Save new configuration for view
     */
    protected function saveAction()
    {
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-modify');
            return;
        }

        foreach ($this->model->columns as $key1 => $group) {
            if ($group['tag'] === 'column') {
                $name = $group['name'];
                $this->setColumnOption($this->model->columns[$key1], $name, 'title', false, false);
                $this->setColumnOption($this->model->columns[$key1], $name, 'display', false, false);
                $this->setColumnOption($this->model->columns[$key1], $name, 'level', false, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'readonly', true, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'decimal', true, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'numcolumns', false, true);
                $this->setColumnOption($this->model->columns[$key1], $name, 'order', false, true);
                continue;
            }

            foreach ($group['children'] as $key2 => $col) {
                $name = $col['name'];
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'title', false, false);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'display', false, false);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'level', false, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'readonly', true, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'decimal', true, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'numcolumns', false, true);
                $this->setColumnOption($this->model->columns[$key1]['children'][$key2], $name, 'order', false, true);
            }
        }

        if ($this->model->save()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            $this->loadPageOptions();
            return;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
    }

    /**
     * 
     * @param array  $column
     * @param string $name
     * @param string $key
     * @param bool   $isWidget
     * @param bool   $allowEmpty
     */
    private function setColumnOption(&$column, string $name, string $key, bool $isWidget, bool $allowEmpty)
    {
        $newValue = $this->request->request->get($name . '-' . $key);
        if ($isWidget) {
            if (!empty($newValue) || $allowEmpty) {
                $column['children'][0][$key] = $newValue;
            }
            return;
        }

        if (!empty($newValue) || $allowEmpty) {
            $column[$key] = $newValue;
        }
    }
}
