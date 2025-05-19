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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CodeModel;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\PageOption;
use FacturaScripts\Dinamic\Model\User;

/**
 * Edit option for any page.
 *
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 * @author Jose Antonio Cuello          <yopli2000@gmail.com>
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
     * @var array
     */
    public $columns = [];

    /**
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

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'options';
        $data['icon'] = 'fa-solid fa-wrench';
        $data['showonmenu'] = false;
        return $data;
    }

    /**
     * Get the list of users, excluding the user admin
     *
     * @return array
     */
    public function getUserList(): array
    {
        $result = [];
        $users = CodeModel::all(User::tableName(), 'nick', 'nick', false);
        foreach ($users as $codeModel) {
            if ($codeModel->code != 'admin') {
                $result[$codeModel->code] = $codeModel->description;
            }
        }

        return $result;
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
        $this->model = new PageOption();
        $this->loadSelectedViewName();
        $this->setBackPage();
        $this->selectedUser = $this->user->admin ? $this->request->get('nick') : $this->user->nick;
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
            Tools::log()->warning('not-allowed-delete');
            return;
        } elseif (false === $this->validateFormToken()) {
            return;
        }

        if ($this->model->delete()) {
            Tools::log()->notice('record-deleted-correctly');
            $this->loadPageOptions();
            return;
        }

        Tools::log()->warning('default-not-deletable');
    }

    /**
     * Load the display options to edit.
     * If it does not find them in the database,
     * it loads the default options of the xml view.
     */
    protected function loadPageOptions()
    {
        if ($this->selectedUser && false === $this->loadPageOptionsForUser()) {
            VisualItemLoadEngine::installXML($this->selectedViewName, $this->model);
        }

        if (empty($this->selectedUser) && false === $this->loadPageOptionsForAll()) {
            VisualItemLoadEngine::installXML($this->selectedViewName, $this->model);
        }

        VisualItemLoadEngine::loadArray($this->columns, $this->modals, $this->rows, $this->model);
    }

    protected function loadSelectedViewName()
    {
        $code = $this->request->get('code', '');
        if (false === strpos($code, '-')) {
            $this->selectedViewName = $code;
            return;
        }

        $parts = explode('-', $code);
        $this->selectedViewName = empty($parts) ? $code : $parts[0];
    }

    /**
     * Save new configuration for view
     */
    protected function saveAction()
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return;
        } elseif (false === $this->validateFormToken()) {
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
            Tools::log()->notice('record-updated-correctly');
            $this->loadPageOptions();
            return;
        }

        Tools::log()->error('record-save-error');
    }

    /**
     * Loads the general display options for all users,
     * and indicates if they exist or not.
     *
     * @return bool
     */
    private function loadPageOptionsForAll(): bool
    {
        $where = [
            new DataBaseWhere('name', $this->selectedViewName),
            new DataBaseWhere('nick', null, 'IS'),
        ];
        return $this->model->loadFromCode('', $where);
    }

    /**
     * Loads the display options specific to the user.
     * If they do not exist, look for the display options common to all users.
     * In either case, it indicates whether it has found a configuration.
     *
     * @return bool
     */
    private function loadPageOptionsForUser(): bool
    {
        $where = [
            new DataBaseWhere('name', $this->selectedViewName),
            new DataBaseWhere('nick', $this->selectedUser),
        ];
        if ($this->model->loadFromCode('', $where)) {
            // Existen opciones para el usuario.
            return true;
        }

        if (false === $this->loadPageOptionsForAll()) {
            // No existe opciones generales. Asignamos las opciones por defecto de la vista xml al usuario.
            $this->model->nick = $this->selectedUser;
            return false;
        }

        // No existe opciones para el usuario. Clonamos las generales.
        $this->model->id = null;
        $this->model->nick = $this->selectedUser;
        return true;
    }

    private function setBackPage()
    {
        // check if the url is a real controller name
        $url = $this->request->get('url', '');
        $pageModel = new Page();
        foreach ($pageModel->all([], [], 0, 0) as $page) {
            if (substr($url, 0, strlen($page->name)) === $page->name) {
                $this->backPage = $url;
                return;
            }
        }

        // set the default back page
        $this->backPage = $this->selectedViewName;
    }

    /**
     * @param array $column
     * @param string $name
     * @param string $key
     * @param bool $isWidget
     * @param bool $allowEmpty
     */
    private function setColumnOption(&$column, string $name, string $key, bool $isWidget, bool $allowEmpty)
    {
        $newValue = Tools::noHtml($this->request->request->get($name . '-' . $key));
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
