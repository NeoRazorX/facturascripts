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

use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Lib\ExtendedController\ListController;

/**
 *  Controller to list the items in the Almacen model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 */
class ListAlmacen extends ListController
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'warehouse';
        $data['title'] = 'warehouses';
        $data['icon'] = 'fa-solid fa-warehouse';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewWarehouse();
    }

    protected function createViewWarehouse(string $viewName = 'ListAlmacen'): void
    {
        $this->addView($viewName, 'Almacen', 'warehouses', 'fa-solid fa-warehouse')
            ->addSearchFields(['apartado', 'ciudad', 'codalmacen', 'codpostal', 'direccion', 'nombre', 'provincia'])
            ->addOrderBy(['codalmacen'], 'code')
            ->addOrderBy(['nombre'], 'name', 1);

        // Filters
        $this->addFilterSelect($viewName, 'idempresa', 'company', 'idempresa', Empresas::codeModel());
    }
}
