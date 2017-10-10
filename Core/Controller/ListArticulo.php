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
 * Description of ListArticulo
 *
 * @author carlos
 */
class ListArticulo extends ExtendedController\ListController
{
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'Articulos';
        $pagedata['icon'] = 'fa-cubes';
        $pagedata['menu'] = 'almacen';

        return $pagedata;
    }

    protected function createViews()
    {
        $className = $this->getClassName();
        $this->addView('FacturaScripts\Core\Model\Articulo', $className);
        $this->addSearchFields($className, ['referencia', 'descripcion']);

        $this->addOrderBy($className, 'referencia', 'reference');
        $this->addOrderBy($className, 'descripcion', 'description');
        $this->addOrderBy($className, 'pvp', 'price');
        $this->addOrderBy($className, 'stockfis', 'stock');
    }
}
