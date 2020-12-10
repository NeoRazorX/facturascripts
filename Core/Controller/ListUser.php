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

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list the items in the User model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListUser extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'users';
        $data['icon'] = 'fas fa-users';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsUsers();
        $this->createViewsRoles();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsRoles(string $viewName = 'ListRole')
    {
        $this->addView($viewName, 'Role', 'roles', 'fas fa-address-card');
        $this->addSearchFields($viewName, ['codrole', 'descripcion']);
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addOrderBy($viewName, ['codrole'], 'code');
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewsUsers(string $viewName = 'ListUser')
    {
        $this->addView($viewName, 'User', 'users', 'fas fa-users');
        $this->addSearchFields($viewName, ['nick', 'email']);
        $this->addOrderBy($viewName, ['nick'], 'nick', 1);
        $this->addOrderBy($viewName, ['email'], 'email');
        $this->addOrderBy($viewName, ['level'], 'level');
        $this->addOrderBy($viewName, ['creationdate'], 'creation-date');
        $this->addOrderBy($viewName, ['lastactivity'], 'last-activity');

        /// filters
        $levels = $this->codeModel->all('users', 'level', 'level');
        $this->addFilterSelect($viewName, 'level', 'level', 'level', $levels);

        $languages = $this->codeModel->all('users', 'langcode', 'langcode');
        $this->addFilterSelect($viewName, 'langcode', 'language', 'langcode', $languages);

        $companies = $this->codeModel->all('empresas', 'idempresa', 'nombrecorto');
        if (\count($companies) > 2) {
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $companies);
        }

        $warehouses = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        if (\count($warehouses) > 2) {
            $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'codalmacen', $warehouses);
        }
    }
}
