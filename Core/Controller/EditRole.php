<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController;
use FacturaScripts\Core\Model;

/**
 * Controller to edit a single item from the Role model.
 *
 * @author Artex Trading sa <jferrer@artextrading.com>
 */
class EditRole extends ExtendedController\PanelController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'role';
        $pagedata['menu'] = 'admin';
        $pagedata['icon'] = 'fas fa-id-card';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Add the indicated page list to the Role group
     * and all users who are in that group
     *
     * @param string       $codrole
     * @param Model\Page[] $pages
     *
     * @throws \Exception
     */
    private function addRoleAccess($codrole, $pages)
    {
        // add Pages to Rol
        if (!Model\RoleAccess::addPagesToRole($codrole, $pages)) {
            throw new \Exception($this->i18n->trans('cancel-process'));
        }
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('EditRole', 'Role', 'rol', 'fas fa-id-card');
        $this->addEditListView('EditRoleAccess', 'RoleAccess', 'rules', 'fas fa-check-square');
        $this->addEditListView('EditRoleUser', 'RoleUser', 'users', 'fas fa-address-card');

        /// Disable columns
        $this->views['EditRoleAccess']->disableColumn('role', true);
        $this->views['EditRoleUser']->disableColumn('role', true);
    }

    /**
     * Run the actions that alter data before reading it
     *
     * @param string   $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'add-rol-access':
                $codrole = $this->request->get('code', '');
                $pages = $this->getPages();
                if (empty($pages) || empty($codrole)) {
                    return true;
                }

                $this->dataBase->beginTransaction();
                try {
                    $this->addRoleAccess($codrole, $pages);
                    $this->dataBase->commit();
                } catch (\Exception $e) {
                    $this->dataBase->rollback();
                    $this->miniLog->notice($e->getMessage());
                }

                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * List of all the pages included in a menu option
     * and, optionally, included in a submenu option
     *
     * @return Model\Page[]
     */
    private function getPages()
    {
        $menu = $this->request->get('menu', '---null---');
        if ($menu === '---null---') {
            return [];
        }

        $page = new Model\Page();
        $where = [new DataBaseWhere('menu', $menu)];

        return $page->all($where);
    }

    /**
     * Load view data
     *
     * @param string                      $viewName
     * @param ExtendedController\EditView $view
     */
    protected function loadData($viewName, $view)
    {
        $order = [];
        switch ($viewName) {
            case 'EditRole':
                $code = $this->request->get('code');
                $view->loadData($code);
                break;

            case 'EditRoleAccess':
                $order['pagename'] = 'ASC';
            /// no break
            case 'EditRoleUser':
                $codrole = $this->getViewModelValue('EditRole', 'codrole');
                $where = [new DataBaseWhere('codrole', $codrole)];
                $view->loadData('', $where, $order, 0, 0);
                break;
        }
    }
}
