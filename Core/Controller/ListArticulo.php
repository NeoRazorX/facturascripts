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

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewArticulo();
        $this->createViewArticuloProveedor();
        $this->createViewStock();
    }

    private function createViewArticulo()
    {
        $this->addView('ListArticulo', 'Articulo', 'products');
        $this->addSearchFields('ListArticulo', ['referencia', 'descripcion']);

        $selectValues = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect('ListArticulo', 'codfabricante', 'manufacturer', 'codfabricante', $selectValues);

        $familyValues = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect('ListArticulo', 'codfamilia', 'family', 'codfamilia', $familyValues);

        $this->addFilterCheckbox('ListArticulo', 'bloqueado', 'locked', 'bloqueado');
        $this->addFilterCheckbox('ListArticulo', 'publico', 'public', 'publico');

        $this->addOrderBy('ListArticulo', 'referencia', 'reference');
        $this->addOrderBy('ListArticulo', 'descripcion', 'description');
        $this->addOrderBy('ListArticulo', 'pvp', 'price');
        $this->addOrderBy('ListArticulo', 'stockfis', 'stock');
    }

    private function createViewArticuloProveedor()
    {
        $this->addView('ListArticuloProveedor', 'ArticuloProveedor', 'supplier-products');
        $this->addSearchFields('ListArticuloProveedor', ['referencia', 'descripcion']);

        $this->addFilterAutocomplete('ListArticuloProveedor', 'codproveedor', 'supplier', 'codproveedor', 'proveedores', 'codproveedor', 'nombre');

        $this->addOrderBy('ListArticuloProveedor', 'referencia', 'reference');
        $this->addOrderBy('ListArticuloProveedor', 'descripcion', 'description');
        $this->addOrderBy('ListArticuloProveedor', 'pvp', 'price');
        $this->addOrderBy('ListArticuloProveedor', 'stockfis', 'stock');
    }

    private function createViewStock()
    {
        $this->addView('ListStock', 'Stock', 'stock');
        $this->addSearchFields('ListStock', ['referencia', 'ubicacion']);

        $selectValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListStock', 'codalmacen', 'almacenes', 'codalmacen', $selectValues);

        $this->addOrderBy('ListStock', 'referencia', 'reference');
        $this->addOrderBy('ListStock', 'cantidad', 'quantity');
        $this->addOrderBy('ListStock', 'disponible', 'available');
    }
}
