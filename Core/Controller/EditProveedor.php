<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Model;

/**
 * Controller to edit a single item from the Proveedor model
 *
 * @author Nazca Networks <comercial@nazcanetworks.com>
 */
class EditProveedor extends ExtendedController\PanelController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView(Model\Proveedor::class, 'EditProveedor', 'supplier');
        $this->addEditListView(Model\DireccionProveedor::class, 'EditDireccionProveedor', 'addresses', 'fa-road');
        $this->addEditListView(Model\CuentaBancoProveedor::class, 'EditCuentaBancoProveedor', 'bank-accounts', 'fa-university');
        $this->addEditListView(Model\ArticuloProveedor::class, 'EditProveedorArticulo', 'products', 'fa-cubes');
        $this->addListView(Model\FacturaProveedor::class, 'ListFacturaProveedor', 'invoices', 'fa-files-o');
        $this->addListView(Model\AlbaranProveedor::class, 'ListAlbaranProveedor', 'delivery-notes', 'fa-files-o');
        $this->addListView(Model\PedidoProveedor::class, 'ListPedidoProveedor', 'orders', 'fa-files-o');
    }

    /**
     * Load view data
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        $codproveedor = $this->request->get('code');

        switch ($keyView) {
            case 'EditProveedor':
                $view->loadData($codproveedor);
                break;

            case 'EditDireccionProveedor':
            case 'EditCuentaBancoProveedor':
            case 'EditProveedorArticulo':
            case 'ListFacturaProveedor':
            case 'ListAlbaranProveedor':
            case 'ListPedidoProveedor':
            case 'ListPresupuestoProveedor':
                $where = [new DataBaseWhere('codproveedor', $codproveedor)];
                $view->loadData($where);
                break;
        }
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'supplier';
        $pagedata['icon'] = 'fa-users';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }
}
