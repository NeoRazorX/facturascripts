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

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the Articulo model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListArticulo extends ExtendedController\ListController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        /* Artículos */
        $this->addView('Articulo', 'ListArticulo', 'products');
        $this->addSearchFields('ListArticulo', ['referencia', 'descripcion']);

        $this->addFilterSelect('ListArticulo', 'codfabricante', 'fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect('ListArticulo', 'codfamilia', 'familias', 'codfamilia', 'descripcion');
        $this->addFilterCheckbox('ListArticulo', 'bloqueado', 'locked', 'bloqueado');
        $this->addFilterCheckbox('ListArticulo', 'publico', 'public', 'publico');

        $this->addOrderBy('ListArticulo', 'referencia', 'reference');
        $this->addOrderBy('ListArticulo', 'descripcion', 'description');
        $this->addOrderBy('ListArticulo', 'pvp', 'price');
        $this->addOrderBy('ListArticulo', 'stockfis', 'stock');

        /* Artículos de proveedor */
        $this->addView('ArticuloProveedor', 'ListArticuloProveedor', 'supplier-products');
        $this->addSearchFields('ListArticuloProveedor', ['referencia', 'descripcion']);

        $this->addFilterSelect('ListArticuloProveedor', 'codproveedor', 'proveedores', 'codproveedor', 'nombre');

        $this->addOrderBy('ListArticuloProveedor', 'referencia', 'reference');
        $this->addOrderBy('ListArticuloProveedor', 'descripcion', 'description');
        $this->addOrderBy('ListArticuloProveedor', 'pvp', 'price');
        $this->addOrderBy('ListArticuloProveedor', 'stockfis', 'stock');

        /* Stock */
        $this->addView('Stock', 'ListStock', 'stock');
        $this->addSearchFields('ListStock', ['referencia', 'ubicacion']);

        $this->addFilterSelect('ListStock', 'codalmacen', 'almacenes', 'codalmacen', 'nombre');

        $this->addOrderBy('ListStock', 'referencia', 'reference');
        $this->addOrderBy('ListStock', 'cantidad', 'quantity');
        $this->addOrderBy('ListStock', 'disponible', 'available');
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'products';
        $pagedata['icon'] = 'fa-cubes';
        $pagedata['menu'] = 'warehouse';

        return $pagedata;
    }
}
