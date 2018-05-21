<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the Serie model
 *
 * @author Raul Jimenez <raul.jimenez@nazcanetworks.xom>
 * 
 * 
 */
class ListDiario extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     * 
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'accounting-books';
        $pagedata['icon'] = 'fa fa-book';
        $pagedata['menu'] = 'accounting';

        return $pagedata;
    }

    /**
     * load views
     */
    protected function createViews()
    {
        $this->addView('ListDiario', 'Diario');
        $this->addSearchFields('ListDiario', ['nombre']);
        $this->addOrderBy('ListDiario', 'coddiario', 'code');
        $this->addOrderBy('ListDiario', 'nombre', 'name');
    }
}
