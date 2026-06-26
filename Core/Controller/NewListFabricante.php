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
use FacturaScripts\Core\UIComponents\UIListController;

/**
 * Listado de fabricantes construido sobre UIListController.
 *
 * Replica ListFabricante mostrando código, nombre y número de productos.
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewListFabricante extends UIListController
{
    public function getModelClassName(): string
    {
        return 'Fabricante';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'manufacturers';
        $data['icon'] = 'fa-solid fa-industry';
        return $data;
    }

    protected function createUI(): void
    {
        $this->createViewsManufacturers();
    }

    protected function createViewsManufacturers(string $tabName = 'ListFabricante'): void
    {
        $tab = $this->addTab($tabName, 'Fabricante', 'manufacturers', 'fa-solid fa-industry');

        $tab->addColumn(ComponentText::make('codfabricante')->setLabel('code')->setCols(2));
        $tab->addColumn(ComponentText::make('nombre')->setLabel('name'));
        $tab->addColumn(ComponentNumber::make('numproductos')->setLabel('products')->setDecimals(0)->setAlign('right')->setCols(2));

        $tab->addSearchField('codfabricante', 'nombre');

        $tab->addOrderBy(['codfabricante'], 'code', 1);
        $tab->addOrderBy(['nombre'], 'name');
        $tab->addOrderBy(['numproductos'], 'products');

        $tab->setNewUrl('NewEditFabricante');
        $tab->setRowUrlCallback(fn($record) => 'NewEditFabricante?code=' . urlencode($record->codfabricante));
    }
}
