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
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model;
use Symfony\Component\HttpFoundation\Response;

/**
 * Edit option for any page.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 * @author Fco. Antonio Moreno Pérez <famphuelva@gmail.com>
 */
class EditPageOption extends Base\Controller
{

    /**
     * Details of the view configuration
     *
     * @var Model\PageOption
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
     * Contains the url to go back.
     *
     * @var string
     */
    public $backPage;

    /**
     * Load and initialize the parameters sent by the form
     */
    private function getParams()
    {
        $this->selectedViewName = $this->request->get('code', '');
        $this->backPage = $this->request->get('url') ? : $this->selectedViewName;

        $this->selectedUser = $this->user->admin ? $this->request->get('nick', '') : $this->user->nick;
    }

    /**
     * Runs the controller's private logic.
     *
     * @param Response                   $response
     * @param Model\User                 $user
     * @param Base\ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        parent::privateCore($response, $user, $permissions);

        $this->getParams();
        $this->model = new Model\PageOption();
        $this->model->getForUser($this->selectedViewName, $this->selectedUser);

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
     * Checking the value of the nick field.
     * It determines if we edit a configuration for all the users or one,
     * and if there is already configuration for the nick
     */
    private function checkNickAndID()
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
     * Save new configuration for view
     */
    private function saveData()
    {
        $this->checkNickAndID();
        $data = $this->request->request->all();
        foreach ($data as $key => $value) {
            if (strpos($key, '+')) {
                $path = explode('+', $key);
                $this->model->columns[$path[0]]->columns[$path[1]]->{$path[2]} = $value;
            }
        }

        if ($this->model->save()) {
            $this->miniLog->notice($this->i18n->trans('record-updated-correctly'));

            return;
        }
        $this->miniLog->alert($this->i18n->trans('data-save-error'));
    }

    /**
     * Delete configuration for view
     */
    private function deleteData()
    {
        $nick = $this->request->get('nick');
        $where = [
            new DataBaseWhere('name', $this->selectedViewName)
        ];

        if (empty($nick)) {
            $where[] = new DataBaseWhere('nick', 'null', 'IS');
        } else {
            $where[] = new DataBaseWhere('nick', $nick);
        }

        $rows = $this->model->all($where, [], 0, 1);
        if ($rows[0] && $rows[0]->delete()) {
            $this->miniLog->notice($this->i18n->trans('record-deleted-correctly'));
            $this->model->getForUser($this->selectedViewName, $this->selectedUser);
        } else {
            $this->miniLog->alert($this->i18n->trans('default-not-deletable'));
        }
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'page-configuration';
        $pagedata['menu'] = 'admin';
        $pagedata['icon'] = 'fa-wrench';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Returns the text for the data main panel header
     *
     * @return string
     */
    public function getPanelHeader()
    {
        return $this->i18n->trans('configure-columns');
    }

    /**
     * Returns the text for the data main panel footer
     *
     * @return string
     */
    public function getPanelFooter()
    {
        return '<strong>'
            . $this->i18n->trans('page') . ':&nbsp;' . $this->selectedViewName . '<br>'
            . $this->i18n->trans('user') . ':&nbsp;' . $this->selectedUser
            . '</strong>';
    }

    /**
     * Get the list of users, excluding the user admin
     *
     * @return Array
     */
    public function getUserList()
    {
        $result = [];
        $users = Model\CodeModel::all(Model\User::tableName(), 'nick', 'nick', false);
        foreach ($users as $codeModel) {
            if ($codeModel->code != 'admin') {
                $result[$codeModel->code] = $codeModel->description;
            }
        }

        return $result;
    }
}
