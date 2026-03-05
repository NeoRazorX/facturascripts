<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\RegimenIVA;
use FacturaScripts\Dinamic\Model\Atributo;

/**
 * Controller to list the items in the Producto model
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class ListProducto extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'products';
        $data['icon'] = 'fa-solid fa-cubes';
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

    protected function createViewProducto(string $viewName = 'ListProducto'): void
    {
        $this->addView($viewName, 'Producto', 'products', 'fa-solid fa-cubes')
            ->addOrderBy(['referencia'], 'reference')
            ->addOrderBy(['descripcion'], 'description')
            ->addOrderBy(['fechaalta'], 'creation-date')
            ->addOrderBy(['precio'], 'price')
            ->addOrderBy(['stockfis'], 'stock')
            ->addOrderBy(['actualizado'], 'update-time')
            ->addSearchFields(['referencia', 'descripcion', 'observaciones']);

        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $types = [['code' => '', 'description' => '------']];
        foreach (ProductType::all() as $key => $value) {
            $types[] = [
                'code' => $key,
                'description' => Tools::trans($value)
            ];
        }
        $taxes = Impuestos::codeModel();
        $exceptions = [['code' => '', 'description' => '------']];
        foreach (RegimenIVA::allExceptions() as $key => $value) {
            $exceptions[] = [
                'code' => $key,
                'description' => Tools::trans($value)
            ];
        }

        // filtros
        $this->listView($viewName)
            ->addFilterSelectWhere('status', [
                ['label' => Tools::trans('only-active'), 'where' => [new DataBaseWhere('bloqueado', false)]],
                ['label' => '------', 'where' => []],
                ['label' => Tools::trans('blocked'), 'where' => [new DataBaseWhere('bloqueado', true)]],
                ['label' => Tools::trans('public'), 'where' => [new DataBaseWhere('publico', true)]],
                ['label' => Tools::trans('not-public'), 'where' => [new DataBaseWhere('publico', false)]],
                ['label' => Tools::trans('all'), 'where' => []]
            ])
            ->addFilterSelect('codfabricante', 'manufacturer', 'codfabricante', $manufacturers)
            ->addFilterSelect('codfamilia', 'family', 'codfamilia', $families)
            ->addFilterSelect('tipo', 'type', 'tipo', $types)
            ->addFilterNumber('min-price', 'price', 'precio', '>=')
            ->addFilterNumber('max-price', 'price', 'precio', '<=')
            ->addFilterSelect('codimpuesto', 'tax', 'codimpuesto', $taxes)
            ->addFilterSelect('excepcioniva', 'vat-exception', 'excepcioniva', $exceptions)
            ->addFilterNumber('min-stock', 'stock', 'stockfis', '>=')
            ->addFilterNumber('max-stock', 'stock', 'stockfis', '<=')
            ->addFilterCheckbox('nostock', 'no-stock', 'nostock')
            ->addFilterCheckbox('ventasinstock', 'allow-sale-without-stock', 'ventasinstock')
            ->addFilterCheckbox('secompra', 'for-purchase', 'secompra')
            ->addFilterCheckbox('sevende', 'for-sale', 'sevende')
            ->addFilterTree('descendientede', 'descendant-of', 'codfamilia', 'familias', 'madre', 'codfamilia', 'descripcion');
    }

    protected function createViewVariante(string $viewName = 'ListVariante'): void
    {
        $this->addView($viewName, 'Join\VarianteProducto', 'variants', 'fa-solid fa-project-diagram')
            ->addOrderBy(['variantes.referencia'], 'reference')
            ->addOrderBy(['variantes.codbarras'], 'barcode')
            ->addOrderBy(['variantes.precio'], 'price')
            ->addOrderBy(['variantes.coste'], 'cost-price')
            ->addOrderBy(['variantes.stockfis'], 'stock')
            ->addOrderBy(['productos.descripcion', 'variantes.referencia'], 'product')
            ->addSearchFields(['variantes.referencia', 'variantes.codbarras', 'productos.descripcion'])
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false);

        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $attributes1 = $this->getAttributesForFilter(1);
        $attributes2 = $this->getAttributesForFilter(2);
        $attributes3 = $this->getAttributesForFilter(3);
        $attributes4 = $this->getAttributesForFilter(4);

        // filtros
        $this->listView($viewName)
            ->addFilterSelect('codfabricante', 'manufacturer', 'codfabricante', $manufacturers)
            ->addFilterSelect('codfamilia', 'family', 'codfamilia', $families)
            ->addFilterSelect('idatributovalor1', 'attribute-value-1', 'variantes.idatributovalor1', $attributes1)
            ->addFilterSelect('idatributovalor2', 'attribute-value-2', 'variantes.idatributovalor2', $attributes2)
            ->addFilterSelect('idatributovalor3', 'attribute-value-3', 'variantes.idatributovalor3', $attributes3)
            ->addFilterSelect('idatributovalor4', 'attribute-value-4', 'variantes.idatributovalor4', $attributes4)
            ->addFilterNumber('min-price', 'price', 'variantes.precio', '>=')
            ->addFilterNumber('max-price', 'price', 'variantes.precio', '<=')
            ->addFilterNumber('min-stock', 'stock', 'variantes.stockfis', '>=')
            ->addFilterNumber('max-stock', 'stock', 'variantes.stockfis', '<=');
    }

    protected function createViewStock(string $viewName = 'ListStock'): void
    {
        $this->addView($viewName, 'Join\StockProducto', 'stock', 'fa-solid fa-dolly')
            ->addOrderBy(['stocks.referencia'], 'reference')
            ->addOrderBy(['stocks.cantidad'], 'quantity')
            ->addOrderBy(['stocks.disponible'], 'available')
            ->addOrderBy(['stocks.reservada'], 'reserved')
            ->addOrderBy(['stocks.pterecibir'], 'pending-reception')
            ->addOrderBy(['productos.descripcion', 'stocks.referencia'], 'product')
            ->addSearchFields(['stocks.referencia', 'stocks.ubicacion', 'productos.descripcion'])
            ->setSettings('btnDelete', false)
            ->setSettings('btnNew', false);

        // filtros
        if (count(Almacenes::all()) > 1) {
            $warehouses = Almacenes::codeModel();
            $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'stocks.codalmacen', $warehouses);
        } else {
            // ocultamos la columna de almacén si solo hay uno
            $this->tab($viewName)->disableColumn('warehouse');
        }

        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');

        $this->listView($viewName)
            ->addFilterSelectWhere('type', [
                [
                    'label' => Tools::trans('all'),
                    'where' => []
                ],
                [
                    'label' => '------',
                    'where' => []
                ],
                [
                    'label' => Tools::trans('under-minimums'),
                    'where' => [new DataBaseWhere('stocks.disponible', 'field:stockmin', '<', 'AND', true)]
                ],
                [
                    'label' => Tools::trans('excess'),
                    'where' => [new DataBaseWhere('stocks.disponible', 'field:stockmax', '>', 'AND', true)]
                ]
            ])
            ->addFilterSelect('codfabricante', 'manufacturer', 'productos.codfabricante', $manufacturers)
            ->addFilterSelect('codfamilia', 'family', 'productos.codfamilia', $families)
            ->addFilterNumber('min-stock', 'quantity', 'cantidad', '>=')
            ->addFilterNumber('max-stock', 'quantity', 'cantidad', '<=')
            ->addFilterNumber('min-reserved', 'reserved', 'reservada', '>=')
            ->addFilterNumber('max-reserved', 'reserved', 'reservada', '<=')
            ->addFilterNumber('min-pterecibir', 'pending-reception', 'pterecibir', '>=')
            ->addFilterNumber('max-pterecibir', 'pending-reception', 'pterecibir', '<=')
            ->addFilterNumber('min-disponible', 'available', 'disponible', '>=')
            ->addFilterNumber('max-disponible', 'available', 'disponible', '<=');
    }

    protected function getAttributesForFilter(int $num): array
    {
        $values = [];

        // buscamos los atributos que usen el selector $num
        $attributeModel = new Atributo();
        $where = [new DataBaseWhere('num_selector', $num)];
        foreach ($attributeModel->all($where) as $attribute) {
            foreach ($attribute->getValues() as $value) {
                $values[] = new CodeModel([
                    'code' => $value->id,
                    'description' => $value->descripcion,
                ]);
            }
        }

        // si no hay ninguno, buscamos los que tenga el selector 0
        if (empty($values)) {
            $where = [new DataBaseWhere('num_selector', 0)];
            foreach ($attributeModel->all($where) as $attribute) {
                foreach ($attribute->getValues() as $value) {
                    $values[] = new CodeModel([
                        'code' => $value->id,
                        'description' => $value->descripcion,
                    ]);
                }
            }
        }

        // añadimos el valor vacío al principio
        array_unshift($values, new CodeModel([
            'code' => '',
            'description' => '------'
        ]));

        return $values;
    }
}
