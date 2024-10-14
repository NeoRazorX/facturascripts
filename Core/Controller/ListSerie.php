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

use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Tools;

/**
 * Controller to list the items in the Serie model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListSerie extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'series';
        $data['icon'] = 'fa-solid fa-layer-group';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsSeries();
    }

    protected function createViewsSeries(string $viewName = 'ListSerie'): void
    {
        $this->addView($viewName, 'Serie', 'series', 'fa-solid fa-layer-group')
            ->addSearchFields(['descripcion', 'codserie'])
            ->addOrderBy(['codserie'], 'code')
            ->addOrderBy(['descripcion'], 'description');

        // filtros
        $this->addFilterCheckbox($viewName, 'siniva', 'without-tax', 'siniva');

        $this->addFilterSelect($viewName, 'tipo', 'type', 'tipo', [
            '' => '------',
            'R' => Tools::lang()->trans('rectifying'),
            'S' => Tools::lang()->trans('simplified'),
        ]);
    }
}
