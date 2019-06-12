<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\CommissionCalculate;
use FacturaScripts\Core\Lib\ExtendedController\BaseView;
use FacturaScripts\Core\Lib\ExtendedController\EditController;
use FacturaScripts\Dinamic\Model\ModelView\LiquidacionComisionFactura;
use FacturaScripts\Dinamic\Model\FacturaCliente;

/**
 * Description of EditCommissionSettlement
 *
 * @author Artex Trading s.a. <jcuello@artextrading.com>
 */
class EditLiquidacionComision extends EditController
{

    const VIEWNAME_SETTLEDINVOICE = 'ListLiquidacionComisionFactura';
    const INSERT_STATUS_ALL = 'ALL';
    const INSERT_STATUS_CHARGED = 'CHARGED';
    const INSERT_DOMICILED_ALL = 'ALL';
    const INSERT_DOMICILED_DOMICILED = 'DOMICILED';
    const INSERT_DOMICILED_WITHOUT = 'WITHOUT';

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
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();

        $this->addSettledInvoiceView();
        $this->setTabsPosition('bottom');
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
            case 'insertinvoices':
                $data = $this->request->request->all();
                $this->insertInvoices($data);
                return true;

            case 'calculatecommission':
                $data = $this->request->request->all();
                $this->calculateCommission($data);
                return true;

            case 'generateinvoice':
                $this->generateInvoice();
                return true;

            case 'delete':
                parent::execPreviousAction($action);
                $this->calculateTotalCommission();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
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
                $this->setViewStatus($view);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * Add view with Invoices included
     *
     * @param string $viewName
     */
    private function addSettledInvoiceView($viewName = self::VIEWNAME_SETTLEDINVOICE)
    {
        $this->addListView($viewName, 'ModelView\LiquidacionComisionFactura', 'invoices', 'fas fa-file-invoice');
        $this->setSettings($viewName, 'modalInsert', 'insertinvoices');
    }

    /**
     * Calculate the commission percentage for each of the selected invoices
     *
     * @param array $data
     */
    private function calculateCommission($data)
    {
        $commission = new CommissionCalculate();
        $docs = $this->getInvoicesFromDataForm($data);

        $this->dataBase->beginTransaction();
        try {
            /// recalculate all business documents
            if (isset($docs)) {
                foreach ($docs as $invoice) {
                    $commission->recalculate($invoice);
                }
            }

            /// update total to settlement commission
            $this->calculateTotalCommission();

            /// confirm changes
            $this->dataBase->commit();
        } catch (Exception $ex) {
            $this->dataBase->rollback();
            $this->miniLog->error($ex->getMessage());
        }
    }

    /**
     * Calculate the total commission amount for the settlement
     */
    private function calculateTotalCommission()
    {
        $settle = $this->request->get('code');
        $this->getModel()->calculateTotalCommission($settle);
    }

    /**
     * Indicates if any data necessary for the insertion of invoices
     * in the settlement is missing.
     *
     * @param array $data
     *
     * @return bool
     */
    private function errorInInsertData($data): bool
    {
        return empty($data['idliquidacion']) ||
            empty($data['codejercicio']) ||
            empty($data['codagente']);
    }

    /**
     * Indicates if any of the periods necessary for the insertion of invoices
     * in the settlement are missing.
     *
     * @param array $data
     * @return bool
     */
    private function errorInSelectDates($data): bool
    {
        return empty($data['datefrom']) && empty($data['dateto']);
    }

    /**
     * Create the invoice for the payment to the agent
     */
    private function generateInvoice()
    {
        ;
    }

    /**
     * Return invoice button configuration
     *
     * @return array
     */
    private function getInvoiceButton()
    {
        return [
            'action' => 'generateinvoice',
            'icon' => 'fas fa-file-invoice',
            'label' => 'generate-invoice',
            'type' => 'action',
            'color' => 'info',
            'confirm' => true,
        ];
    }

    /**
     * Get the list of invoices selected by the user
     *
     * @param array $data
     * @return FacturaCliente[]|null
     */
    private function getInvoicesFromDataForm($data)
    {
        $selected = implode(',', $data['code']);
        if (empty($selected)) {
            return null;
        }

        $invoice = new FacturaCliente();
        $where = [ new DataBaseWhere('idfactura', $selected, 'IN') ];
        $order = ['idfactura' => 'ASC'];
        return $invoice->all($where, $order, 0, 0);
    }

    /**
     * Gets a where filter of the data reported in the form
     *
     * @param array $data
     *
     * @return DatabaseWhere[]
     */
    private function getInvoicesWhere($data)
    {
        /// Basic data filter
        $where = [
            new DatabaseWhere('facturascli.codagente', $data['codagente']),
            new DatabaseWhere('facturascli.codejercicio', $data['codejercicio']),
        ];

        /// Dates filter
        if (!empty($data['datefrom'])) {
            $this->getWhereFromDate($where, $data, 'datefrom', 'dateto', 'facturascli.fecha');
        }

        /// Status payment filter
        if ($data['status'] == self::INSERT_STATUS_CHARGED) {
            $where[] = new DatabaseWhere('facturascli.pagada', true);
        }

        /// Payment source filter
        switch ($data['domiciled']) {
            case self::INSERT_DOMICILED_DOMICILED:
                $where[] = new DatabaseWhere('formaspago.domiciliado', true);
                break;

            case self::INSERT_DOMICILED_WITHOUT:
                $where[] = new DatabaseWhere('formaspago.domiciliado', false);
                break;
        }

        /// Customer filter
        if (!empty($data['codcliente'])) {
            $where[] = new DatabaseWhere('facturascli.codcliente', $data['codcliente']);
        }

        /// Return completed filter
        return $where;
    }

    /**
     * Get a where filter based on the dates indicated, applied to the names
     * of fields that are reported
     *
     * @param DatabaseWhere[] $where
     * @param array           $data
     * @param string          $fieldFrom
     * @param string          $fieldTo
     * @param string          $sqlField
     */
    private function getWhereFromDate(&$where, $data, $fieldFrom, $fieldTo, $sqlField)
    {
        if (empty($data[$fieldTo])) {
            $where[] = new DatabaseWhere($sqlField, $data[$fieldFrom]);
            return;
        }

        $where[] = new DatabaseWhere($sqlField, $data[$fieldFrom], '>=');
        $where[] = new DatabaseWhere($sqlField, $data[$fieldTo], '<=');
    }

    /**
     * Insert Invoices in the settled
     *
     * @param array $data
     */
    private function insertInvoices($data)
    {
        if ($this->errorInInsertData($data)) {
            $this->miniLog->error($this->i18n->trans('insert-invoices-data-error'));
            return;
        }

        if ($this->errorInSelectDates($data)) {
            $this->miniLog->error($this->i18n->trans('insert-invoices-date-error'));
            return;
        }

        /// add new invoice to settlement commission
        $where = $this->getInvoicesWhere($data);
        $settleinvoice = new LiquidacionComisionFactura();
        $settleinvoice->addInvoiceToSettle($data['idliquidacion'], $where);

        /// update total to settlement commission
        $this->calculateTotalCommission();
    }

    /**
     * Load data to view with Invoices detaill
     *
     * @param BaseView $view
     */
    private function loadDataSettledInvoice($view)
    {
        /// Get master data
        $mainViewName = $this->getMainViewName();
        $idsettled = $this->getViewModelValue($mainViewName, 'idliquidacion');

        /// Set master values to insert modal view
        $view->model->idliquidacion = $idsettled;
        $view->model->codejercicio = $this->getViewModelValue($mainViewName, 'codejercicio');
        $view->model->codagente = $this->getViewModelValue($mainViewName, 'codagente');

        /// Load view data
        $where = [new DataBaseWhere('facturascli.idliquidacion', $idsettled)];
        $view->loadData(false, $where, ['facturascli.codigo' => 'ASC']);
    }

    /**
     * Allows you to set special conditions for columns and action buttons
     * based on the state of the views
     *
     * @param BaseView $view
     */
    private function setViewStatus($view)
    {
        /// If new record, nothing to do
        $mainViewName = $this->getMainViewName();
        $idsettled = $this->getViewModelValue($mainViewName, 'idliquidacion');
        if (empty($idsettled)) {
            return;
        }

        /// Add invoice button and insert/delete
        $idinvoice = $this->getViewModelValue($mainViewName, 'idfactura');
        if (empty($idinvoice)) {
            $this->addButton($mainViewName, $this->getInvoiceButton());
        } else {
            $view->settings['btnNew'] = false;
            $view->settings['btnDelete'] = false;
        }

        /// Disable header fields when there are invoice selected
        if ($view->count > 0) {
            $masterView = $this->views[$mainViewName];
            $masterView->disableColumn('exercise', false, 'true');
            $masterView->disableColumn('agent', false, 'true');
        }
    }
}
