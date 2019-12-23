<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * Details of the view configuration
     *
     * @var PageOption
     */
    public $model;

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
        $data['title'] = 'page-configuration';
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
        $this->selectedViewName = $this->request->get('code', '');
        $this->backPage = $this->request->get('url') ?: $this->selectedViewName;
        $this->selectedUser = $this->user->admin ? $this->request->get('nick', '') : $this->user->nick;
        $this->loadPageOptions();

        $action = $this->request->get('action', '');
        switch ($action) {
            case 'save':
                $this->saveData();
                break;

            case 'delete':
                $this->deleteData();
                break;
        }
    }

    /**
     * Checks and fix GroupItem array.
     *
     * @param array $group
     *
     * @return array
     */
    protected function checkGroupItem($group)
    {
        foreach ($group['children'] as $key => $child) {
            if (!isset($child['level'])) {
                $group['children'][$key]['level'] = 0;
            }

            if (!isset($child['children'][0]['readonly'])) {
                $group['children'][$key]['children'][0]['readonly'] = 'false';
            }
        }

        return $group;
    }

    /**
     * Checking the value of the nick field.
     * It determines if we edit a configuration for all the users or one,
     * and if there is already configuration for the nick
     */
    protected function checkNickAndID()
    {
        if ($this->model->nick != $this->selectedUser) {
            $this->model->id = null;
            $this->model->nick = empty($this->selectedUser) ? null : $this->selectedUser;
        }

        if ($this->model->nick === '') {
            $this->model->nick = null;
        }
    }

    /**
     * Delete configuration for view
     */
    protected function deleteData()
    {
        $nick = $this->request->get('nick');
        $where = [
            new DataBaseWhere('name', $this->selectedViewName)
        ];

        $where[] = empty($nick) ? new DataBaseWhere('nick', null, 'IS') : new DataBaseWhere('nick', $nick);
        $rows = $this->model->all($where, [], 0, 1);
        if ($rows[0] && $rows[0]->delete()) {
            $this->toolBox()->i18nLog()->notice('record-deleted-correctly');
            $this->loadPageOptions();
        } else {
            $this->toolBox()->i18nLog()->warning('default-not-deletable');
        }
    }

    /**
     *
     */
    protected function loadPageOptions()
    {
        $orderby = ['nick' => 'ASC'];
        $where = [
            new DataBaseWhere('name', $this->selectedViewName),
            new DataBaseWhere('nick', $this->selectedUser),
            new DataBaseWhere('nick', null, 'IS', 'OR'),
        ];

        if (!$this->model->loadFromCode('', $where, $orderby)) {
            VisualItemLoadEngine::installXML($this->selectedViewName, $this->model);
        }

        // there always need to be groups of columns
        $groups = [];
        $newGroupArray = [
            'children' => [],
            'name' => 'main',
            'tag' => 'group',
        ];

        foreach ($this->model->columns as $key => $item) {
            if ($item['tag'] === 'group') {
                $groups[$key] = $this->checkGroupItem($item);
            } else {
                $newGroupArray['children'][$key] = $item;
            }
        }

        /// is there are loose columns, then we put it on a new group
        if (!empty($newGroupArray['children'])) {
            $groups['main'] = $this->checkGroupItem($newGroupArray);
        }

        $this->model->columns = $groups;
    }

    /**
     * Save new configuration for view
     */
    protected function saveData()
    {
        $this->checkNickAndID();
        $data = $this->request->request->all();
        foreach ($data as $key => $value) {
            if (\strpos($key, '+')) {
                $path = \explode('+', $key);
                $item = &$this->model->columns[$path[0]]['children'][$path[1]];
                if (\in_array('widget', $path)) {
                    $item['children'][0][$path[3]] = $value;
                    continue;
                }

                $item[$path[2]] = $value;
            }
        }

        if ($this->model->save()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
    }
}
