<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\DataSrc\Impuestos;
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

        // filters
        $i18n = $this->toolBox()->i18n();
        $this->addFilterSelectWhere($viewName, 'status', [
            ['label' => $i18n->trans('only-active'), 'where' => [new DataBaseWhere('bloqueado', false)]],
            ['label' => $i18n->trans('blocked'), 'where' => [new DataBaseWhere('bloqueado', true)]],
            ['label' => $i18n->trans('public'), 'where' => [new DataBaseWhere('publico', true)]],
            ['label' => $i18n->trans('all'), 'where' => []]
        ]);

        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect($viewName, 'codfabricante', 'manufacturer', 'codfabricante', $manufacturers);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect($viewName, 'codfamilia', 'family', 'codfamilia', $families);

        $taxes = Impuestos::codeModel();
        $this->addFilterSelect($viewName, 'codimpuesto', 'tax', 'codimpuesto', $taxes);

        $this->addFilterNumber($viewName, 'min-price', 'price', 'precio', '<=');
        $this->addFilterNumber($viewName, 'max-price', 'price', 'precio', '>=');
        $this->addFilterNumber($viewName, 'min-stock', 'stock', 'stockfis', '<=');
        $this->addFilterNumber($viewName, 'max-stock', 'stock', 'stockfis', '>=');

        $this->addFilterCheckbox($viewName, 'nostock', 'no-stock', 'nostock');
        $this->addFilterCheckbox($viewName, 'ventasinstock', 'allow-sale-without-stock', 'ventasinstock');
        $this->addFilterCheckbox($viewName, 'secompra', 'for-purchase', 'secompra');
        $this->addFilterCheckbox($viewName, 'sevende', 'for-sale', 'sevende');
    }

    /**
     * @param string $viewName
     */
    protected function createViewVariante(string $viewName = 'ListVariante')
    {
        $this->addView($viewName, 'Join\VarianteProducto', 'variants', 'fas fa-project-diagram');
        $this->addOrderBy($viewName, ['variantes.referencia'], 'reference');
        $this->addOrderBy($viewName, ['variantes.codbarras'], 'barcode');
        $this->addOrderBy($viewName, ['variantes.precio'], 'price');
        $this->addOrderBy($viewName, ['variantes.coste'], 'cost-price');
        $this->addOrderBy($viewName, ['variantes.stockfis'], 'stock');
        $this->addOrderBy($viewName, ['productos.descripcion', 'variantes.referencia'], 'product');
        $this->addSearchFields($viewName, ['variantes.referencia', 'variantes.codbarras', 'productos.descripcion']);

        // filters
        $attributes = $this->codeModel->all('atributos_valores', 'id', 'descripcion');
        $this->addFilterSelect($viewName, 'idatributovalor1', 'attribute-value-1', 'variantes.idatributovalor1', $attributes);
        $this->addFilterSelect($viewName, 'idatributovalor2', 'attribute-value-2', 'variantes.idatributovalor2', $attributes);
        $this->addFilterSelect($viewName, 'idatributovalor3', 'attribute-value-3', 'variantes.idatributovalor3', $attributes);
        $this->addFilterSelect($viewName, 'idatributovalor4', 'attribute-value-4', 'variantes.idatributovalor4', $attributes);
        $this->addFilterNumber($viewName, 'min-price', 'price', 'variantes.precio', '<=');
        $this->addFilterNumber($viewName, 'max-price', 'price', 'variantes.precio', '>=');
        $this->addFilterNumber($viewName, 'min-stock', 'stock', 'variantes.stockfis', '<=');
        $this->addFilterNumber($viewName, 'max-stock', 'stock', 'variantes.stockfis', '>=');

        // disable buttons
        $this->setSettings($viewName, 'btnNew', false);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createViewStock(string $viewName = 'ListStock')
    {
        $this->addView($viewName, 'Join\StockProducto', 'stock', 'fas fa-dolly');
        $this->addOrderBy($viewName, ['stocks.referencia'], 'reference');
        $this->addOrderBy($viewName, ['stocks.cantidad'], 'quantity');
        $this->addOrderBy($viewName, ['stocks.disponible'], 'available');
        $this->addOrderBy($viewName, ['stocks.reservada'], 'reserved');
        $this->addOrderBy($viewName, ['stocks.pterecibir'], 'pending-reception');
        $this->addOrderBy($viewName, ['productos.descripcion', 'stocks.referencia'], 'product');
        $this->addSearchFields($viewName, ['stocks.referencia', 'productos.descripcion']);

        // filters
        $warehouses = Almacenes::codeModel();
        $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'stocks.codalmacen', $warehouses);

        $this->addFilterSelectWhere($viewName, 'type', [
            [
                'label' => $this->toolBox()->i18n()->trans('all'),
                'where' => []
            ],
            [
                'label' => $this->toolBox()->i18n()->trans('under-minimums'),
                'where' => [new DataBaseWhere('stocks.disponible', 'field:stockmin', '<')]
            ],
            [
                'label' => $this->toolBox()->i18n()->trans('excess'),
                'where' => [new DataBaseWhere('stocks.disponible', 'field:stockmax', '>')]
            ]
        ]);

        $this->addFilterNumber($viewName, 'max-stock', 'quantity', 'cantidad', '>=');
        $this->addFilterNumber($viewName, 'min-stock', 'quantity', 'cantidad', '<=');

        // disable buttons
        $this->setSettings($viewName, 'btnNew', false);
    }
}
