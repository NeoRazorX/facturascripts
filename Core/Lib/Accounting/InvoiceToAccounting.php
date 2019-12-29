<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Dinamic\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Retencion;
use FacturaScripts\Dinamic\Model\Serie;

/**
 * Class for the generation of accounting entries of a sale/purchase document
 * and the settlement of your receipts.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class InvoiceToAccounting extends AccountingClass
{

    /**
     * Document Subtotals Lines array
     *
     * @var array
     */
    protected $subtotals;

    /**
     * Method to launch the accounting process
     *
     * @param FacturaCliente|FacturaProveedor $model
     */
    public function generate($model)
    {
        parent::generate($model);
        if (!$this->initialChecks()) {
            return;
        }

        switch ($model->modelClassName()) {
            case 'FacturaCliente':
                $this->salesAccountingEntry();
                break;

            case 'FacturaProveedor':
                $this->purchaseAccountingEntry();
                break;
        }
    }

    /**
     * Add the customer line to the accounting entry
     *
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addCustomerLine($accountEntry): bool
    {
        $customer = new Cliente();
        if (!$customer->loadFromCode($this->document->codcliente)) {
            $this->toolBox()->i18nLog()->warning('customer-not-found');
            return false;
        }

        $subaccount = $this->getCustomerAccount($customer);
        if (!$subaccount->exists()) {
            $this->toolBox()->i18nLog()->warning('customer-account-not-found');
            return false;
        }

        return $this->addBasicLine($accountEntry, $subaccount, true);
    }

    /**
     *
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addGoodsPurchaseLine($accountEntry)
    {
        $cuenta = $this->getSpecialAccount('COMPRA');
        if (!$cuenta->exists()) {
            $this->toolBox()->i18nLog()->warning('purchases-account-not-found');
            return false;
        }

        foreach ($cuenta->getSubcuentas() as $subcuenta) {
            $line = $accountEntry->getNewLine();
            $line->codsubcuenta = $subcuenta->codsubcuenta;
            $line->debe = $this->document->neto;
            $line->idsubcuenta = $subcuenta->idsubcuenta;
            return $line->save();
        }

        $this->toolBox()->i18nLog()->warning('purchases-subaccount-not-found');
        return false;
    }

    /**
     *
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addGoodsSalesLine($accountEntry)
    {
        $cuenta = $this->getSpecialAccount('VENTAS');
        if (!$cuenta->exists()) {
            $this->toolBox()->i18nLog()->warning('sales-account-not-found');
            return false;
        }

        foreach ($cuenta->getSubcuentas() as $subcuenta) {
            $line = $accountEntry->getNewLine();
            $line->codsubcuenta = $subcuenta->codsubcuenta;
            $line->haber = $this->document->neto;
            $line->idsubcuenta = $subcuenta->idsubcuenta;
            return $line->save();
        }

        $this->toolBox()->i18nLog()->warning('sales-subaccount-not-found');
        return false;
    }

    /**
     *
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addPurchaseIrpfLines($accountEntry)
    {
        if (empty($this->document->totalirpf) || count($this->subtotals) == 0) {
            return true;
        }

        $key = array_keys($this->subtotals)[0];
        $percentaje = $this->subtotals[$key]['irpf'];

        $retention = new Retencion();
        if (!$retention->loadFromPercentage($percentaje)) {
            $this->toolBox()->i18nLog()->warning('irpf-code-not-found');
            return false;
        }

        $subaccount = $this->getIRPFPurchaseAccount($retention);
        if (!$subaccount->exists()) {
            $this->toolBox()->i18nLog()->warning('irpfpr-subaccount-not-found');
            return false;
        }

        return $this->addBasicLine($accountEntry, $subaccount, false, $this->subtotals[$key]['totalirpf']);
    }

    /**
     * Add the purchase line to the accounting entry
     *
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addPurchaseTaxLines($accountEntry): bool
    {
        $tax = new Impuesto();
        foreach ($this->subtotals as $key => $value) {
            /// search for tax data
            $tax->loadFromCode($key);
            $subaccount = $this->getTaxSupportedAccount($tax);
            if (!$subaccount->exists()) {
                $this->toolBox()->i18nLog()->warning('ivasop-account-not-found');
                return false;
            }

            if (!$this->addTaxLine($accountEntry, $subaccount, true, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addSalesIrpfLines($accountEntry)
    {
        if (empty($this->document->totalirpf) || count($this->subtotals) == 0) {
            return true;
        }

        $key = array_keys($this->subtotals)[0];
        $percentaje = $this->subtotals[$key]['irpf'];

        $retention = new Retencion();
        if (!$retention->loadFromPercentage($percentaje)) {
            $this->toolBox()->i18nLog()->warning('irpf-code-not-found');
            return false;
        }

        $subaccount = $this->getIRPFSalesAccount($retention);
        if (!$subaccount->exists()) {
            $this->toolBox()->i18nLog()->warning('irpf-subaccount-not-found');
            return false;
        }

        return $this->addBasicLine($accountEntry, $subaccount, true, $this->subtotals[$key]['totalirpf']);
    }

    /**
     * Add the sales line to the accounting entry
     *
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addSalesTaxLines($accountEntry): bool
    {
        $tax = new Impuesto();
        foreach ($this->subtotals as $key => $value) {
            /// search for tax data
            $tax->loadFromCode($key);
            $subaccount = $this->getTaxImpactedAccount($tax);
            if (!$subaccount->exists()) {
                $this->toolBox()->i18nLog()->warning('ivarep-subaccount-not-found');
                return false;
            }

            /// add tax line
            if (!$this->addTaxLine($accountEntry, $subaccount, false, $value)) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @param Asiento $accountEntry
     *
     * @return bool
     */
    protected function addSupplierLine($accountEntry): bool
    {
        $supplier = new Proveedor();
        if (!$supplier->loadFromCode($this->document->codproveedor)) {
            $this->toolBox()->i18nLog()->warning('supplier-not-found');
            return false;
        }

        $subaccount = $this->getSupplierAccount($supplier);
        if (!$subaccount->exists()) {
            $this->toolBox()->i18nLog()->warning('supplier-account-not-found');
            return false;
        }

        return $this->addBasicLine($accountEntry, $subaccount, false);
    }

    /**
     * Perform the initial checks to continue with the accounting process
     *
     * @return bool
     */
    protected function initialChecks(): bool
    {
        if (!empty($this->document->idasiento)) {
            return false;
        }

        if (!$this->exercise->loadFromCode($this->document->codejercicio) || !$this->exercise->isOpened()) {
            $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->document->codejercicio]);
            return false;
        }

        if (!$this->loadSubtotals()) {
            $this->toolBox()->i18nLog()->warning('invoice-subtotals-error');
            return false;
        }

        return true;
    }

    /**
     *
     * @return bool
     */
    protected function loadSubtotals(): bool
    {
        $tools = new BusinessDocumentTools();
        $this->subtotals = $tools->getSubtotals($this->document->getLines(), [$this->document->dtopor1, $this->document->dtopor2]);
        return !empty($this->document->total);
    }

    /**
     * Generate the accounting entry for a purchase document.
     */
    protected function purchaseAccountingEntry()
    {
        $accountEntry = new Asiento();
        $this->setAccountingData($accountEntry, $this->toolBox()->i18n()->trans('supplier-invoice') . ' ' . $this->document->codigo);
        if (!$accountEntry->save()) {
            $this->toolBox()->i18nLog()->warning('accounting-entry-error');
            return;
        }

        if ($this->addSupplierLine($accountEntry) &&
            $this->addPurchaseTaxLines($accountEntry) &&
            $this->addPurchaseIrpfLines($accountEntry) &&
            $this->addGoodsPurchaseLine($accountEntry)) {
            $this->document->idasiento = $accountEntry->primaryColumnValue();
            return;
        }

        $this->toolBox()->i18nLog()->warning('accounting-lines-error');
        $accountEntry->delete();
    }

    /**
     * Generate the accounting entry for a sales document.
     */
    protected function salesAccountingEntry()
    {
        $accountEntry = new Asiento();
        $this->setAccountingData($accountEntry, $this->toolBox()->i18n()->trans('customer-invoice') . ' ' . $this->document->codigo);
        if (!$accountEntry->save()) {
            $this->toolBox()->i18nLog()->warning('accounting-entry-error');
            return;
        }

        if ($this->addCustomerLine($accountEntry) &&
            $this->addSalesTaxLines($accountEntry) &&
            $this->addSalesIrpfLines($accountEntry) &&
            $this->addGoodsSalesLine($accountEntry)) {
            $this->document->idasiento = $accountEntry->primaryColumnValue();
            return;
        }

        $this->toolBox()->i18nLog()->warning('accounting-lines-error');
        $accountEntry->delete();
    }

    /**
     * Assign the document data to the accounting entry
     *
     * @param Asiento $accountEntry
     * @param string  $concept
     */
    protected function setAccountingData(&$accountEntry, $concept)
    {
        $accountEntry->codejercicio = $this->document->codejercicio;
        $accountEntry->concepto = $concept;
        $accountEntry->documento = $this->document->codigo;
        $accountEntry->fecha = $this->document->fecha;
        $accountEntry->idempresa = $this->document->idempresa;
        $accountEntry->importe = $this->document->total;
        $accountEntry->editable = false;

        /// Assign analytical data defined in Serie model
        $serie = new Serie();
        $serie->loadFromCode($this->document->codserie);

        $accountEntry->iddiario = $serie->iddiario;
        $accountEntry->canal = $serie->canal;
    }
}
