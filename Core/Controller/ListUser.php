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

use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list the items in the User model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListUser extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'users';
        $data['icon'] = 'fa-solid fa-users';
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

    protected function createViewsRoles(string $viewName = 'ListRole'): void
    {
        $this->addView($viewName, 'Role', 'roles', 'fa-solid fa-address-card')
            ->addSearchFields(['codrole', 'descripcion'])
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['codrole'], 'code');
    }

    protected function createViewsUsers(string $viewName = 'ListUser'): void
    {
        $this->addView($viewName, 'User', 'users', 'fa-solid fa-users')
            ->addSearchFields(['nick', 'email'])
            ->addOrderBy(['nick'], 'nick', 1)
            ->addOrderBy(['email'], 'email');

        if ($this->user->admin) {
            $this->addOrderBy($viewName, ['level'], 'level');
        }

        $this->addOrderBy($viewName, ['creationdate'], 'creation-date');
        $this->addOrderBy($viewName, ['lastactivity'], 'last-activity');

        // filters
        if ($this->user->admin) {
            $levels = $this->codeModel->all('users', 'level', 'level');
            $this->addFilterSelect($viewName, 'level', 'level', 'level', $levels);
        }

        $languages = $this->codeModel->all('users', 'langcode', 'langcode');
        $this->addFilterSelect($viewName, 'langcode', 'language', 'langcode', $languages);

        $companies = Empresas::codeModel();
        if (count($companies) > 2) {
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $companies);
        }

        $warehouses = Almacenes::codeModel();
        if (count($warehouses) > 2) {
            $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'codalmacen', $warehouses);
        }

        // disable print button
        $this->setSettings($viewName, 'btnPrint', false);
    }
}
