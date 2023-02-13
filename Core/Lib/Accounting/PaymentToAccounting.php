<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\PagoCliente;
use FacturaScripts\Core\Model\PagoProveedor;
use FacturaScripts\Core\Model\ReciboCliente;
use FacturaScripts\Core\Model\ReciboProveedor;
use FacturaScripts\Dinamic\Model\Asiento as DinAsiento;

/**
 * Description of PaymentToAccounting
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class PaymentToAccounting extends AccountingClass
{
    /**
     * @var PagoCliente|PagoProveedor
     */
    protected $document;

    /**
     * @var ReciboCliente|ReciboProveedor
     */
    protected $receipt;

    /**
     * @param PagoCliente|PagoProveedor $model
     */
    public function generate($model)
    {
        parent::generate($model);

        // Initial checks
        switch ($model->modelClassName()) {
            case 'PagoCliente':
            case 'PagoProveedor':
                $this->receipt = $this->document->getReceipt();
                $this->exercise->idempresa = $this->receipt->idempresa;
                if (false === $this->exercise->loadFromDate($this->document->fecha)) {
                    ToolBox::i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->exercise->codejercicio]);
                    return;
                }
                if (false === $this->exercise->hasAccountingPlan()) {
                    ToolBox::i18nLog()->warning('exercise-without-accounting-plan', ['%exercise%' => $this->exercise->codejercicio]);
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

    protected function customerPaymentAccountingEntry(): bool
    {
        // Create account entry header
        $accEntry = new DinAsiento();
        $concept = ToolBox::i18n()->trans('customer-payment-concept', ['%document%' => $this->receipt->getCode()]);

        $invoice = $this->receipt->getInvoice();
        $concept .= $invoice->numero2 ?
            ' (' . $invoice->numero2 . ') - ' . $invoice->nombrecliente :
            ' - ' . $invoice->nombrecliente;

        $this->setCommonData($accEntry, $concept);
        $accEntry->importe += $this->document->gastos;
        if (false === $accEntry->save()) {
            ToolBox::i18nLog()->warning('accounting-entry-error');
            return false;
        }

        // Add lines and save accounting entry relation
        if ($this->customerPaymentLine($accEntry) &&
            $this->customerPaymentBankLine($accEntry) &&
            $this->customerPaymentExpenseLine($accEntry) &&
            $accEntry->isBalanced()) {
            $this->document->idasiento = $accEntry->primaryColumnValue();
            return true;
        }

        ToolBox::i18nLog()->warning('accounting-lines-error');
        $accEntry->delete();
        return false;
    }

    protected function customerPaymentBankLine(Asiento &$accEntry): bool
    {
        $paymentSubAccount = $this->getPaymentAccount($this->document->codpago ?? '');
        if (false === $paymentSubAccount->exists()) {
            return false;
        }

        $newLine = $accEntry->getNewLine();
        $newLine->setAccount($paymentSubAccount);
        $newLine->debe = $this->document->importe + $this->document->gastos;
        return $newLine->save();
    }

    protected function customerPaymentExpenseLine(Asiento &$accEntry): bool
    {
        if (empty($this->document->gastos)) {
            return true;
        }

        $expLine = $accEntry->getNewLine();
        $subAccountExpense = $this->getExpenseAccount($this->document->codpago ?? '');
        $expLine->setAccount($subAccountExpense);
        $expLine->concepto = ToolBox::i18n()->trans('receipt-expense-account', ['%document%' => $accEntry->documento]);
        $expLine->haber = $this->document->gastos;
        return $expLine->save();
    }

    protected function customerPaymentLine(Asiento &$accEntry): bool
    {
        $customer = $this->receipt->getSubject();
        $customerSubAccount = $this->getCustomerAccount($customer);
        if (false === $customerSubAccount->exists()) {
            return false;
        }

        $newLine = $accEntry->getNewLine();
        $newLine->setAccount($customerSubAccount);
        $newLine->haber = $this->document->importe;
        return $newLine->save();
    }

    protected function supplierPaymentAccountingEntry(): bool
    {
        // Create account entry header
        $accEntry = new DinAsiento();
        $concept = ToolBox::i18n()->trans('supplier-payment-concept', ['%document%' => $this->receipt->getCode()]);

        $invoice = $this->receipt->getInvoice();
        $concept .= $invoice->numproveedor ?
            ' (' . $invoice->numproveedor . ') - ' . $invoice->nombre :
            ' - ' . $invoice->nombre;

        $this->setCommonData($accEntry, $concept);
        if (false === $accEntry->save()) {
            ToolBox::i18nLog()->warning('accounting-entry-error');
            return false;
        }

        // Add lines and save accounting entry relation
        if ($this->supplierPaymentLine($accEntry) && $this->supplierPaymentBankLine($accEntry) && $accEntry->isBalanced()) {
            $this->document->idasiento = $accEntry->primaryColumnValue();
            return true;
        }

        ToolBox::i18nLog()->warning('accounting-lines-error');
        $accEntry->delete();
        return false;
    }

    protected function supplierPaymentBankLine(Asiento &$accEntry): bool
    {
        $paymentSubAccount = $this->getPaymentAccount($this->document->codpago ?? '');
        if (false === $paymentSubAccount->exists()) {
            return false;
        }

        $newLine = $accEntry->getNewLine();
        $newLine->setAccount($paymentSubAccount);
        $newLine->haber = $this->document->importe;
        return $newLine->save();
    }

    protected function supplierPaymentLine(Asiento &$accEntry): bool
    {
        $supplier = $this->receipt->getSubject();
        $supplierSubAccount = $this->getSupplierAccount($supplier);
        if (false === $supplierSubAccount->exists()) {
            return false;
        }

        $newLine = $accEntry->getNewLine();
        $newLine->setAccount($supplierSubAccount);
        $newLine->debe = $this->document->importe;
        return $newLine->save();
    }

    /**
     * Establishes the common data of the accounting entry
     *
     * @param Asiento $accEntry
     * @param string $concept
     */
    protected function setCommonData(Asiento &$accEntry, string $concept): void
    {
        $invoice = $this->receipt->getInvoice();
        $accEntry->codejercicio = $this->exercise->codejercicio;
        $accEntry->concepto = $concept;
        $accEntry->documento = $invoice->codigo;
        $accEntry->canal = $invoice->getSerie()->canal;
        $accEntry->fecha = $this->document->fecha;
        $accEntry->idempresa = $this->exercise->idempresa;
        $accEntry->importe = $this->document->importe;
    }
}
