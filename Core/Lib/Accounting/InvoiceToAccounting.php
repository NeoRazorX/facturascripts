<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\Join\PurchasesDocIrpfAccount;
use FacturaScripts\Dinamic\Model\Join\PurchasesDocLineAccount;
use FacturaScripts\Dinamic\Model\Join\SalesDocLineAccount;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Retencion;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Dinamic\Model\Subcuenta;

/**
 * Class for the generation of accounting entries of a sale/purchase document
 * and the settlement of your receipts.
 *
 * @author Carlos García Gómez           <carlos@facturascripts.com>
 * @author Jose Antonio Cuello Principal <yopli2000@gmail.com>
 * @author Raúl Jiménez Jiménez          <raljopa@gmail.com>
 */
class InvoiceToAccounting extends AccountingClass
{
    /**
     * @var Subcuenta
     */
    protected $counterpart;

    /**
     * @var FacturaCliente|FacturaProveedor
     */
    protected $document;

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
        if (false === $this->initialChecks()) {
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
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addCustomerLine(Asiento $entry): bool
    {
        $customer = new Cliente();
        if (false === $customer->loadFromCode($this->document->codcliente)) {
            ToolBox::i18nLog()->warning('customer-not-found');
            $this->counterpart = null;
            return false;
        }

        $subAccount = $this->getCustomerAccount($customer);
        if (false === $subAccount->exists()) {
            ToolBox::i18nLog()->warning('customer-account-not-found');
            $this->counterpart = null;
            return false;
        }

        $this->counterpart = $subAccount;
        return $this->addBasicLine($entry, $subAccount, true);
    }

    /**
     * Add the goods purchase line to the accounting entry.
     * Make one line for each product/family purchase subaccount.
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addGoodsPurchaseLine(Asiento $entry): bool
    {
        $rectifAccount = $this->getSpecialSubAccount('DEVCOM');
        $purchaseAccount = $this->document->idfacturarect && $rectifAccount->exists() ? $rectifAccount :
            $this->getSpecialSubAccount('COMPRA');

        $tool = new PurchasesDocLineAccount();
        $totals = $tool->getTotalsForDocument($this->document, $purchaseAccount->codsubcuenta ?? '');
        return $this->addLinesFromTotals(
            $entry,
            $totals,
            true,
            $this->counterpart,
            'purchases-subaccount-not-found',
            'good-purchases-line-error'
        );
    }

    /**
     * Add the goods sales line to the accounting entry.
     * Make one line for each product/family sale subaccount.
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addGoodsSalesLine(Asiento $entry): bool
    {
        $rectifAccount = $this->getSpecialSubAccount('DEVVEN');
        $salesAccount = $this->document->idfacturarect && $rectifAccount->exists() ? $rectifAccount :
            $this->getSpecialSubAccount('VENTAS');

        $tool = new SalesDocLineAccount();
        $totals = $tool->getTotalsForDocument($this->document, $salesAccount->codsubcuenta ?? '');
        return $this->addLinesFromTotals(
            $entry,
            $totals,
            false,
            $this->counterpart,
            'sales-subaccount-not-found',
            'good-sales-line-error'
        );
    }

    /**
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addPurchaseIrpfLines(Asiento $entry): bool
    {
        if (empty($this->document->totalirpf) || count($this->subtotals) == 0) {
            return true;
        }

        $retention = new Retencion();
        if (false === $retention->loadFromPercentage($this->subtotals['irpf'])) {
            ToolBox::i18nLog()->warning('irpf-record-not-found', ['%value%' => $this->subtotals['irpf']]);
            return false;
        }

        $account = $this->getIRPFPurchaseAccount($retention);
        if (false === $account->exists()) {
            ToolBox::i18nLog()->warning('irpfpr-subaccount-not-found');
            return false;
        }

        $tool = new PurchasesDocIrpfAccount();
        $totals = $tool->getTotalsForDocument($this->document, $account->codsubcuenta ?? '', $this->subtotals['irpf']);
        return $this->addLinesFromTotals(
            $entry,
            $totals,
            false,
            $this->counterpart,
            'irpf-subaccount-not-found',
            'irpf-purchase-line-error'
        );
    }

    /**
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addPurchaseSuppliedLines(Asiento $entry): bool
    {
        if (empty($this->document->totalsuplidos)) {
            return true;
        }

        $subAccount = $this->getSpecialSubAccount('SUPLI');
        if (false === $subAccount->exists()) {
            ToolBox::i18nLog()->warning('supplied-subaccount-not-found');
            return false;
        }

        return $this->addBasicLine($entry, $subAccount, true, $this->document->totalsuplidos);
    }

    /**
     * Add the purchase line to the accounting entry
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addPurchaseTaxLines(Asiento $entry): bool
    {
        $tax = new Impuesto();
        foreach ($this->subtotals['iva'] as $key => $value) {
            // search for tax data
            $tax->loadFromCode($value['codimpuesto']);
            $subAccount = $this->getTaxSupportedAccount($tax);
            if (false === $subAccount->exists()) {
                ToolBox::i18nLog()->warning('ivasop-account-not-found');
                return false;
            }

            if (false === $this->addTaxLine($entry, $subAccount, $this->counterpart, true, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addSalesIrpfLines(Asiento $entry): bool
    {
        if (empty($this->document->totalirpf) || count($this->subtotals) == 0) {
            return true;
        }

        $retention = new Retencion();
        if (false === $retention->loadFromPercentage($this->subtotals['irpf'])) {
            ToolBox::i18nLog()->warning('irpf-record-not-found', ['%value%' => $this->subtotals['irpf']]);
            return false;
        }

        $account = $this->getIRPFSalesAccount($retention);
        if (false === $account->exists()) {
            ToolBox::i18nLog()->warning('irpf-subaccount-not-found');
            return false;
        }

        $newLine = $this->getBasicLine($entry, $account, true, $this->subtotals['totalirpf']);
        $newLine->setCounterpart($this->counterpart);
        return $newLine->save();
    }

    /**
     * Add the supplied line to the accounting entry
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addSalesSuppliedLines(Asiento $entry): bool
    {
        if (empty($this->document->totalsuplidos)) {
            return true;
        }

        $subAccount = $this->getSpecialSubAccount('SUPLI');
        if (false === $subAccount->exists()) {
            ToolBox::i18nLog()->warning('supplied-subaccount-not-found');
            return false;
        }

        return $this->addBasicLine($entry, $subAccount, false, $this->document->totalsuplidos);
    }

    /**
     * Add the sales line to the accounting entry
     *
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addSalesTaxLines(Asiento $entry): bool
    {
        $tax = new Impuesto();
        foreach ($this->subtotals['iva'] as $key => $value) {
            // search for tax data
            $tax->loadFromCode($value['codimpuesto']);
            $subAccount = $this->getTaxImpactedAccount($tax);
            if (false === $subAccount->exists()) {
                ToolBox::i18nLog()->warning('ivarep-subaccount-not-found');
                return false;
            }

            // add tax line
            if (false === $this->addTaxLine($entry, $subAccount, $this->counterpart, false, $value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Asiento $entry
     *
     * @return bool
     */
    protected function addSupplierLine(Asiento $entry): bool
    {
        $supplier = new Proveedor();
        if (false === $supplier->loadFromCode($this->document->codproveedor)) {
            ToolBox::i18nLog()->warning('supplier-not-found');
            $this->counterpart = null;
            return false;
        }

        $subAccount = $this->getSupplierAccount($supplier);
        if (false === $subAccount->exists()) {
            ToolBox::i18nLog()->warning('supplier-account-not-found');
            $this->counterpart = null;
            return false;
        }

        $this->counterpart = $subAccount;
        return $this->addBasicLine($entry, $subAccount, false);
    }

    /**
     * Perform the initial checks to continue with the accounting process
     *
     * @return bool
     */
    protected function initialChecks(): bool
    {
        if (!empty($this->document->idasiento) || empty($this->document->total)) {
            return false;
        }

        if (false === $this->exercise->loadFromCode($this->document->codejercicio) || false === $this->exercise->isOpened()) {
            ToolBox::i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->document->codejercicio]);
            return false;
        }

        if (false === $this->exercise->hasAccountingPlan()) {
            ToolBox::i18nLog()->warning('exercise-without-accounting-plan', ['%exercise%' => $this->exercise->codejercicio]);
            return false;
        }

        if (false === $this->loadSubtotals()) {
            ToolBox::i18nLog()->warning('invoice-subtotals-error');
            return false;
        }

        $cuenta = new Cuenta();
        $where = [new DataBaseWhere('codejercicio', $this->document->codejercicio)];
        if (0 === $cuenta->count($where)) {
            ToolBox::i18nLog()->warning('accounting-data-missing', ['%exerciseName%' => $this->document->codejercicio]);
            return false;
        }

        return true;
    }

    protected function loadSubtotals(): bool
    {
        $this->subtotals = Calculator::getSubtotals($this->document, $this->document->getLines());
        return !empty($this->document->total);
    }

    /**
     * Generate the accounting entry for a purchase document.
     */
    protected function purchaseAccountingEntry()
    {
        $concept = ToolBox::i18n()->trans('supplier-invoice') . ' ' . $this->document->codigo;
        $concept .= $this->document->numproveedor ? ' (' . $this->document->numproveedor . ') - ' . $this->document->nombre :
            ' - ' . $this->document->nombre;

        $entry = new Asiento();
        $this->setAccountingData($entry, $concept);
        if (false === $entry->save()) {
            ToolBox::i18nLog()->warning('accounting-entry-error');
            return;
        }

        if ($this->addSupplierLine($entry) &&
            $this->addPurchaseTaxLines($entry) &&
            $this->addPurchaseIrpfLines($entry) &&
            $this->addPurchaseSuppliedLines($entry) &&
            $this->addGoodsPurchaseLine($entry) &&
            $entry->isBalanced()) {
            $this->document->idasiento = $entry->primaryColumnValue();
            return;
        }

        ToolBox::i18nLog()->warning('accounting-lines-error');
        $entry->delete();
    }

    /**
     * Generate the accounting entry for a sales document.
     */
    protected function salesAccountingEntry()
    {
        $concept = ToolBox::i18n()->trans('customer-invoice') . ' ' . $this->document->codigo;
        $concept .= $this->document->numero2 ? ' (' . $this->document->numero2 . ') - ' . $this->document->nombrecliente :
            ' - ' . $this->document->nombrecliente;

        $entry = new Asiento();
        $this->setAccountingData($entry, $concept);
        if (false === $entry->save()) {
            ToolBox::i18nLog()->warning('accounting-entry-error');
            return;
        }

        if ($this->addCustomerLine($entry) &&
            $this->addSalesTaxLines($entry) &&
            $this->addSalesIrpfLines($entry) &&
            $this->addSalesSuppliedLines($entry) &&
            $this->addGoodsSalesLine($entry) &&
            $entry->isBalanced()) {
            $this->document->idasiento = $entry->primaryColumnValue();
            return;
        }

        ToolBox::i18nLog()->warning('accounting-lines-error');
        $entry->delete();
    }

    /**
     * Assign the document data to the accounting entry
     *
     * @param Asiento $entry
     * @param string $concept
     */
    protected function setAccountingData(Asiento &$entry, string $concept)
    {
        $entry->codejercicio = $this->document->codejercicio;
        $entry->concepto = $concept;
        $entry->documento = $this->document->codigo;
        $entry->fecha = $this->document->fecha;
        $entry->idempresa = $this->document->idempresa;
        $entry->importe = $this->document->total;

        // Assign analytical data defined in Serie model
        $serie = new Serie();
        $serie->loadFromCode($this->document->codserie);

        $entry->iddiario = $serie->iddiario;
        $entry->canal = $serie->canal;
    }
}
