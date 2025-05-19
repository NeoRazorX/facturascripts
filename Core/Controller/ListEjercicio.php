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
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Tools;

/**
 * Controller to list the items in the Ejercicio model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListEjercicio extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'exercises';
        $data['icon'] = 'fa-solid fa-calendar-alt';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $viewName = 'ListEjercicio';
        $this->addView($viewName, 'Ejercicio', 'exercises', 'fa-solid fa-calendar-alt')
            ->addSearchFields(['nombre', 'codejercicio'])
            ->addOrderBy(['fechainicio'], 'start-date', 2)
            ->addOrderBy(['codejercicio'], 'code')
            ->addOrderBy(['nombre'], 'name')
            ->addOrderBy(['idempresa, codejercicio'], 'company');

        // filters
        $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', Empresas::codeModel());

        $this->addFilterSelectWhere($viewName, 'status', [
            [
                'label' => Tools::lang()->trans('all'),
                'where' => []
            ],
            [
                'label' => Tools::lang()->trans('only-active'),
                'where' => [new DataBaseWhere('estado', Ejercicio::EXERCISE_STATUS_OPEN)]
            ],
            [
                'label' => Tools::lang()->trans('only-closed'),
                'where' => [new DataBaseWhere('estado', Ejercicio::EXERCISE_STATUS_CLOSED)]
            ],
        ]);
    }
}
