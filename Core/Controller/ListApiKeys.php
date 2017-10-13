<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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
 * Description of ListApiKeys
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class ListApiKeys extends ExtendedController\ListController 
{
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'APIKeys';
        $pagedata['icon'] = 'fa-exchange';
        $pagedata['menu'] = 'admin';

        return $pagedata;
    }

    protected function createViews()
    {
        $className = $this->getClassName();
        $this->addView('FacturaScripts\Core\Model\ApiKeys', $className);
        $this->addSearchFields($className, ['id', 'apikey', 'descripcion', 'enabled']);

        $this->addOrderBy($className, 'descripcion', 'Descripcion');
        $this->addOrderBy($className, 'f_alta', 'Fecha');
        $this->addOrderBy($className, 'usuario_creacion','Usuario');
    }
}
