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

    public function __construct(&$cache, &$i18n, &$miniLog, $className)
    {
        parent::__construct($cache, $i18n, $miniLog, $className);
    }

    public function privateCore(&$response, $user)
    {
        parent::privateCore($response, $user);
    }

    protected function createViews()
    {
        /* Cuentas */
        $className = $this->getClassName();
        $index = $this->addView('FacturaScripts\Core\Model\Cuenta', $className, 'Cuentas');
        $this->addSearchFields($index, ['descripcion', 'codcuenta', 'codejercicio', 'codepigrafe']);

        $this->addOrderBy($index, 'codcuenta||codejercicio', 'code');
        $this->addOrderBy($index, 'descripcion||codejercicio', 'description');

        $this->addFilterSelect($index, 'codepigrafe', 'co_epigrafes', '', 'descripcion');
        $this->addFilterSelect($index, 'codejercicio', 'ejercicios', '', 'nombre');

        /* Epigrafes */
        $index = $this->addView('FacturaScripts\Core\Model\Epigrafe', 'ListEpigrafe', 'Epigrafes');
        $this->addSearchFields($index, ['descripcion', 'codepigrafe', 'codejercicio']);

        $this->addOrderBy($index, 'descripcion||codejercicio', 'description');
        $this->addOrderBy($index, 'codepigrafe||codejercicio', 'code');

        $this->addFilterSelect($index, 'codgrupo', 'co_gruposepigrafes', '', 'descripcion');
        $this->addFilterSelect($index, 'codejercicio', 'ejercicios', '', 'nombre');

        /* Grupo Epígrafes */
        $index = $this->addView('FacturaScripts\Core\Model\GrupoEpigrafes', 'ListGrupoEpigrafe', 'Grupo Epígrafes');
        $this->addSearchFields($index, ['descripcion', 'codgrupo', 'codejercicio']);

        $this->addOrderBy($index, 'codgrupo||codejercicio', 'code');
        $this->addOrderBy($index, 'descripcion||codejercicio', 'description');

        $this->addFilterSelect($index, 'codejercicio', 'ejercicios', '', 'nombre');
    }

    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Cuentas';
        $pagedata['icon'] = 'fa-th-list';
        $pagedata['menu'] = 'contabilidad';

        return $pagedata;
    }
}
