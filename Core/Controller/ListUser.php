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

use FacturaScripts\Dinamic\Lib\ExtendedController;

/**
 * Controller to list the items in the User model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListUser extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'users';
        $pagedata['icon'] = 'fas fa-users';
        $pagedata['menu'] = 'admin';

        return $pagedata;
    }

    /**
     * 
     * @param string $name
     */
    protected function createViewRoles($name = 'ListRole')
    {
        $this->addView($name, 'Role', 'roles', 'fas fa-address-card');
        $this->addSearchFields($name, ['codrole', 'descripcion']);
        $this->addOrderBy($name, ['descripcion'], 'description');
        $this->addOrderBy($name, ['codrole'], 'code');
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewUsers();
        $this->createViewRoles();
    }

    /**
     * 
     * @param string $name
     */
    protected function createViewUsers($name = 'ListUser')
    {
        $this->addView($name, 'User', 'users', 'fas fa-users');
        $this->addSearchFields($name, ['nick', 'email']);
        $this->addOrderBy($name, ['nick'], 'nick', 1);
        $this->addOrderBy($name, ['email'], 'email');
        $this->addOrderBy($name, ['level'], 'level');
        $this->addOrderBy($name, ['lastactivity'], 'last-activity');

        /// filters
        $levels = $this->codeModel->all('users', 'level', 'level');
        $this->addFilterSelect($name, 'level', 'level', 'level', $levels);

        $companies = $this->codeModel->all('empresas', 'idempresa', 'nombrecorto');
        $this->addFilterSelect($name, 'company', 'company', 'idempresa', $companies);

        $languages = $this->codeModel->all('users', 'langcode', 'langcode');
        $this->addFilterSelect($name, 'langcode', 'lang-code', 'langcode', $languages);
    }
}
