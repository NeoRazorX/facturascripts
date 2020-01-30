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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\ListController;
use FacturaScripts\Core\Model\Ejercicio;

/**
 * Controller to list the items in the Ejercicio model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class ListEjercicio extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'accounting';
        $data['title'] = 'exercises';
        $data['icon'] = 'fas fa-calendar-alt';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $viewName = 'ListEjercicio';
        $this->addView($viewName, 'Ejercicio', 'exercises', 'fas fa-calendar-alt');
        $this->addSearchFields($viewName, ['nombre', 'codejercicio']);
        $this->addOrderBy($viewName, ['fechainicio'], 'start-date', 2);
        $this->addOrderBy($viewName, ['codejercicio'], 'code');
        $this->addOrderBy($viewName, ['nombre'], 'name');
        $this->addOrderBy($viewName, ['idempresa, codejercicio'], 'company');

        /// filters
        $selectValues = $this->codeModel->all('empresas', 'idempresa', 'nombre');
        $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', $selectValues);

        $values = [
            ['label' => $this->toolBox()->i18n()->trans('all'), 'where' => []],
            ['label' => $this->toolBox()->i18n()->trans('only-active'), 'where' => [new DataBaseWhere('estado', Ejercicio::EXERCISE_STATUS_OPEN)]],
            ['label' => $this->toolBox()->i18n()->trans('only-closed'), 'where' => [new DataBaseWhere('estado', Ejercicio::EXERCISE_STATUS_CLOSED)]],
        ];
        $this->addFilterSelectWhere($viewName, 'status', $values);
    }
}
