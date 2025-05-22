<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

        // filtros
        $i18n = Tools::lang();
        $this->addFilterSelectWhere($viewName, 'status', [
            ['label' => $i18n->trans('only-active'), 'where' => [new DataBaseWhere('bloqueado', false)]],
            ['label' => $i18n->trans('blocked'), 'where' => [new DataBaseWhere('bloqueado', true)]],
            ['label' => $i18n->trans('public'), 'where' => [new DataBaseWhere('publico', true)]],
            ['label' => $i18n->trans('not-public'), 'where' => [new DataBaseWhere('publico', false)]],
            ['label' => $i18n->trans('all'), 'where' => []]
        ]);

        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect($viewName, 'codfabricante', 'manufacturer', 'codfabricante', $manufacturers);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect($viewName, 'codfamilia', 'family', 'codfamilia', $families);

        $types = [['code' => '', 'description' => '------']];
        foreach (ProductType::all() as $key => $value) {
            $types[] = [
                'code' => $key,
                'description' => $i18n->trans($value)
            ];
        }
        $this->addFilterSelect($viewName, 'tipo', 'type', 'tipo', $types);

        $this->addFilterNumber($viewName, 'min-price', 'price', 'precio', '>=');
        $this->addFilterNumber($viewName, 'max-price', 'price', 'precio', '<=');

        $taxes = Impuestos::codeModel();
        $this->addFilterSelect($viewName, 'codimpuesto', 'tax', 'codimpuesto', $taxes);

        $exceptions = [['code' => '', 'description' => '------']];
        foreach (RegimenIVA::allExceptions() as $key => $value) {
            $exceptions[] = [
                'code' => $key,
                'description' => $i18n->trans($value)
            ];
        }
        $this->addFilterSelect($viewName, 'excepcioniva', 'vat-exception', 'excepcioniva', $exceptions);

        $this->addFilterNumber($viewName, 'min-stock', 'stock', 'stockfis', '>=');
        $this->addFilterNumber($viewName, 'max-stock', 'stock', 'stockfis', '<=');

        $this->addFilterCheckbox($viewName, 'nostock', 'no-stock', 'nostock');
        $this->addFilterCheckbox($viewName, 'ventasinstock', 'allow-sale-without-stock', 'ventasinstock');
        $this->addFilterCheckbox($viewName, 'secompra', 'for-purchase', 'secompra');
        $this->addFilterCheckbox($viewName, 'sevende', 'for-sale', 'sevende');
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
            ->addSearchFields(['variantes.referencia', 'variantes.codbarras', 'productos.descripcion']);

        // filtros
        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect($viewName, 'codfabricante', 'manufacturer', 'codfabricante', $manufacturers);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect($viewName, 'codfamilia', 'family', 'codfamilia', $families);

        $attributes1 = $this->getAttributesForFilter(1);
        $this->addFilterSelect($viewName, 'idatributovalor1', 'attribute-value-1', 'variantes.idatributovalor1', $attributes1);

        $attributes2 = $this->getAttributesForFilter(2);
        $this->addFilterSelect($viewName, 'idatributovalor2', 'attribute-value-2', 'variantes.idatributovalor2', $attributes2);

        $attributes3 = $this->getAttributesForFilter(3);
        $this->addFilterSelect($viewName, 'idatributovalor3', 'attribute-value-3', 'variantes.idatributovalor3', $attributes3);

        $attributes4 = $this->getAttributesForFilter(4);
        $this->addFilterSelect($viewName, 'idatributovalor4', 'attribute-value-4', 'variantes.idatributovalor4', $attributes4);

        $this->addFilterNumber($viewName, 'min-price', 'price', 'variantes.precio', '>=');
        $this->addFilterNumber($viewName, 'max-price', 'price', 'variantes.precio', '<=');

        $this->addFilterNumber($viewName, 'min-stock', 'stock', 'variantes.stockfis', '>=');
        $this->addFilterNumber($viewName, 'max-stock', 'stock', 'variantes.stockfis', '<=');

        // desactivamos los botones de nuevo y eliminar
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
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
            ->addSearchFields(['stocks.referencia', 'stocks.ubicacion', 'productos.descripcion']);

        // filtros
        if (count(Almacenes::all()) > 1) {
            $warehouses = Almacenes::codeModel();
            $this->addFilterSelect($viewName, 'codalmacen', 'warehouse', 'stocks.codalmacen', $warehouses);
        } else {
            // ocultamos la columna de almacén si solo hay uno
            $this->tab($viewName)->disableColumn('warehouse');
        }

        $manufacturers = $this->codeModel->all('fabricantes', 'codfabricante', 'nombre');
        $this->addFilterSelect($viewName, 'codfabricante', 'manufacturer', 'productos.codfabricante', $manufacturers);

        $families = $this->codeModel->all('familias', 'codfamilia', 'descripcion');
        $this->addFilterSelect($viewName, 'codfamilia', 'family', 'productos.codfamilia', $families);

        $this->addFilterSelectWhere($viewName, 'type', [
            [
                'label' => Tools::lang()->trans('all'),
                'where' => []
            ],
            [
                'label' => '------',
                'where' => []
            ],
            [
                'label' => Tools::lang()->trans('under-minimums'),
                'where' => [new DataBaseWhere('stocks.disponible', 'field:stockmin', '<')]
            ],
            [
                'label' => Tools::lang()->trans('excess'),
                'where' => [new DataBaseWhere('stocks.disponible', 'field:stockmax', '>')]
            ]
        ]);

        $this->addFilterNumber($viewName, 'min-stock', 'quantity', 'cantidad', '>=');
        $this->addFilterNumber($viewName, 'max-stock', 'quantity', 'cantidad', '<=');

        $this->addFilterNumber($viewName, 'min-reserved', 'reserved', 'reservada', '>=');
        $this->addFilterNumber($viewName, 'max-reserved', 'reserved', 'reservada', '<=');

        $this->addFilterNumber($viewName, 'min-pterecibir', 'pending-reception', 'pterecibir', '>=');
        $this->addFilterNumber($viewName, 'max-pterecibir', 'pending-reception', 'pterecibir', '<=');

        $this->addFilterNumber($viewName, 'min-disponible', 'available', 'disponible', '>=');
        $this->addFilterNumber($viewName, 'max-disponible', 'available', 'disponible', '<=');

        // desactivamos los botones de nuevo y eliminar
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
    }

    protected function getAttributesForFilter(int $num): array
    {
        $values = [];

        // buscamos los atributos que usen el selector $num
        $attributeModel = new Atributo();
        $where = [new DataBaseWhere('num_selector', $num)];
        foreach ($attributeModel->all($where) as $attribute) {
            foreach ($attribute->getValores() as $value) {
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
                foreach ($attribute->getValores() as $value) {
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
