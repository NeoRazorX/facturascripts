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
use FacturaScripts\Core\Model;

/**
 * Controller to edit a single item from the Cliente model
 *
 * @author Artex Trading sa <jcuello@artextrading.com>
 */
class EditCliente extends ExtendedController\PanelController
{

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addEditView('FacturaScripts\Core\Model\Cliente', 'EditCliente', 'customer');
        $this->addEditListView('FacturaScripts\Core\Model\DireccionCliente', 'EditDireccionCliente', 'addresses', 'fa-road');
        $this->addEditListView('FacturaScripts\Core\Model\CuentaBancoCliente', 'EditCuentaBancoCliente', 'customer-banking-accounts', 'fa-bank');
        $this->addListView('FacturaScripts\Core\Model\Cliente', 'ListCliente', 'same-group', 'fa-users');
        $this->addListView('FacturaScripts\Core\Model\FacturaCliente', 'ListFacturaCliente', 'invoices', 'fa-files-o');
        $this->addListView('FacturaScripts\Core\Model\AlbaranCliente', 'ListAlbaranCliente', 'delivery-notes', 'fa-files-o');
        $this->addListView('FacturaScripts\Core\Model\PedidoCliente', 'ListPedidoCliente', 'orders', 'fa-files-o');
        $this->addListView('FacturaScripts\Core\Model\PresupuestoCliente', 'ListPresupuestoCliente', 'estimations', 'fa-files-o');
    }

    /**
     * Load view data procedure
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        $codcliente = $this->request->get('code');
        $codgrupo = $this->getViewModelValue('EditCliente', 'codgrupo');

        switch ($keyView) {
            case 'EditCliente':
                $view->loadData($codcliente);
                break;

            case 'ListCliente':
                if (!empty($codgrupo)) {
                    $where = [new DataBase\DataBaseWhere('codgrupo', $codgrupo)];
                    $view->loadData($where);
                }
                break;

            case 'EditDireccionCliente':
            case 'EditCuentaBancoCliente':
            case 'ListFacturaCliente':
            case 'ListAlbaranCliente':
            case 'ListPedidoCliente':
            case 'ListPresupuestoCliente':
                $where = [new DataBase\DataBaseWhere('codcliente', $codcliente)];
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
        $pagedata['title'] = 'customer';
        $pagedata['icon'] = 'fa-users';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Devuelve la suma del total de albaranes del cliente.
     *
     * @param ExtendedController\EditView $view
     *
     * @return string
     */
    public function calcClientDeliveryNotes($view)
    {
        $where = [];
        $where[] = new DataBase\DataBaseWhere('codcliente', $this->getViewModelValue('EditCliente', 'codcliente'));
        $where[] = new DataBase\DataBaseWhere('ptefactura', TRUE);

        $totalModel = Model\TotalModel::all('albaranescli', $where, ['total' => 'SUM(total)'], '')[0];
        return $this->divisaTools->format($totalModel->totals['total'], 2);
    }

    /**
     * Devuelve la suma del total de facturas pendientes del cliente.
     *
     * @param ExtendedController\EditView $view
     *
     * @return string
     */
    public function calcClientInvoicePending($view)
    {
        $where = [];
        $where[] = new DataBase\DataBaseWhere('codcliente', $this->getViewModelValue('EditCliente', 'codcliente'));
        $where[] = new DataBase\DataBaseWhere('estado', 'Pagado', '<>');

        $totalModel = Model\TotalModel::all('reciboscli', $where, ['total' => 'SUM(importe)'], '')[0];
        return $this->divisaTools->format($totalModel->totals['total'], 2);
    }
}
