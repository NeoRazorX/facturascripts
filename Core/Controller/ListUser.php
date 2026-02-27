<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\DataSrc\Agentes;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Tools;

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
    protected function createViews(): void
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
            ->addOrderBy(['email'], 'email')
            ->addOrderBy(['creationdate'], 'creation-date')
            ->addOrderBy(['lastactivity'], 'last-activity')
            ->setSettings('btnPrint', false);

        if ($this->user->admin) {
            $this->addOrderBy($viewName, ['level'], 'level');
        }

        // filters
        $companies = Empresas::codeModel();
        if (count($companies) > 2) {
            $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $companies);
        }

        $warehouses = Almacenes::codeModel();
        if (count($warehouses) > 2) {
            $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'codalmacen', $warehouses);
        }

        $series = Series::codeModel();
        if (count($series) > 2) {
            $this->addFilterSelect($viewName, 'codserie', 'series', 'codserie', $series);
        }

        $agents = Agentes::codeModel();
        if (count($agents) > 2) {
            $this->addFilterSelect($viewName, 'codagente', 'agent', 'codagente', $agents);
        }

        $this->listView($viewName)
            ->addFilterSelectWhere('type', [
                [
                    'label' => Tools::trans('all'),
                    'where' => []
                ],
                [
                    'label' => '------',
                    'where' => []
                ],
                [
                    'label' => Tools::trans('admin'),
                    'where' => [new DataBaseWhere('admin', true)]
                ],
                [
                    'label' => Tools::trans('no-admin'),
                    'where' => [new DataBaseWhere('admin', false)]
                ]
            ])
            ->addFilterSelectWhere('2fa', [
                [
                    'label' => Tools::trans('two-factor-auth'),
                    'where' => []
                ],
                [
                    'label' => '------',
                    'where' => []
                ],
                [
                    'label' => Tools::trans('two-factor-auth-enabled'),
                    'where' => [new DataBaseWhere('two_factor_enabled', true)]
                ],
                [
                    'label' => Tools::trans('two-factor-auth-disabled'),
                    'where' => [new DataBaseWhere('two_factor_enabled', false)]
                ]
            ]);

        if ($this->user->admin) {
            $levels = $this->codeModel->all('users', 'level', 'level');
            $this->addFilterSelect($viewName, 'level', 'level', 'level', $levels);
        }

        $languages = $this->codeModel->all('users', 'langcode', 'langcode');
        if (count($languages) > 2) {
            $this->addFilterSelect($viewName, 'langcode', 'language', 'langcode', $languages);
        }
    }
}
