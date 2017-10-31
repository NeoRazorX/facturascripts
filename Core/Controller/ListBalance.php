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
 * Description of ListBalance
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @author Fco. Antonio Moreno Pérez <famphuelva@gmail.com>
 */
class ListBalance extends ExtendedController\ListController
{
    /**
     * Devuelve los datos básicos de la página
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'balance';
        $pagedata['icon'] = 'fa-clipboard';
        $pagedata['menu'] = 'accounting';

        return $pagedata;
    }

    /**
     * Procedimiento para insertar vistas en el controlador
     */
    protected function createViews()
    {
        $className = $this->getClassName();
        $this->addView('FacturaScripts\Core\Model\Balance', $className);
        $this->addSearchFields($className, ['codbalance', 'naturaleza', 'descripcion1', 'descripcion2', 'descripcion3', 'descripcion4', 'descripcion4ba']);

        $this->addOrderBy($className, 'codbalance', 'code');
        $this->addOrderBy($className, 'descripcion1', 'description 1', 2); /// forzamos el orden por defecto descripcion1
        $this->addOrderBy($className, 'descripcion2', 'description 2', 3);
        $this->addOrderBy($className, 'descripcion3', 'description 3', 4);
        $this->addOrderBy($className, 'descripcion4', 'description 4', 5);
        $this->addOrderBy($className, 'descripcion4ba', 'description4ba', 6);
    }
}
