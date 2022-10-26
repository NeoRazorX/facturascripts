<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Lib\ExtendedController\ListBusinessDocument;

/**
 *  Controller to list the items in the AlbaranProveedor model
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Cristo M. Estévez Hernández   <cristom.estevez@gmail.com>
 */
class ListAlbaranProveedor extends ListBusinessDocument
{
    public function getPageData(): array
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'delivery-notes';
        $data['icon'] = 'fas fa-dolly-flatbed';
        return $data;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->createViewsAlbaranes();

        if ($this->permissions->onlyOwnerData === false) {
            $this->createViewLines('ListLineaAlbaranProveedor', 'LineaAlbaranProveedor');
        }
    }

    protected function createViewsAlbaranes(string $viewName = 'ListAlbaranProveedor')
    {
        $this->createViewPurchases($viewName, 'AlbaranProveedor', 'delivery-notes');

        // añadimos botones
        $this->addButtonGroupDocument($viewName);
        $this->addButtonApproveDocument($viewName);
    }
}
