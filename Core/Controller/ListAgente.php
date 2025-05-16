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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Tools;

/**
 * Controller to list the items in the Agentes model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListAgente extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'admin';
        $data['title'] = 'agents';
        $data['icon'] = 'fa-solid fa-user-tie';
        return $data;
    }

    protected function createAgentView(string $viewName = 'ListAgente'): void
    {
        $this->addView($viewName, 'Agente', 'agents', 'fa-solid fa-user-tie')
            ->addSearchFields(['nombre', 'codagente', 'email', 'telefono1', 'telefono2', 'observaciones'])
            ->addOrderBy(['codagente'], 'code')
            ->addOrderBy(['nombre'], 'name', 1);

        // Filters
        $this->addFilterSelectWhere($viewName, 'status', [
            ['label' => Tools::lang()->trans('only-active'), 'where' => [new DataBaseWhere('debaja', false)]],
            ['label' => Tools::lang()->trans('only-suspended'), 'where' => [new DataBaseWhere('debaja', true)]],
            ['label' => Tools::lang()->trans('all'), 'where' => []]
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
