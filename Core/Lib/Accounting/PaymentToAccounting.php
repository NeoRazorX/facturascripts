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
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\PagoCliente;
use FacturaScripts\Dinamic\Model\PagoProveedor;
use FacturaScripts\Dinamic\Model\ReciboCliente;
use FacturaScripts\Dinamic\Model\ReciboProveedor;

/**
 * Description of PaymentToAccounting
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class PaymentToAccounting extends AccountingClass
{

    /**
     *
     * @var PagoCliente|PagoProveedor
     */
    protected $document;

    /**
     *
     * @var ReciboCliente|ReciboProveedor
     */
    protected $receipt;

    /**
     * 
     * @param PagoCliente|PagoProveedor $model
     */
    public function generate($model)
    {
        parent::generate($model);

        /// Initial checks
        switch ($model->modelClassName()) {
            case 'PagoCliente':
            case 'PagoProveedor':
                $this->receipt = $this->document->getReceipt();
                $this->exercise->idempresa = $this->receipt->idempresa;
                if (false === $this->exercise->loadFromDate($this->document->fecha)) {
                    $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->exercise->codejercicio]);
                    return;
                }
                break;
        }

        switch ($model->modelClassName()) {
            case 'PagoCliente':
                $this->customerPaymentAccountingEntry();
                break;

            case 'PagoProveedor':
                $this->supplierPaymentAccountingEntry();
                break;
        }
    }

    /**
     * 
     * @return bool
     */
    protected function customerPaymentAccountingEntry()
    {
        /// Create account entry header
        $accEntry = new Asiento();
        $concept = $this->toolBox()->i18n()->trans('customer-payment-concept', ['%document%' => $this->receipt->getCode()]);

        $invoice = $this->receipt->getInvoice();
        $concept .= $invoice->numero2 ?
            ' (' . $invoice->numero2 . ') - ' . $invoice->nombrecliente :
            ' - ' . $invoice->nombrecliente;

        $this->setCommonData($accEntry, $concept);
        $accEntry->importe += $this->document->gastos;
        if (false === $accEntry->save()) {
            $this->toolBox()->i18nLog()->warning('accounting-entry-error');
            return false;
        }

        /// Add lines and save accounting entry relation
        if ($this->customerPaymentLine($accEntry) && $this->customerPaymentBankLine($accEntry) && $accEntry->isBalanced()) {
            $this->document->idasiento = $accEntry->primaryColumnValue();
            return true;
        }

        $this->toolBox()->i18nLog()->warning('accounting-lines-error');
        $accEntry->delete();
        return false;
    }

    /**
     * 
     * @param Asiento $accEntry
     *
     * @return bool
     */
    protected function customerPaymentBankLine(&$accEntry)
    {
        $paymentSubaccount = $this->getPaymentAccount($this->document->codpago);
        if (false === $paymentSubaccount->exists()) {
            return false;
        }

        $newLine = $accEntry->getNewLine();
        $newLine->setAccount($paymentSubaccount);
        $newLine->debe = $this->document->importe + $this->document->gastos;
        return $newLine->save();
    }

    /**
     * 
     * @param Asiento $accEntry
     *
     * @return bool
     */
    protected function customerPaymentLine(&$accEntry)
    {
        $customer = $this->receipt->getSubject();
        $customerSubaccount = $this->getCustomerAccount($customer);
        if (false === $customerSubaccount->exists()) {
            return false;
        }

        $newLine = $accEntry->getNewLine();
        $newLine->setAccount($customerSubaccount);
        $newLine->haber = $this->document->importe;
        if (false === $newLine->save()) {
            return false;
        }

        /// Add Expense Amount Line
        if ($this->document->gastos != 0) {
            $expLine = $accEntry->getNewLine();
            $expLine->setAccount($customerSubaccount);
            $expLine->concepto = $this->toolBox()->i18n()->trans('receipt-expense-account', ['%document%' => $accEntry->documento]);
            $expLine->haber = $this->document->gastos;
            return $expLine->save();
        }

        return true;
    }

    protected function supplierPaymentAccountingEntry()
    {
        /// Create account entry header
        $accEntry = new Asiento();
        $concept = $this->toolBox()->i18n()->trans('supplier-payment-concept', ['%document%' => $this->receipt->getCode()]);

        $invoice = $this->receipt->getInvoice();
        $concept .= $invoice->numproveedor ?
            ' (' . $invoice->numproveedor . ') - ' . $invoice->nombre :
            ' - ' . $invoice->nombre;

        $this->setCommonData($accEntry, $concept);
        if (false === $accEntry->save()) {
            $this->toolBox()->i18nLog()->warning('accounting-entry-error');
            return false;
        }

        /// Add lines and save accounting entry relation
        if ($this->supplierPaymentLine($accEntry) && $this->supplierPaymentBankLine($accEntry) && $accEntry->isBalanced()) {
            $this->document->idasiento = $accEntry->primaryColumnValue();
            return true;
        }

        $this->toolBox()->i18nLog()->warning('accounting-lines-error');
        $accEntry->delete();
        return false;
    }

    /**
     * 
     * @param Asiento $accEntry
     *
     * @return bool
     */
    protected function supplierPaymentBankLine(&$accEntry)
    {
        $paymentSubaccount = $this->getPaymentAccount($this->document->codpago);
        if (false === $paymentSubaccount->exists()) {
            return false;
        }

        $newLine = $accEntry->getNewLine();
        $newLine->setAccount($paymentSubaccount);
        $newLine->haber = $this->document->importe;
        return $newLine->save();
    }

    /**
     * 
     * @param Asiento $accEntry
     *
     * @return bool
     */
    protected function supplierPaymentLine(&$accEntry)
    {
        $supplier = $this->receipt->getSubject();
        $supplierSubaccount = $this->getSupplierAccount($supplier);
        if (false === $supplierSubaccount->exists()) {
            return false;
        }

        $newLine = $accEntry->getNewLine();
        $newLine->setAccount($supplierSubaccount);
        $newLine->debe = $this->document->importe;
        return $newLine->save();
    }

    /**
     * Establishes the common data of the accounting entry
     *
     * @param Asiento $accEntry
     * @param string  $concept
     */
    protected function setCommonData(&$accEntry, string $concept)
    {
        $accEntry->codejercicio = $this->exercise->codejercicio;
        $accEntry->concepto = $concept;
        $accEntry->documento = $this->receipt->getInvoice()->codigo;
        $accEntry->fecha = $this->document->fecha;
        $accEntry->idempresa = $this->exercise->idempresa;
        $accEntry->importe = $this->document->importe;
    }
}
