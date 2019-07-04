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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\DivisaTools;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\ComercialContactController;
use FacturaScripts\Dinamic\Model\TotalModel;

/**
 * Controller to edit a single item from the Agente model
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * @author Artex Trading sa    <jcuello@artextrading.com>
 * @author Raul
 */
class EditAgente extends ComercialContactController
{

    /**
     * Returns the sum of the agent's total outstanding invoices.
     *
     * @return string
     */
    public function calcAgentInvoicePending()
    {
        $where = [
            new DataBaseWhere('codagente', $this->getViewModelValue($this->getMainViewName(), 'codagente')),
            new DataBaseWhere('pagada', false)
        ];

        $totalModel = TotalModel::all('facturascli', $where, ['total' => 'SUM(total)'], '')[0];
        $divisaTools = new DivisaTools();
        return $divisaTools->format($totalModel->totals['total'], 2);
    }

    /**
     * Returns the class name of the model to use.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'Agente';
    }

    /**
     * Returns basic page attributes.
     *
     * @return array
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $data['menu'] = 'sales';
        $data['title'] = 'agent';
        $data['icon'] = 'fas fa-user-tie';
        return $data;
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createCommissionsView($viewName = 'ListComision')
    {
        $this->addListView($viewName, 'Comision', 'commissions', 'fas fa-percentage');
        $this->views[$viewName]->addOrderBy(['prioridad'], 'priority', 2);

        /// disable columns
        $this->views[$viewName]->disableColumn('agent', true);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createContactView($viewName = 'EditContacto')
    {
        $this->addEditView($viewName, 'Contacto', 'contact', 'fa fa-address-book');

        /// disable columns
        $this->views[$viewName]->disableColumn('agent', true);
        $this->views[$viewName]->disableColumn('company', true);
        $this->views[$viewName]->disableColumn('fiscal-id', true);
        $this->views[$viewName]->disableColumn('fiscal-number', true);
        $this->views[$viewName]->disableColumn('position', true);
    }

    /**
     * 
     * @param string $viewName
     */
    protected function createSettlementView($viewName = 'ListLiquidacionComision')
    {
        $this->addListView($viewName, 'LiquidacionComision', 'settlements', 'fas fa-chalkboard-teacher');
    }

    /**
     * Load Views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->createContactView();
        $this->createCommissionsView();
        $this->createSettlementView();
        $this->createCustomerListView('ListFacturaCliente', 'FacturaCliente', 'invoices');
        $this->createCustomerListView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes');
        $this->createCustomerListView('ListPedidoCliente', 'PedidoCliente', 'orders');
        $this->createCustomerListView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations');
    }

    /**
     * Load view data procedure
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditContacto':
            case 'ListAlbaranCliente':
            case 'ListComision':
            case 'ListFacturaCliente':
            case 'ListLiquidacionComision':
            case 'ListPedidoCliente':
            case 'ListPresupuestoCliente':
                $codagente = $this->getViewModelValue('EditAgente', 'codagente');
                $where = [new DataBaseWhere('codagente', $codagente)];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                if (!$view->model->exists()) {
                    $view->disableColumn('contact');
                }
                break;
        }
    }
}
