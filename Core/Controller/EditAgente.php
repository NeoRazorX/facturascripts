<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\ComercialContactController;
use FacturaScripts\Dinamic\Model\Agente;
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
        return $this->toolBox()->coins()->format($totalModel->totals['total'], 2);
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
    protected function createCommissionsView(string $viewName = 'ListComision')
    {
        $this->addListView($viewName, 'Comision', 'commissions', 'fas fa-percentage');
        $this->views[$viewName]->addOrderBy(['prioridad'], 'priority', 2);
        $this->views[$viewName]->addOrderBy(['porcentaje'], 'percentage');

        /// disable columns
        $this->views[$viewName]->disableColumn('agent', true);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createContactView(string $viewName = 'EditContacto')
    {
        $this->addEditView($viewName, 'Contacto', 'contact', 'fa fa-address-book');

        /// disable columns
        $this->views[$viewName]->disableColumn('agent', true);
        $this->views[$viewName]->disableColumn('company', true);
        $this->views[$viewName]->disableColumn('fiscal-id', true);
        $this->views[$viewName]->disableColumn('fiscal-number', true);
        $this->views[$viewName]->disableColumn('position', true);

        /// disable delete button
        $this->setSettings($viewName, 'btnDelete', false);
    }

    /**
     *
     * @param string $viewName
     * @param string $model
     * @param string $label
     */
    protected function createDocumentView(string $viewName, string $model, string $label)
    {
        $this->createCustomerListView($viewName, $model, $label);
        $this->addButtonGroupDocument($viewName);
        $this->addButtonApproveDocument($viewName);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createInvoiceView(string $viewName)
    {
        $this->createCustomerListView($viewName, 'FacturaCliente', 'invoices');
        $this->addButtonLockInvoice($viewName);
    }

    /**
     *
     * @param string $viewName
     */
    protected function createSettlementView(string $viewName = 'ListLiquidacionComision')
    {
        $this->addListView($viewName, 'LiquidacionComision', 'settlements', 'fas fa-chalkboard-teacher');
        $this->views[$viewName]->addOrderBy(['fecha'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['total'], 'amount');
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
        $this->createInvoiceView('ListFacturaCliente');
        $this->createDocumentView('ListAlbaranCliente', 'AlbaranCliente', 'delivery-notes');
        $this->createDocumentView('ListPedidoCliente', 'PedidoCliente', 'orders');
        $this->createDocumentView('ListPresupuestoCliente', 'PresupuestoCliente', 'estimations');
    }

    /**
     *
     * @return bool
     */
    protected function editAction()
    {
        $return = parent::editAction();
        if ($return && $this->active == 'EditContacto') {
            /// update agent data when contact data is updated
            $agente = new Agente();
            if ($agente->loadFromCode($this->views[$this->active]->model->codagente)) {
                $agente->email = $this->views[$this->active]->model->email;
                $agente->telefono1 = $this->views[$this->active]->model->telefono1;
                $agente->telefono2 = $this->views[$this->active]->model->telefono2;
                $agente->save();
            }
        }

        return $return;
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
                if (false === $view->model->exists()) {
                    $view->disableColumn('contact');
                }
                break;
        }
    }

    /**
     *
     * @param string $viewName
     */
    protected function setCustomWidgetValues($viewName)
    {
        ;
    }
}
