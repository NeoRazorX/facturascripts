<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Component\ComponentNumber;
use FacturaScripts\Core\Component\ComponentText;
use FacturaScripts\Core\DataSrc\Almacenes;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\ProductType;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\UIComponents\UIListController;

/**
 * Listado de productos, variantes y stock construido sobre UIListController.
 *
 * Replica ListProducto con tres pestañas:
 *  - ListProducto: productos con filtros completos
 *  - ListVariante: variantes con búsqueda y ordenación
 *  - ListStock: stock por almacén
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewListProducto extends UIListController
{
    public function getModelClassName(): string
    {
        return 'Producto';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'new-products';
        $data['icon'] = 'fa-solid fa-cubes';
        return $data;
    }

    protected function createUI(): void
    {
        $this->createViewProducto();
        $this->createViewVariante();
        $this->createViewStock();
    }

    protected function createViewProducto(string $tabName = 'ListProducto'): void
    {
        $tab = $this->addTab($tabName, 'Producto', 'products', 'fa-solid fa-cubes');

        $tab->addColumn(ComponentText::make('referencia')->setLabel('reference')->setCols(2));
        $tab->addColumn(ComponentText::make('descripcion')->setLabel('description'));
        $tab->addColumn(ComponentText::make('codfabricante')->setLabel('manufacturer')->setCols(2));
        $tab->addColumn(ComponentText::make('codfamilia')->setLabel('family')->setCols(2));
        $tab->addColumn(ComponentNumber::make('precio')->setLabel('price')->setDecimals(2)->setAlign('right')->setCols(2));
        $tab->addColumn(ComponentNumber::make('stockfis')->setLabel('stock')->setDecimals(2)->setAlign('right')->setCols(2));

        $tab->addSearchField('referencia', 'descripcion', 'observaciones');

        $tab->addOrderBy(['referencia'], 'reference', 1);
        $tab->addOrderBy(['descripcion'], 'description');
        $tab->addOrderBy(['fechaalta'], 'creation-date');
        $tab->addOrderBy(['precio'], 'price');
        $tab->addOrderBy(['stockfis'], 'stock');
        $tab->addOrderBy(['actualizado'], 'update-time');

        $manufacturers = CodeModel::all('fabricantes', 'codfabricante', 'nombre');
        $manufacturerOpts = [['value' => '', 'title' => '------']];
        foreach ($manufacturers as $c) {
            $manufacturerOpts[] = ['value' => $c->code, 'title' => $c->description];
        }
        $tab->addFilterSelect('codfabricante', 'manufacturer', 'codfabricante', $manufacturerOpts);

        $tab->addFilterAutocomplete('codfamilia', 'family', 'codfamilia', 'familias', 'codfamilia', 'descripcion');

        $types = [['value' => '', 'title' => '------']];
        foreach (ProductType::all() as $key => $value) {
            $types[] = ['value' => $key, 'title' => Tools::trans($value)];
        }
        $tab->addFilterSelect('tipo', 'type', 'tipo', $types);

        $taxOpts = [['value' => '', 'title' => '------']];
        foreach (Impuestos::codeModel() as $c) {
            $taxOpts[] = ['value' => $c->code, 'title' => $c->description];
        }
        $tab->addFilterSelect('codimpuesto', 'tax', 'codimpuesto', $taxOpts);

        $tab->addFilterCheckbox('bloqueado', 'blocked', 'bloqueado');
        $tab->addFilterCheckbox('publico', 'public', 'publico');
        $tab->addFilterCheckbox('nostock', 'no-stock', 'nostock');
        $tab->addFilterCheckbox('secompra', 'for-purchase', 'secompra');
        $tab->addFilterCheckbox('sevende', 'for-sale', 'sevende');
        $tab->addFilterCheckbox('ventasinstock', 'allow-sale-without-stock', 'ventasinstock');

        $tab->setNewUrl('NewEditProducto');
        $tab->setRowUrlCallback(fn($record) => 'NewEditProducto?code=' . urlencode($record->idproducto));
    }

    protected function createViewVariante(string $tabName = 'ListVariante'): void
    {
        $tab = $this->addTab($tabName, 'Variante', 'variants', 'fa-solid fa-project-diagram');

        $tab->addColumn(ComponentText::make('referencia')->setLabel('reference')->setCols(3));
        $tab->addColumn(ComponentText::make('codbarras')->setLabel('barcode')->setCols(3));
        $tab->addColumn(ComponentNumber::make('precio')->setLabel('price')->setDecimals(2)->setAlign('right')->setCols(2));
        $tab->addColumn(ComponentNumber::make('coste')->setLabel('cost-price')->setDecimals(2)->setAlign('right')->setCols(2));
        $tab->addColumn(ComponentNumber::make('stockfis')->setLabel('stock')->setDecimals(2)->setAlign('right')->setCols(2));

        $tab->addSearchField('referencia', 'codbarras');

        $tab->addOrderBy(['referencia'], 'reference', 1);
        $tab->addOrderBy(['codbarras'], 'barcode');
        $tab->addOrderBy(['precio'], 'price');
        $tab->addOrderBy(['coste'], 'cost-price');
        $tab->addOrderBy(['stockfis'], 'stock');

        $tab->setRowUrlCallback(fn($record) => 'NewEditProducto?code=' . urlencode($record->idproducto));
    }

    protected function createViewStock(string $tabName = 'ListStock'): void
    {
        $tab = $this->addTab($tabName, 'Stock', 'stock', 'fa-solid fa-dolly');

        $tab->addColumn(ComponentText::make('referencia')->setLabel('reference')->setCols(3));
        $tab->addColumn(ComponentText::make('codalmacen')->setLabel('warehouse')->setCols(2));
        $tab->addColumn(ComponentNumber::make('cantidad')->setLabel('quantity')->setDecimals(2)->setAlign('right')->setCols(2));
        $tab->addColumn(ComponentNumber::make('disponible')->setLabel('available')->setDecimals(2)->setAlign('right')->setCols(2));
        $tab->addColumn(ComponentNumber::make('reservada')->setLabel('reserved')->setDecimals(2)->setAlign('right')->setCols(2));
        $tab->addColumn(ComponentNumber::make('pterecibir')->setLabel('pending-reception')->setDecimals(2)->setAlign('right')->setCols(2));

        $tab->addSearchField('referencia', 'ubicacion');

        $tab->addOrderBy(['referencia'], 'reference', 1);
        $tab->addOrderBy(['cantidad'], 'quantity');
        $tab->addOrderBy(['disponible'], 'available');
        $tab->addOrderBy(['reservada'], 'reserved');
        $tab->addOrderBy(['pterecibir'], 'pending-reception');

        if (count(Almacenes::all()) > 1) {
            $warehouseOpts = [['value' => '', 'title' => '------']];
            foreach (Almacenes::codeModel() as $c) {
                $warehouseOpts[] = ['value' => $c->code, 'title' => $c->description];
            }
            $tab->addFilterSelect('codalmacen', 'warehouse', 'codalmacen', $warehouseOpts);
        }
    }
}
