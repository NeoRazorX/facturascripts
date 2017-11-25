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

use FacturaScripts\Core\Base\ExtendedController;
use FacturaScripts\Core\Base\DataBase;

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
        $this->addEditView('FacturaScripts\Core\Model\Proveedor', 'EditProveedor', 'supplier');
        $this->addEditListView('FacturaScripts\Core\Model\DireccionProveedor', 'EditDireccionProveedor', 'addresses', 'fa-road');
        $this->addEditListView('FacturaScripts\Core\Model\CuentaBancoProveedor', 'EditCuentaBancoProveedor', 'bank-accounts', 'fa-university');
        $this->addEditListView('FacturaScripts\Core\Model\ArticuloProveedor', 'EditProveedorArticulo', 'products', 'fa-cubes');

        // TODO: How we indicate that we want apply a filter by codproveedor to this list?
        $this->addListView('FacturaScripts\Core\Model\FacturaProveedor', 'ListFacturaProveedor', 'invoices');
        $this->addListView('FacturaScripts\Core\Model\AlbaranProveedor', 'ListAlbaranProveedor', 'delivery-notes');
        $this->addListView('FacturaScripts\Core\Model\PedidoProveedor', 'ListPedidoProveedor', 'orders');
    }

    /**
     * Load view data
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditProveedor':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'EditDireccionProveedor':
                $where = [new DataBase\DataBaseWhere('codproveedor', $this->getViewModelValue('EditProveedor', 'codproveedor'))];
                $view->loadData($where);
                break;

            case 'EditCuentaBancoProveedor':
                $where = [new DataBase\DataBaseWhere('codproveedor', $this->getViewModelValue('EditProveedor', 'codproveedor'))];
                $view->loadData($where);
                break;

            case 'EditProveedorArticulo':
                $where = [new DataBase\DataBaseWhere('codproveedor', $this->getViewModelValue('EditProveedor', 'codproveedor'))];
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
