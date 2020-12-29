<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Exception;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Lib\CommissionTools;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\Join\LiquidacionComisionFactura;

/**
 * Description of EditCommissionSettlement
 *
 * @author Artex Trading s.a.   <jcuello@artextrading.com>
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 */
class EditLiquidacionComision extends EditController
{

    const INSERT_DOMICILED_ALL = 'ALL';
    const INSERT_DOMICILED_DOMICILED = 'DOMICILED';
    const INSERT_DOMICILED_WITHOUT = 'WITHOUT';
    const INSERT_STATUS_ALL = 'ALL';
    const INSERT_STATUS_CHARGED = 'CHARGED';
    const VIEWNAME_SETTLEDINVOICE = 'ListLiquidacionComisionFactura';

    /**
     * Returns the model name.
     *
     * @return string
     */
    public function getModelClassName()
    {
        return 'LiquidacionComision';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['menu'] = 'sales';
        $pagedata['title'] = 'settlement';
        $pagedata['icon'] = 'fas fa-chalkboard-teacher';
        return $pagedata;
    }

    /**
     * Calculate the commission percentage for each of the selected invoices
     */
    protected function calculateCommission()
    {
        $data = $this->request->request->all();
        $docs = $this->getInvoicesFromDataForm($data);
        if (empty($docs)) {
            $this->toolBox()->i18nLog()->warning('no-selected-item');
            return;
        }

        $commission = new CommissionTools();
        $this->dataBase->beginTransaction();

        try {
            /// recalculate all business documents
            foreach ($docs as $invoice) {
                $lines = $invoice->getLines();
                $commission->recalculate($invoice, $lines);
                $invoice->save();
            }

            /// update total to settlement commission
            $this->calculateTotalCommission();

            /// confirm changes
            $this->dataBase->commit();

            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
        } catch (Exception $exc) {
            $this->dataBase->rollback();
            $this->toolBox()->log()->error($exc->getMessage());
        }
    }

    /**
     * Calculate the total commission amount for the settlement
     */
    protected function calculateTotalCommission()
    {
        $code = $this->request->query->get('code');
        $this->getModel()->calculateTotalCommission($code);
    }

    /**
     * Add view with Invoices included
     *
     * @param string $viewName
     */
    protected function createSettledInvoiceView(string $viewName = self::VIEWNAME_SETTLEDINVOICE)
    {
        $this->addListView($viewName, 'Join\LiquidacionComisionFactura', 'invoices', 'fas fa-file-invoice');
        $this->views[$viewName]->addOrderBy(['fecha', 'idfactura'], 'date', 2);
        $this->views[$viewName]->addOrderBy(['total'], 'amount');
        $this->views[$viewName]->addOrderBy(['totalcomision'], 'commission');

        /// settings
        $this->setSettings($viewName, 'modalInsert', 'insertinvoices');
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('bottom');

        /// disable company column if there is only one company
        if ($this->empresa->count() < 2) {
            $this->views[$this->getMainViewName()]->disableColumn('company');
        }

        $this->createSettledInvoiceView();
    }

    /**
     * Run the controller after actions.
     *
     * @param string $action
     */
    protected function execAfterAction($action)
    {
        switch ($action) {
            case 'generateinvoice':
                $this->generateInvoice();
                return;
        }

        parent::execAfterAction($action);
    }

    /**
     * Run the actions that alter data before reading it.
     *
     * @param string $action
     *
     * @return bool
     */
    protected function execPreviousAction($action)
    {
        switch ($action) {
            case 'calculatecommission':
                $this->calculateCommission();
                return true;

            case 'delete':
                parent::execPreviousAction($action);
                $this->calculateTotalCommission();
                return true;

            case 'insertinvoices':
                $this->insertInvoices();
                return true;
        }

        return parent::execPreviousAction($action);
    }

    /**
     * Create the invoice for the payment to the agent
     */
    protected function generateInvoice()
    {
        if ($this->views[$this->getMainViewName()]->model->generateInvoice()) {
            $this->toolBox()->i18nLog()->notice('record-updated-correctly');
            return true;
        }

        $this->toolBox()->i18nLog()->error('record-save-error');
        return false;
    }

    /**
     * Get the list of invoices selected by the user
     *
     * @param array $data
     *
     * @return FacturaCliente[]
     */
    protected function getInvoicesFromDataForm($data)
    {
        if (!isset($data['code'])) {
            return [];
        }

        $selected = implode(',', $data['code']);
        if (empty($selected)) {
            return [];
        }

        $invoice = new FacturaCliente();
        $where = [new DataBaseWhere('idfactura', $selected, 'IN')];
        return $invoice->all($where, ['idfactura' => 'ASC'], 0, 0);
    }

    /**
     * Gets a where filter of the data reported in the form
     *
     * @param array $data
     *
     * @return DataBaseWhere[]
     */
    protected function getInvoicesWhere($data)
    {
        /// Basic data filter
        $where = [
            new DataBaseWhere('facturascli.idempresa', $data['idempresa']),
            new DataBaseWhere('facturascli.codserie', $data['codserie']),
            new DataBaseWhere('facturascli.codagente', $data['codagente'])
        ];

        /// Date filter
        if (!empty($data['datefrom'])) {
            $where[] = new DataBaseWhere('facturascli.fecha', $data['datefrom'], '>=');
        }
        if (!empty($data['dateto'])) {
            $where[] = new DataBaseWhere('facturascli.fecha', $data['dateto'], '<=');
        }

        /// Status payment filter
        if ($data['status'] == self::INSERT_STATUS_CHARGED) {
            $where[] = new DataBaseWhere('facturascli.pagada', true);
        }

        /// Payment source filter
        switch ($data['domiciled']) {
            case self::INSERT_DOMICILED_DOMICILED:
                $where[] = new DataBaseWhere('formaspago.domiciliado', true);
                break;

            case self::INSERT_DOMICILED_WITHOUT:
                $where[] = new DataBaseWhere('formaspago.domiciliado', false);
                break;
        }

        /// Customer filter
        if (!empty($data['codcliente'])) {
            $where[] = new DataBaseWhere('facturascli.codcliente', $data['codcliente']);
        }

        /// Return completed filter
        return $where;
    }

    /**
     * Insert Invoices in the settled
     */
    protected function insertInvoices()
    {
        $data = $this->request->request->all();

        /// add new invoice to settlement commission
        $where = $this->getInvoicesWhere($data);
        $settleinvoice = new LiquidacionComisionFactura();
        $settleinvoice->addInvoiceToSettle($data['idliquidacion'], $where);

        /// update total to settlement commission
        $this->calculateTotalCommission();
    }

    /**
     * Loads the data to display.
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case self::VIEWNAME_SETTLEDINVOICE:
                $this->loadDataSettledInvoice($view);
                $this->setViewStatus($viewName, $view);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * Load data to view with Invoices detaill
     *
     * @param BaseView $view
     */
    protected function loadDataSettledInvoice($view)
    {
        /// Get master data
        $mainViewName = $this->getMainViewName();
        $idsettled = $this->getViewModelValue($mainViewName, 'idliquidacion');
        if (empty($idsettled)) {
            return;
        }

        /// Set master values to insert modal view
        $view->model->codagente = $this->getViewModelValue($mainViewName, 'codagente');
        $view->model->codserie = $this->getViewModelValue($mainViewName, 'codserie');
        $view->model->idempresa = $this->getViewModelValue($mainViewName, 'idempresa');
        $view->model->idliquidacion = $idsettled;

        /// Load view data
        $where = [new DataBaseWhere('facturascli.idliquidacion', $idsettled)];
        $view->loadData('', $where);
    }

    /**
     * Allows you to set special conditions for columns and action buttons
     * based on the state of the views
     *
     * @param string   $viewName
     * @param BaseView $view
     */
    protected function setViewStatus($viewName, $view)
    {
        if ($view->count === 0) {
            $this->setSettings($viewName, 'btnDelete', false);
            return;
        }

        /// disable some fields in the main view
        $mainViewName = $this->getMainViewName();
        $this->views[$mainViewName]->disableColumn('company', false, 'true');
        $this->views[$mainViewName]->disableColumn('serie', false, 'true');
        $this->views[$mainViewName]->disableColumn('agent', false, 'true');

        /// Is there an invoice created?
        $canInvoice = empty($this->getViewModelValue($mainViewName, 'idfactura'));

        /// Update insert/delete buttons status
        $this->setSettings($viewName, 'btnNew', $canInvoice);
        $this->setSettings($viewName, 'btnDelete', $canInvoice);

        if ($canInvoice) {
            $this->addButton($viewName, [
                'action' => 'calculatecommission',
                'confirm' => 'true',
                'icon' => 'fas fa-percentage',
                'label' => 'calculate'
            ]);
        }

        $total = $this->getViewModelValue($mainViewName, 'total');
        if ($canInvoice && $total > 0) {
            $this->addButton($mainViewName, [
                'action' => 'generateinvoice',
                'color' => 'info',
                'confirm' => true,
                'icon' => 'fas fa-file-invoice',
                'label' => 'generate-invoice'
            ]);
        }
    }
}
