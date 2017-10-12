<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\ExtendedController;

/**
 * Controlador para la lista de cuentas
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListCuenta extends ExtendedController\ListController
{
    protected function createViews()
    {
        /* Cuentas */
        $this->addView('FacturaScripts\Core\Model\Cuenta', 'ListCuenta', 'accounts');
        $this->addSearchFields('ListCuenta', ['descripcion', 'codcuenta', 'codejercicio', 'codepigrafe']);

        $this->addOrderBy('ListCuenta', 'codcuenta||codejercicio', 'code');
        $this->addOrderBy('ListCuenta', 'descripcion||codejercicio', 'description');

        $this->addFilterSelect('ListCuenta', 'codepigrafe', 'co_epigrafes', '', 'descripcion');
        $this->addFilterSelect('ListCuenta', 'codejercicio', 'ejercicios', '', 'nombre');

        /* Epigrafes */
        $this->addView('FacturaScripts\Core\Model\Epigrafe', 'ListEpigrafe', 'epigraphs');
        $this->addSearchFields('ListEpigrafe', ['descripcion', 'codepigrafe', 'codejercicio']);

        $this->addOrderBy('ListEpigrafe', 'descripcion||codejercicio', 'description');
        $this->addOrderBy('ListEpigrafe', 'codepigrafe||codejercicio', 'code');

        $this->addFilterSelect('ListEpigrafe', 'codgrupo', 'co_gruposepigrafes', '', 'descripcion');
        $this->addFilterSelect('ListEpigrafe', 'codejercicio', 'ejercicios', '', 'nombre');

        /* Grupo Epígrafes */
        $this->addView('FacturaScripts\Core\Model\GrupoEpigrafes', 'ListGrupoEpigrafes', 'epigraphs-group');
        $this->addSearchFields('ListGrupoEpigrafes', ['descripcion', 'codgrupo', 'codejercicio']);

        $this->addOrderBy('ListGrupoEpigrafes', 'codgrupo||codejercicio', 'code');
        $this->addOrderBy('ListGrupoEpigrafes', 'descripcion||codejercicio', 'description');

        $this->addFilterSelect('ListGrupoEpigrafes', 'codejercicio', 'ejercicios', '', 'nombre');
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'accounting-accounts';
        $pagedata['icon'] = 'fa-book';
        $pagedata['menu'] = 'accounting';

        return $pagedata;
    }
}
