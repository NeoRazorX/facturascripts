<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * @param string $name
     */
    protected function createViewProducto($name = 'ListProducto')
    {
        $this->addView($name, 'Producto', 'products', 'fas fa-cubes');
        $this->addSearchFields($name, ['referencia', 'descripcion', 'observaciones']);
        $this->addOrderBy($name, ['referencia'], 'reference');
        $this->addOrderBy($name, ['descripcion'], 'description');
        $this->addOrderBy($name, ['precio'], 'price');
        $this->addOrderBy($name, ['stockfis'], 'stock');
        $this->addOrderBy($name, ['actualizado'], 'update-time');

        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect($name, 'codfabricante', 'manufacturer', 'codfabricante', $manufacturers);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect($name, 'codfamilia', 'family', 'codfamilia', $families);

        $taxes = $this->codeModel->all('impuestos', 'codimpuesto', 'descripcion');
        $this->addFilterSelect($name, 'codimpuesto', 'tax', 'codimpuesto', $taxes);

        $this->addFilterCheckbox($name, 'nostock', 'no-stock', 'nostock');
        $this->addFilterCheckbox($name, 'bloqueado', 'locked', 'bloqueado');
        $this->addFilterCheckbox($name, 'secompra', 'for-purchase', 'secompra');
        $this->addFilterCheckbox($name, 'sevende', 'for-sale', 'sevende');
        $this->addFilterCheckbox($name, 'publico', 'public', 'publico');
    }

    /**
     * 
     * @param string $name
     */
    protected function createViewVariante($name = 'ListVariante')
    {
        $this->addView($name, 'Variante', 'variants', 'fas fa-project-diagram');
        $this->addSearchFields($name, ['referencia', 'codbarras']);
        $this->addOrderBy($name, ['referencia'], 'reference');
        $this->addOrderBy($name, ['codbarras'], 'barcode');
        $this->addOrderBy($name, ['precio'], 'price');
        $this->addOrderBy($name, ['coste'], 'cost-price');
        $this->addOrderBy($name, ['stockfis'], 'stock');

        $attributeValues = $this->codeModel->all('atributos_valores', 'id', 'descripcion');
        $this->addFilterSelect($name, 'idatributovalor1', 'attribute-value-1', 'idatributovalor1', $attributeValues);
        $this->addFilterSelect($name, 'idatributovalor2', 'attribute-value-2', 'idatributovalor2', $attributeValues);

        /// disable new button
        $this->setSettings($name, 'btnNew', false);
    }

    /**
     * 
     * @param string $name
     */
    protected function createViewStock($name = 'ListStock')
    {
        $this->addView($name, 'Stock', 'stock', 'fas fa-tasks');
        $this->addSearchFields($name, ['referencia']);
        $this->addOrderBy($name, ['referencia'], 'reference');
        $this->addOrderBy($name, ['cantidad'], 'quantity');
        $this->addOrderBy($name, ['disponible'], 'available');
        $this->addOrderBy($name, ['reservada'], 'reserved');
        $this->addOrderBy($name, ['pterecibir'], 'pending-reception');

        $selectValues = $this->codeModel->all('almacenes', 'codalmacen', 'nombre');
        $this->addFilterSelect($name, 'codalmacen', 'warehouse', 'codalmacen', $selectValues);

        /// disable new button
        $this->setSettings($name, 'btnNew', false);
    }
}
