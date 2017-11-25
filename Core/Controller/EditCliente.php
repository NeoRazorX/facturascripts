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
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\CuentaBancoCliente;
use FacturaScripts\Core\Model\DireccionCliente;

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
        $this->addEditView(Cliente::class, 'EditCliente', 'customer');
        $this->addEditListView(DireccionCliente::class, 'EditDireccionCliente', 'addresses', 'fa-road');
        $this->addEditListView(CuentaBancoCliente::class, 'EditCuentaBancoCliente', 'customer-banking-accounts',
            'fa-bank');
        $this->addListView(Cliente::class, 'ListCliente', 'same-group');
    }

    /**
     * Load view data procedure
     *
     * @param string $keyView
     * @param ExtendedController\EditView $view
     */
    protected function loadData($keyView, $view)
    {
        switch ($keyView) {
            case 'EditCliente':
                $value = $this->request->get('code');
                $view->loadData($value);
                break;

            case 'EditDireccionCliente':
                $where = [new DataBaseWhere('codcliente', $this->getViewModelValue('EditCliente', 'codcliente'))];
                $view->loadData($where);
                break;

            case 'EditCuentaBancoCliente':
                $where = [new DataBaseWhere('codcliente', $this->request->get('code'))];
                $view->loadData($where);
                break;

            case 'ListCliente':
                $codgroup = $this->getViewModelValue('EditCliente', 'codgrupo');

                if (!empty($codgroup)) {
                    $where = [new DataBaseWhere('codgrupo', $codgroup)];
                    $view->loadData($where);
                }
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
        $where[] = new DataBaseWhere('codcliente', $this->getViewModelValue('EditCliente', 'codcliente'));
        $where[] = new DataBaseWhere('ptefactura', true);

        $totalModel = Model\TotalModel::all('albaranescli', $where, ['total' => 'SUM(total)'])[0];
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
        $where[] = new DataBaseWhere('codcliente', $this->getViewModelValue('EditCliente', 'codcliente'));
        $where[] = new DataBaseWhere('estado', 'Pagado', '<>');

        $totalModel = Model\TotalModel::all('reciboscli', $where, ['total' => 'SUM(importe)'])[0];
        return $this->divisaTools->format($totalModel->totals['total'], 2);
    }
}
