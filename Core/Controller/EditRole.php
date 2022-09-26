<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\RoleAccess;

/**
 * Controller to edit a single item from the Role model.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class EditRole extends EditController
{

    public function getAccessRules(): array
    {
        $rules = [];
        $i18n = $this->toolBox()->i18n();
        foreach ($this->getAllPages() as $page) {
            $rules[$page->name] = [
                'menu' => $i18n->trans($page->menu) . ' » ' . $i18n->trans($page->title),
                'show' => false,
                'onlyOwner' => false,
                'update' => false,
                'delete' => false
            ];
        }

        $roleAccessModel = new RoleAccess();
        $where = [new DataBaseWhere('codrole', $this->getModel()->primaryColumnValue())];
        foreach ($roleAccessModel->all($where, [], 0, 0) as $roleAccess) {
            $rules[$roleAccess->pagename]['show'] = true;
            $rules[$roleAccess->pagename]['onlyOwner'] = $roleAccess->onlyownerdata;
            $rules[$roleAccess->pagename]['update'] = $roleAccess->allowupdate;
            $rules[$roleAccess->pagename]['delete'] = $roleAccess->allowdelete;
            $rules[$roleAccess->pagename]['export'] = $roleAccess->allowexport;
            $rules[$roleAccess->pagename]['import'] = $roleAccess->allowimport;
        }

        return $rules;
    }

    public function getModelClassName(): string
    {
        return 'Role';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'role';
        $data['icon'] = 'fas fa-id-card';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createViewsAccess();
        $this->createViewsUsers();
        $this->setTabsPosition('bottom');
    }

    protected function createViewsAccess(string $viewName = 'RoleAccess')
    {
        $this->addHtmlView($viewName, 'Tab/RoleAccess', 'RoleAccess', 'rules', 'fas fa-check-square');
    }

    protected function createViewsUsers(string $viewName = 'EditRoleUser')
    {
        $this->addEditListView($viewName, 'RoleUser', 'users', 'fas fa-address-card');
        $this->views[$viewName]->setInLine(true);

        // Disable column
        $this->views[$viewName]->disableColumn('role', true);
    }

    protected function editRulesAction(): bool
    {
        // check user permissions
        if (false === $this->permissions->allowUpdate) {
            $this->toolBox()->i18nLog()->warning('not-allowed-update');
            return true;
        } elseif (false === $this->validateFormToken()) {
            return true;
        }

        $show = $this->request->request->get('show', []);
        $onlyOwner = $this->request->request->get('onlyOwner', []);
        $update = $this->request->request->get('update', []);
        $delete = $this->request->request->get('delete', []);
        $export = $this->request->request->get('export', []);
        $import = $this->request->request->get('import', []);

        // update or delete current access rules
        $roleAccessModel = new RoleAccess();
        $where = [new DataBaseWhere('codrole', $this->request->query->get('code'))];
        $rules = $roleAccessModel->all($where, [], 0, 0);
        foreach ($rules as $roleAccess) {
            // delete rule?
            if (false === is_array($show) || false === in_array($roleAccess->pagename, $show)) {
                $roleAccess->delete();
                continue;
            }

            // update
            $roleAccess->onlyownerdata = is_array($onlyOwner) && in_array($roleAccess->pagename, $onlyOwner);
            $roleAccess->allowupdate = is_array($update) && in_array($roleAccess->pagename, $update);
            $roleAccess->allowdelete = is_array($delete) && in_array($roleAccess->pagename, $delete);
            $roleAccess->allowexport = is_array($export) && in_array($roleAccess->pagename, $export);
            $roleAccess->allowimport = is_array($import) && in_array($roleAccess->pagename, $import);
            $roleAccess->save();
        }

        // add new rules
        foreach ($show as $pageName) {
            $found = false;
            foreach ($rules as $rule) {
                if ($rule->pagename === $pageName) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                continue;
            }

            // add
            $newRoleAccess = new RoleAccess();
            $newRoleAccess->codrole = $this->request->query->get('code');
            $newRoleAccess->pagename = $pageName;
            $newRoleAccess->onlyownerdata = is_array($onlyOwner) && in_array($pageName, $onlyOwner);
            $newRoleAccess->allowupdate = is_array($update) && in_array($pageName, $update);
            $newRoleAccess->allowdelete = is_array($delete) && in_array($pageName, $delete);
            $newRoleAccess->allowexport = is_array($export) && in_array($pageName, $export);
            $newRoleAccess->allowimport = is_array($import) && in_array($pageName, $import);
            $newRoleAccess->save();
        }

        $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        return true;
    }

    /**
     * Run the actions that alter data before reading it
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        if ($action == 'edit-rules') {
            return $this->editRulesAction();
        }

        return parent::execPreviousAction($action);
    }

    /**
     * List of all the pages.
     *
     * @return Page[]
     */
    protected function getAllPages(): array
    {
        $page = new Page();
        $order = ['menu' => 'ASC', 'title' => 'ASC'];
        return $page->all([], $order, 0, 0);
    }

    /**
     * Load view data
     *
     * @param string $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditRoleUser':
                $code = $this->getViewModelValue($this->getMainViewName(), 'codrole');
                $where = [new DataBaseWhere('codrole', $code)];
                $view->loadData('', $where, ['id' => 'DESC']);
                break;

            default:
                parent::loadData($viewName, $view);
        }
    }
}
