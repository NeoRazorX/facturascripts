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
use FacturaScripts\Core\Model\Atributo;
use FacturaScripts\Core\UIComponents\UIListController;

/**
 * Listado de atributos y sus valores construido sobre UIListController.
 *
 * Replica ListAtributo con dos pestañas:
 *  - ListAtributo: atributos de artículo
 *  - ListAtributoValor: valores de atributo con filtro por atributo
 *
 * @author Abderrahim Darghal Belkacemi <abdedarghal111@gmail.com>
 */
class NewListAtributo extends UIListController
{
    public function getModelClassName(): string
    {
        return 'Atributo';
    }

    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'new-atributos';
        $data['icon'] = 'fa-solid fa-tshirt';
        return $data;
    }

    protected function createUI(): void
    {
        $this->createViewsAttributes();
        $this->createViewsValues();
    }

    protected function createViewsAttributes(string $tabName = 'ListAtributo'): void
    {
        $tab = $this->addTab($tabName, 'Atributo', 'attributes', 'fa-solid fa-tshirt');

        $tab->addColumn(ComponentText::make('codatributo')->setLabel('code')->setCols(2));
        $tab->addColumn(ComponentText::make('nombre')->setLabel('name'));
        $tab->addColumn(ComponentNumber::make('num_selector')->setLabel('selector-number')->setDecimals(0)->setCols(2));

        $tab->addSearchField('codatributo', 'nombre');

        $tab->addOrderBy(['codatributo'], 'code', 1);
        $tab->addOrderBy(['nombre'], 'name');

        $tab->setNewUrl('NewEditAtributo');
        $tab->setRowUrlCallback(fn($record) => 'NewEditAtributo?code=' . urlencode($record->codatributo));
    }

    protected function createViewsValues(string $tabName = 'ListAtributoValor'): void
    {
        $tab = $this->addTab($tabName, 'AtributoValor', 'values', 'fa-solid fa-list');

        $tab->addColumn(ComponentText::make('codatributo')->setLabel('attribute')->setCols(2));
        $tab->addColumn(ComponentText::make('valor')->setLabel('value'));
        $tab->addColumn(ComponentNumber::make('orden')->setLabel('sort')->setDecimals(0)->setAlign('right')->setCols(2));

        $tab->addSearchField('valor', 'codatributo');

        $tab->addOrderBy(['codatributo', 'orden', 'valor'], 'sort', 1);
        $tab->addOrderBy(['valor'], 'value');

        $atributos = (new Atributo())->all([], ['nombre' => 'ASC']);
        $options = array_map(fn($a) => ['value' => $a->codatributo, 'title' => $a->nombre], $atributos);
        $tab->addFilterSelect('codatributo', 'attribute', 'codatributo', $options);

        $tab->setRowUrlCallback(fn($record) => 'NewEditAtributo?code=' . urlencode($record->codatributo));
    }
}
