<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        /* Cuentas */
        $this->addView('FacturaScripts\Core\Model\Cuenta', 'ListCuenta', 'accounts', 'fa-book');
        $this->addSearchFields('ListCuenta', ['descripcion', 'codcuenta', 'codejercicio', 'codepigrafe']);

        $this->addOrderBy('ListCuenta', 'codejercicio desc,codcuenta', 'code');
        $this->addOrderBy('ListCuenta', 'codejercicio desc,descripcion', 'description');

        $this->addFilterSelect('ListCuenta', 'codejercicio', 'ejercicios', '', 'nombre');
        $this->addFilterSelect('ListCuenta', 'codepigrafe', 'co_epigrafes', '', 'descripcion');

        /* Epigrafes */
        $this->addView('FacturaScripts\Core\Model\Epigrafe', 'ListEpigrafe', 'epigraphs', 'fa-list-alt');
        $this->addSearchFields('ListEpigrafe', ['descripcion', 'codepigrafe', 'codejercicio']);

        $this->addOrderBy('ListEpigrafe', 'codejercicio desc,descripcion', 'description');
        $this->addOrderBy('ListEpigrafe', 'codejercicio desc,codepigrafe', 'code');

        $this->addFilterSelect('ListEpigrafe', 'codejercicio', 'ejercicios', '', 'nombre');
        $this->addFilterSelect('ListEpigrafe', 'codgrupo', 'co_gruposepigrafes', '', 'descripcion');

        /* Grupo Epígrafes */
        $this->addView('FacturaScripts\Core\Model\GrupoEpigrafes', 'ListGrupoEpigrafes', 'epigraphs-group', 'fa-bars');
        $this->addSearchFields('ListGrupoEpigrafes', ['descripcion', 'codgrupo', 'codejercicio']);

        $this->addOrderBy('ListGrupoEpigrafes', 'codejercicio desc,codgrupo', 'code');
        $this->addOrderBy('ListGrupoEpigrafes', 'codejercicio desc,descripcion', 'description');

        $this->addFilterSelect('ListGrupoEpigrafes', 'codejercicio', 'ejercicios', '', 'nombre');
    }

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'accounting-accounts';
        $pagedata['icon'] = 'fa-book';
        $pagedata['menu'] = 'accounting';

        return $pagedata;
    }
}
