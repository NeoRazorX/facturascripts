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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Model;

/**
 * Controller to edit a single item from the EditRol model
 *
 *
 * @author Artex Trading sa <jferrer@artextrading.com>
 */
class EditRol extends ExtendedController\PanelController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('\FacturaScripts\Dinamic\Model\Rol', 'EditRol', 'rol', 'fa-id-card');

        $this->addListView('\FacturaScripts\Dinamic\Model\RolAccess', 'ListRolAccess', 'page-rule', 'fa fa-check-square');
        $this->views['ListRolAccess']->disableColumn('role', true);

        $this->addEditListView('\FacturaScripts\Dinamic\Model\RolUser', 'EditRolUser', 'rol-user', 'fa-address-card-o');
        $this->views['EditRolUser']->disableColumn('role', true);
    }

    /**
     * Load view data
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditRol':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'EditRolUser':
                $where = [new DataBaseWhere('codrol', $this->getViewModelValue('EditRol', 'codrol'))];
                $view->loadData($where);
                break;

            case 'ListRolAccess':
                $where = [new DataBaseWhere('codrol', $this->getViewModelValue('EditRol', 'codrol'))];
                $view->loadData($where);
                break;
        }
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'rol';
        $pagedata['menu'] = 'admin';
        $pagedata['icon'] = 'fa-id-card-o';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * List of users in the group with the indicated role code
     *
     * @param string $codRol
     * @return array
     */
    private function getUsers($codRol): array
    {
        $result = [];
        $rolUserModel = new Model\RolUser();
        $rows = $rolUserModel->all([new DataBaseWhere('codrol', $codRol)]);
        foreach ($rows as $rolUser) {
            $result[] = $rolUser->nick;
        }
        return $result;
    }

    /**
     * Add the indicated page list to the Role group
     * and all users who are in that group
     *
     * @param string $codRol
     * @param Model\Page[] $pages
     * @throws \Exception
     */
    private function addRolAccess($codRol, $pages)
    {
        // add Pages to Rol
        if (!Model\RolAccess::addPagesToRol($codRol, $pages)) {
            throw new \Exception(self::$i18n->trans('cancel-process'));
        }

        // add Pages to User
        $users = $this->getUsers($codRol);
        foreach ($users as $nick) {
            if (!Model\PageRule::addPagesToUser($nick, $pages)) {
                throw new \Exception(self::$i18n->trans('cancel-process'));
            }
        }
    }

    /**
     * List of all the pages included in a menu option
     * and, optionally, included in a submenu option
     *
     * @return Model\Page[]
     */
    private function getPages() {
        $menu = $this->request->get('menu', '');
        $submenu = $this->request->get('submenu', '');
        $where = [new DataBaseWhere('menu', $menu)];
        if (!empty($submenu)) {
            $where[] = [new DataBaseWhere('submenu', $submenu)];
        }

        $page = new Model\Page();
        return $page->all($where);
    }

    /**
     * Run the actions that alter data before reading it
     *
     * @param BaseView $view
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($view, $action): bool
    {
        switch ($action) {
            case 'add-rol-access':
                $codRol = $this->request->get('code', '');
                $pages = $this->getPages();
                if (empty($pages) || empty($codRol)) {
                    return true;
                }

                $this->dataBase->beginTransaction();
                try {
                    $this->addRolAccess($codRol, $pages);
                    $this->dataBase->commit();
                } catch (\Exception $e) {
                    $this->dataBase->rollback();
                    $this->miniLog->notice($e->getMessage());
                }
                return true;

            default:
                return parent::execPreviousAction($view, $action);
        }
    }
}
