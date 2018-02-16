<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the User model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListUser extends ExtendedController\ListController
{
    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('\FacturaScripts\Dinamic\Model\User', 'ListUser', 'users', 'fa-users');
        $this->addSearchFields('ListUser', ['nick', 'email']);

        $this->addOrderBy('ListUser', 'nick');
        $this->addOrderBy('ListUser', 'email');

        /* Roles */
        $this->addView('\FacturaScripts\Dinamic\Model\Role', 'ListRole', 'roles', 'fa-address-card-o');
        $this->addSearchFields('ListRole', ['codrole', 'descripcion']);

        $this->addOrderBy('ListRole', 'descripcion', 'description');
        $this->addOrderBy('ListRole', 'codrole', 'code');
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'users';
        $pagedata['icon'] = 'fa-users';
        $pagedata['menu'] = 'admin';

        return $pagedata;
    }
}
