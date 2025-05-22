<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DbQuery;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Core\Model\Role;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\RoleAccess;

/**
 * Controller to edit a single item from the Role model.
 *
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 */
class EditRole extends EditController
{
    public function getAccessRules(): array
    {
        $rules = [];
        $i18n = Tools::lang();
        foreach ($this->getAllPages() as $page) {
            $rules[$page->name] = [
                'menu' => $i18n->trans($page->menu),
                'submenu' => $i18n->trans($page->submenu),
                'page' => $i18n->trans($page->title),
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
        $data['icon'] = 'fa-solid fa-id-card';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        // desactivamos los botones de opciones e imprimir
        $mvn = $this->getMainViewName();
        $this->setSettings($mvn, 'btnOptions', false);
        $this->setSettings($mvn, 'btnPrint', false);

        $this->createViewsAccess();
        $this->createViewsUsers();
    }

    protected function createViewsAccess(string $viewName = 'RoleAccess'): void
    {
        $this->addHtmlView($viewName, 'Tab/RoleAccess', 'RoleAccess', 'rules', 'fa-solid fa-check-square');
    }

    protected function createViewsUsers(string $viewName = 'EditRoleUser'): void
    {
        $this->addEditListView($viewName, 'RoleUser', 'users', 'fa-solid fa-address-card')
            ->disableColumn('role', true)
            ->setInLine(true);
    }

    protected function editRulesAction(): bool
    {
        // comprobamos permisos y token
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-update');
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

        // actualizamos los permisos del rol
        $roleAccessModel = new RoleAccess();
        $where = [new DataBaseWhere('codrole', $this->request->query->get('code'))];
        $rules = $roleAccessModel->all($where, [], 0, 0);
        foreach ($rules as $roleAccess) {
            // eliminamos la regla?
            if (false === is_array($show) || false === in_array($roleAccess->pagename, $show)) {
                $roleAccess->delete();
                continue;
            }

            // actualizamos la regla
            $roleAccess->onlyownerdata = is_array($onlyOwner) && in_array($roleAccess->pagename, $onlyOwner);
            $roleAccess->allowupdate = is_array($update) && in_array($roleAccess->pagename, $update);
            $roleAccess->allowdelete = is_array($delete) && in_array($roleAccess->pagename, $delete);
            $roleAccess->allowexport = is_array($export) && in_array($roleAccess->pagename, $export);
            $roleAccess->allowimport = is_array($import) && in_array($roleAccess->pagename, $import);
            $roleAccess->save();
        }

        // añadimos las nuevas reglas
        foreach ($show as $pageName) {
            // comprobamos si ya existe la regla
            foreach ($rules as $rule) {
                if ($rule->pagename === $pageName) {
                    continue 2;
                }
            }

            // añadimos la regla
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

        // Eliminamos los permisos huérfanos
        $this->removeOrphanAccess();

        Tools::log()->notice('record-updated-correctly');
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
        $orderBy = ['menu' => 'ASC', 'submenu' => 'ASC', 'title' => 'ASC'];
        return $page->all([], $orderBy, 0, 0);
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

    protected function removeOrphanAccess(): void
    {
        $pages = Page::all([], [], 0, 0);
        $roleAccess = RoleAccess::all([], [], 0, 0);
        $pageNames = array_column($pages, 'name');
        $roleAccessPageNames = array_column($roleAccess, 'pagename');

        $orphanPages = array_diff($roleAccessPageNames, $pageNames);
        foreach ($orphanPages as $pageName) {
            $page = new RoleAccess();
            $page->loadFromCode('', [new DataBaseWhere('pagename', $pageName)]);
            $page->delete();

            // si el rol ya no tiene permisos, lo eliminamos.
            $rolesLength = DbQuery::table(RoleAccess::tableName())->whereEq('codrole', $page->codrole)->count();

            if ($rolesLength === 0) {
                $role = new Role();
                $role->loadFromCode('', [new DataBaseWhere('codrole', $page->codrole)]);
                $role->delete();

                // redireccionamos al listado, ya que el rol lo hemos borrado
                $this->redirect((new User())->url() . '?activetab=ListRole');
            }
        }
    }
}
