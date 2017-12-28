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

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model;

/**
 * Edit option for any page.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditPageOption extends Base\Controller
{
    public $selectedUser;

    public $selectedViewName;

    public $model;

    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);
        $this->setTemplate('EditPageOption');
        $this->model = new Model\PageOption();
    }

    private function getParams()
    {
        $this->selectedViewName = $this->request->get('code');
        $this->selectedUser = $this->user->admin
            ? $this->request->get('nick', NULL)
            : $this->user->nick;
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);

        $this->getParams();
        $this->model->getForUser($this->selectedViewName, $this->selectedUser);

        if ($this->request->get('action', '') === 'save') {
            $this->saveData();
        }
    }

    private function getFilter()
    {
        return [
            new DataBaseWhere('name', $this->selectedViewName),
            new DataBaseWhere('user', $this->selectedUser)
        ];
    }

    private function checkNickAndID()
    {
        if ($this->model->nick != $this->selectedUser) {
            $this->model->id = NULL;
            $this->model->nick = empty($this->selectedUser) ? NULL : $this->selectedUser;
        }

        if ($this->model->nick === "") {
            $this->model->nick = NULL;
        }
    }

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
