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
 * Description of ListProveedor
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListProveedor extends ExtendedController\ListController
{

    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'suppliers';
        $pagedata['icon'] = 'fa-users';
        $pagedata['menu'] = 'purchases';

        return $pagedata;
    }

    /**
     * Procedimiento encargado de insertar las vistas a visualizar
     */
    protected function createViews()
    {
        $className = $this->getClassName();
        $this->addView('FacturaScripts\Core\Model\Proveedor', $className);
        $this->addSearchFields($className, ['nombre', 'razonsocial', 'codproveedor', 'email']);

        $this->addOrderBy($className, 'codproveedor', 'code');
        $this->addOrderBy($className, 'nombre', 'name', 1);
        $this->addOrderBy($className, 'fecha', 'date');

        $this->addFilterCheckbox($className, 'debaja', 'suspended');
    }
}
