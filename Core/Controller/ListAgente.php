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
use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list the items in the Agentes model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListAgente extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'agents';
        $data['icon'] = 'fas fa-user-tie';
        return $data;
    }

    /**
     * Add Agent View
     *
     * @param string $viewName
     */
    protected function createAgentView(string $viewName = 'ListAgente')
    {
        $this->addView($viewName, 'Agente', 'agents', 'fas fa-user-tie');
        $this->addOrderBy($viewName, ['codagente'], 'code');
        $this->addOrderBy($viewName, ['nombre'], 'name', 1);
        $this->addSearchFields($viewName, ['nombre', 'codagente', 'email', 'telefono1', 'telefono2', 'observaciones']);

        // Filters
        $this->addFilterSelectWhere($viewName, 'status', [
            ['label' => $this->toolBox()->i18n()->trans('only-active'), 'where' => [new DataBaseWhere('debaja', false)]],
            ['label' => $this->toolBox()->i18n()->trans('only-suspended'), 'where' => [new DataBaseWhere('debaja', true)]],
            ['label' => $this->toolBox()->i18n()->trans('all'), 'where' => []]
        ]);

        $cargos = $this->codeModel->all('agentes', 'cargo', 'cargo');
        $this->addFilterSelect($viewName, 'cargo', 'position', 'cargo', $cargos);
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createAgentView();
    }
}
