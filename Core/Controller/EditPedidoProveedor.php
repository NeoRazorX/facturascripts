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

use FacturaScripts\Dinamic\Lib\ExtendedController\PurchaseDocumentController;

/**
 * Controller to edit a single item from the PedidoProveedor model
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Luis Miguel Pérez    <luismi@pcrednet.com>
 */
class EditPedidoProveedor extends PurchaseDocumentController
{

    /**
     * Return the document class name.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'PedidoProveedor';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'purchases';
        $data['title'] = 'order';
        $data['icon'] = 'fas fa-file-invoice';
        return $data;
    }
}
