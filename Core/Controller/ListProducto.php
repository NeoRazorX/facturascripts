<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Controller to list the items in the Producto model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListProducto extends ExtendedController\ListController
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
        $this->createViewProducto();
        $this->createViewVariante();
    }

    private function createViewProducto()
    {
        $this->addView('ListProducto', 'Producto', 'products', 'fa-cubes');
        $this->addSearchFields('ListProducto', ['referencia', 'descripcion', 'observaciones']);
        $this->addOrderBy('ListProducto', ['referencia'], 'reference');
        $this->addOrderBy('ListProducto', ['descripcion'], 'description');
        $this->addOrderBy('ListProducto', ['precio'], 'price');
        $this->addOrderBy('ListProducto', ['stockfis'], 'stock');

        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect('ListProducto', 'codfabricante', 'manufacturer', 'codfabricante', $manufacturers);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect('ListProducto', 'codfamilia', 'family', 'codfamilia', $families);
        
        $taxes = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect('ListProducto', 'codimpuesto', 'tax', 'codimpuesto', $taxes);

        $this->addFilterCheckbox('ListProducto', 'nostock', 'no-stock', 'nostock');
        $this->addFilterCheckbox('ListProducto', 'bloqueado', 'locked', 'bloqueado');
        $this->addFilterCheckbox('ListProducto', 'secompra', 'for-purchase', 'secompra');
        $this->addFilterCheckbox('ListProducto', 'sevende', 'for-sale', 'sevende');
        $this->addFilterCheckbox('ListProducto', 'publico', 'public', 'publico');
    }
    
    private function createViewVariante()
    {
        $this->addView('ListVariante', 'Variante', 'Variants', 'fa-code-fork');
        $this->addSearchFields('ListVariante', ['referencia', 'codbarras']);
        $this->addOrderBy('ListVariante', ['referencia'], 'reference');
        $this->addOrderBy('ListVariante', ['codbarras'], 'barcode');
        $this->addOrderBy('ListVariante', ['precio'], 'price');
        $this->addOrderBy('ListVariante', ['coste'], 'cost-price');
        
        $attributeValues = $this->codeModel->all('atributos_valores', 'id', 'descripcion');
        $this->addFilterSelect('ListVariante', 'idatributovalor1', 'attribute-value-1', 'idatributovalor1', $attributeValues);
        $this->addFilterSelect('ListVariante', 'idatributovalor2', 'attribute-value-2', 'idatributovalor2', $attributeValues);
    }

    private function createViewStock()
    {
        $this->addView('ListStock', 'Stock', 'stock', 'fa-tasks');
        $this->addSearchFields('ListStock', ['referencia', 'ubicacion']);
        $this->addOrderBy('ListStock', ['referencia'], 'reference');
        $this->addOrderBy('ListStock', ['cantidad'], 'quantity');
        $this->addOrderBy('ListStock', ['disponible'], 'available');

        $selectValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect('ListStock', 'codalmacen', 'warehouse', 'codalmacen', $selectValues);
    }
}
