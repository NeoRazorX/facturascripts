<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\ExtendedController\EditController;

/**
 * Description of EditCommissionSettlement
 *
 * @author Artex Trading s.a. <jcuello@artextrading.com>
 */
class EditLiquidacionComision extends EditController
{

    const VIEWNAME_SETTLEDRECEIPT = 'ListLineaLiquidacionComision';

    const INSERT_STATUS_ALL = 'ALL';
    const INSERT_STATUS_CHARGED = 'CHARGED';

    const INSERT_DOMICILED_ALL = 'ALL';
    const INSERT_DOMICILED_DOMICILED = 'DOMICILED';
    const INSERT_DOMICILED_WITHOUT = 'WITHOUT';

    /**
     * Add view with Receipts included
     *
     * @param string $viewName
     */
    private function addSettledReceiptView($viewName = self::VIEWNAME_SETTLEDRECEIPT)
    {
        $this->addListView($viewName, 'ModelView\LineaLiquidacionComision', 'receipts', 'fas fa-Receipt');
        $this->setSettings($viewName, 'modalInsert', 'insertreceipts');
    }

    /**
     * Create views
     */
    protected function createViews()
    {
        parent::createViews();

        $this->addSettledReceiptView();
        $this->setTabsPosition('bottom');
    }

    /**
     * Set Read Only property to column
     *
     * @param BaseView $view
     * @param string $columnName
     */
    private function disableColumn($view, $columnName)
    {
        $column = $view->columnForName($columnName);
        if (empty($column)) {
            return;
        }

        $column->widget->readonly = 'true';
    }

    /**
     * Indicates if any data necessary for the insertion of receipts
     * in the settlement is missing.
     *
     * @param array $data
     * @return bool
     */
    private function errorInInsertData($data): bool
    {
        return empty($data['idsettled']) ||
            empty($data['idexercise']) ||
            empty($data['idagent']);
    }

    /**
     * Indicates if any of the periods necessary for the insertion of receipts
     * in the settlement are missing.
     *
     * @param array $data
     * @return bool
     */
    private function errorInSelectDates($data): bool
    {
        return empty($data['datefrom']) &&
            empty($data['dateto']) &&
            empty($data['expirationfrom']) &&
            empty($data['expirationto']);
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
            case 'insertreceipts':
                $data = $this->request->request->all();
                $this->insertReceipts($data);
                return true;

            case 'generateinvoice':
                $this->generateInvoice();
                return true;

            default:
                return parent::execPreviousAction($action);
        }
    }

    /**
     * Create the invoice for the payment to the agent
     */
    private function generateInvoice()
    {
        /*
        $factura = new FacturaProveedor();
        $factura->setSubject($proveedor);
        $factura->save();

        $newLinea = $factura->getNewProductLine('123'); /// referencia 123
        $newLinea->cantidad = 2;
        $newLinea->save();

        /// recalculamos
        $docTools = new BusinessDocumentTools();
        $docTools->recalculate($factura);
        $factura->save();
        */
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
     * Returns the model name
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
        $pagedata['submenu'] = 'commissions';
        $pagedata['title'] = 'settlement';
        $pagedata['icon'] = 'fas fa-chalkboard-teacher';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Gets a where filter of the data reported in the form
     *
     * @param array $data
     * @return DatabaseWhere[]
     */
    private function getReceiptsWhere($data)
    {
        /// Basic data filter
        $where = [
            new DatabaseWhere('reciboscli.codagente', $data['idagent']),
            new DatabaseWhere('facturascli.codejercicio', $data['idexercise']),
        ];

        /// Dates filter
        if (!empty($data['datefrom'])) {
            $this->getWhereFromDate($where, $data, 'datefrom', 'dateto', 'facturascli.fecha');
        }

        if (!empty($data['expirationfrom'])) {
            $this->getWhereFromDate($where, $data, 'expirationfrom', 'expirationto', 'reciboscli.fechav');
        }

        /// Status payment filter
        if ($data['status'] == self::INSERT_STATUS_CHARGED) {
            $where[] = new DatabaseWhere('reciboscli.estado', SettledReceipt::STATE_PAID);
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
        if (!empty($data['customer'])) {
            $where[] = new DatabaseWhere('reciboscli.codcliente', $data['customer']);
        }

        /// Return completed filter
        return $where;
    }

    /**
     * Get a where filter based on the dates indicated, applied to the names
     * of fields that are reported
     *
     * @param DatabaseWhere[] $where
     * @param array $data
     * @param string $fieldFrom
     * @param string $fieldTo
     * @param string $sqlField
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
     * Insert Receipts in the settled
     *
     * @param array $data
     */
    private function insertReceipts($data)
    {
        if ($this->errorInInsertData($data)) {
            $this->miniLog->error($this->i18n->trans('insert-receipts-data-error'));
            return;
        }

        if ($this->errorInSelectDates($data)) {
            $this->miniLog->error($this->i18n->trans('insert-receipts-date-error'));
            return;
        }

        $where = $this->getReceiptsWhere($data);
        $settledReceipt = new SettledReceipt();
        $settledReceipt->addSettledReceiptFromSales($data['idsettled'], $where);
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
            case self::VIEWNAME_SETTLEDRECEIPT:
                $this->loadDataSettledReceipt($view);
                $this->setViewStatus($view);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

    /**
     * Load data to view with Receipts detaill
     *
     * @param BaseView $view
     */
    private function loadDataSettledReceipt($view)
    {
        /// Get master data
        $mainViewName = $this->getMainViewName();
        $idsettled = $this->getViewModelValue($mainViewName, 'idliquidacion');

        /// Set master values to insert modal view
        $view->model->idsettled = $idsettled;
        $view->model->idexercise = $this->getViewModelValue($mainViewName, 'codejercicio');
        $view->model->idagent = $this->getViewModelValue($mainViewName, 'codagente');

        /// Load view data
        $where = [new DataBaseWhere('idliquidacion', $idsettled)];
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
        $mainViewName = $this->getMainViewName();
        $idsettled = $this->getViewModelValue($mainViewName, 'idliquidacion');
        if (empty($idsettled)) {
            return;
        }

        $idinvoice = $this->getViewModelValue($mainViewName, 'idfactura');
        if (empty($idinvoice)) {
            $this->addButton($mainViewName, $this->getInvoiceButton());
        } else {
            $view->settings['btnNew'] = false;
            $view->settings['btnDelete'] = false;
        }

        if ($view->count > 0) {
            $masterView = $this->views[$mainViewName];
            $this->disableColumn($masterView, 'exercise');
            $this->disableColumn($masterView, 'agent');
        }
    }
}
