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
 * Controlador para la lista de paises
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class ListPais extends ExtendedController\ListController
{

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'countries';
        $pagedata['icon'] = 'fa-globe';
        $pagedata['menu'] = 'admin';

        return $pagedata;
    }

    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        $className = $this->getClassName();
        $this->addView('FacturaScripts\Core\Model\Pais', $className);
        $this->addSearchFields($className, ['nombre', 'codiso', 'codpais']);

        $this->addFilterCheckbox($className, 'validarprov', 'validate-states');
        $this->addOrderBy($className, 'codpais', 'code');
        $this->addOrderBy($className, 'nombre', 'name');
        $this->addOrderBy($className, 'codiso', 'codiso');
    }
}
