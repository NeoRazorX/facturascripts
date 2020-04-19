<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 * Controller to list the items in the Producto model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListProducto extends ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'products';
        $data['icon'] = 'fas fa-cubes';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewProducto();
        $this->createViewVariante();
        $this->createViewStock();
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewProducto(string $viewName = 'ListProducto')
    {
        $this->addView($viewName, 'Producto', 'products', 'fas fa-cubes');
        $this->addOrderBy($viewName, ['referencia'], 'reference');
        $this->addOrderBy($viewName, ['descripcion'], 'description');
        $this->addOrderBy($viewName, ['fechaalta'], 'creation-date');
        $this->addOrderBy($viewName, ['precio'], 'price');
        $this->addOrderBy($viewName, ['stockfis'], 'stock');
        $this->addOrderBy($viewName, ['actualizado'], 'update-time');
        $this->addSearchFields($viewName, ['referencia', 'descripcion', 'observaciones']);

        /// filters
        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect($viewName, 'codfabricante', 'manufacturer', 'codfabricante', $manufacturers);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect($viewName, 'codfamilia', 'family', 'codfamilia', $families);

        $taxes = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect($viewName, 'codimpuesto', 'tax', 'codimpuesto', $taxes);

        $this->addFilterCheckbox($viewName, 'nostock', 'no-stock', 'nostock');
        $this->addFilterCheckbox($viewName, 'bloqueado', 'locked', 'bloqueado');
        $this->addFilterCheckbox($viewName, 'secompra', 'for-purchase', 'secompra');
        $this->addFilterCheckbox($viewName, 'sevende', 'for-sale', 'sevende');
        $this->addFilterCheckbox($viewName, 'publico', 'public', 'publico');
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewVariante(string $viewName = 'ListVariante')
    {
        $this->addView($viewName, 'Variante', 'variants', 'fas fa-project-diagram');
        $this->addOrderBy($viewName, ['referencia'], 'reference');
        $this->addOrderBy($viewName, ['codbarras'], 'barcode');
        $this->addOrderBy($viewName, ['precio'], 'price');
        $this->addOrderBy($viewName, ['coste'], 'cost-price');
        $this->addOrderBy($viewName, ['stockfis'], 'stock');
        $this->addSearchFields($viewName, ['referencia', 'codbarras']);

        /// filters
        $attributeValues = $this->codeModel->all('atributos_valores', 'id', 'descripcion');
        $this->addFilterSelect($viewName, 'idatributovalor1', 'attribute-value-1', 'idatributovalor1', $attributeValues);
        $this->addFilterSelect($viewName, 'idatributovalor2', 'attribute-value-2', 'idatributovalor2', $attributeValues);

        /// disable buttons
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createViewStock(string $viewName = 'ListStock')
    {
        $this->addView($viewName, 'Stock', 'stock', 'fas fa-dolly');
        $this->addOrderBy($viewName, ['referencia'], 'reference');
        $this->addOrderBy($viewName, ['cantidad'], 'quantity');
        $this->addOrderBy($viewName, ['disponible'], 'available');
        $this->addOrderBy($viewName, ['reservada'], 'reserved');
        $this->addOrderBy($viewName, ['pterecibir'], 'pending-reception');
        $this->addSearchFields($viewName, ['referencia']);

        /// filters
        $selectValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'codalmacen', $selectValues);

        /// disable buttons
        $this->setSettings($viewName, 'btnNew', false);
    }
}
