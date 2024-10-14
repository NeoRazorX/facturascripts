<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\PagoCliente;
use FacturaScripts\Core\Model\PagoProveedor;
use FacturaScripts\Core\Model\ReciboCliente;
use FacturaScripts\Core\Model\ReciboProveedor;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Asiento as DinAsiento;
use FacturaScripts\Dinamic\Model\Ejercicio;

/**
 * Description of PaymentToAccounting
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class PaymentToAccounting
{
    /** @var Ejercicio */
    protected $exercise;

    /** @var PagoCliente|PagoProveedor */
    protected $payment;

    /** @var ReciboCliente|ReciboProveedor */
    protected $receipt;

    public function __construct()
    {
        $this->exercise = new Ejercicio();
    }

    /**
     * @param PagoCliente|PagoProveedor $payment
     * @return bool
     */
    public function generate($payment): bool
    {
        // comprobaciones iniciales
        switch ($payment->modelClassName()) {
            case 'PagoCliente':
            case 'PagoProveedor':
                $this->payment = $payment;
                $this->receipt = $payment->getReceipt();
                $this->exercise->idempresa = $this->receipt->idempresa;
                if (false === $this->exercise->loadFromDate($this->payment->fecha)) {
                    Tools::log()->warning('closed-exercise', [
                        '%exerciseName%' => $this->exercise->codejercicio
                    ]);
                    return false;
                }
                if (false === $this->exercise->hasAccountingPlan()) {
                    Tools::log()->warning('exercise-without-accounting-plan', [
                        '%exercise%' => $this->exercise->codejercicio
                    ]);
                    return false;
                }
                break;
        }

        switch ($payment->modelClassName()) {
            case 'PagoCliente':
                return $this->customerPaymentAccountingEntry();

            case 'PagoProveedor':
                return $this->supplierPaymentAccountingEntry();
        }

        return false;
    }

    protected function customerPaymentAccountingEntry(): bool
    {
        // creamos el asiento
        $entry = new DinAsiento();

        $concept = $this->payment->importe > 0 ?
            Tools::lang()->trans('customer-payment-concept', ['%document%' => $this->receipt->getCode()]) :
            Tools::lang()->trans('refund-payment-concept', ['%document%' => $this->receipt->getCode()]);

        $invoice = $this->receipt->getInvoice();
        $concept .= $invoice->numero2 ?
            ' (' . $invoice->numero2 . ') - ' . $invoice->nombrecliente :
            ' - ' . $invoice->nombrecliente;

        $this->setCommonData($entry, $concept, $invoice);
        $entry->importe += $this->payment->gastos;
        if (false === $entry->save()) {
            Tools::log()->warning('accounting-entry-error');
            return false;
        }

        // Add lines and save accounting entry relation
        if ($this->customerPaymentLine($entry)
            && $this->customerPaymentBankLine($entry)
            && $this->customerPaymentExpenseLine($entry)
            && $entry->isBalanced()) {
            $this->payment->idasiento = $entry->primaryColumnValue();
            return true;
        }

        Tools::log()->warning('accounting-lines-error');
        $entry->delete();
        return false;
    }

    protected function customerPaymentBankLine(Asiento &$entry): bool
    {
        $account = $this->payment->getPaymentMethod()->getSubcuenta($this->exercise->codejercicio, true);
        if (false === $account->exists()) {
            return false;
        }

        $amount = $this->payment->importe + abs($this->payment->gastos);

        $newLine = $entry->getNewLine($account);
        $newLine->debe = max($amount, 0);
        $newLine->haber = $amount < 0 ? abs($amount) : 0;
        return $newLine->save();
    }

    protected function customerPaymentExpenseLine(Asiento &$entry): bool
    {
        if (empty($this->payment->gastos)) {
            return true;
        }

        $account = $this->payment->getPaymentMethod()->getSubcuentaGastos($this->exercise->codejercicio, true);

        $expLine = $entry->getNewLine($account);
        $expLine->concepto = Tools::lang()->trans('receipt-expense-account', ['%document%' => $entry->documento]);
        $expLine->haber = abs($this->payment->gastos);
        return $expLine->save();
    }

    protected function customerPaymentLine(Asiento &$entry): bool
    {
        $account = $this->receipt->getSubject()->getSubcuenta($this->exercise->codejercicio, true);
        if (false === $account->exists()) {
            return false;
        }

        $newLine = $entry->getNewLine($account);
        $newLine->debe = $this->payment->importe < 0 ? abs($this->payment->importe) : 0;
        $newLine->haber = max($this->payment->importe, 0);
        return $newLine->save();
    }

    protected function supplierPaymentAccountingEntry(): bool
    {
        // Create account entry header
        $entry = new DinAsiento();

        $concept = $this->payment->importe > 0 ?
            Tools::lang()->trans('supplier-payment-concept', ['%document%' => $this->receipt->getCode()]) :
            Tools::lang()->trans('refund-payment-concept', ['%document%' => $this->receipt->getCode()]);

        $invoice = $this->receipt->getInvoice();
        $concept .= $invoice->numproveedor ?
            ' (' . $invoice->numproveedor . ') - ' . $invoice->nombre :
            ' - ' . $invoice->nombre;

        $this->setCommonData($entry, $concept, $invoice);
        if (false === $entry->save()) {
            Tools::log()->warning('accounting-entry-error');
            return false;
        }

        // Add lines and save accounting entry relation
        if ($this->supplierPaymentLine($entry)
            && $this->supplierPaymentBankLine($entry)
            && $entry->isBalanced()) {
            $this->payment->idasiento = $entry->primaryColumnValue();
            return true;
        }

        Tools::log()->warning('accounting-lines-error');
        $entry->delete();
        return false;
    }

    protected function supplierPaymentBankLine(Asiento &$entry): bool
    {
        $account = $this->payment->getPaymentMethod()->getSubcuenta($this->exercise->codejercicio, true);
        if (false === $account->exists()) {
            return false;
        }

        $newLine = $entry->getNewLine($account);
        $newLine->debe = $this->payment->importe < 0 ? abs($this->payment->importe) : 0;
        $newLine->haber = max($this->payment->importe, 0);
        return $newLine->save();
    }

    protected function supplierPaymentLine(Asiento &$entry): bool
    {
        $account = $this->receipt->getSubject()->getSubcuenta($this->exercise->codejercicio, true);
        if (false === $account->exists()) {
            return false;
        }

        $newLine = $entry->getNewLine($account);
        $newLine->debe = max($this->payment->importe, 0);
        $newLine->haber = $this->payment->importe < 0 ? abs($this->payment->importe) : 0;
        return $newLine->save();
    }

    protected function setCommonData(Asiento &$entry, string $concept, $invoice): void
    {
        $entry->codejercicio = $this->exercise->codejercicio;
        $entry->concepto = $concept;
        $entry->documento = $invoice->codigo;
        $entry->canal = $invoice->getSerie()->canal;
        $entry->fecha = $this->payment->fecha;
        $entry->idempresa = $this->exercise->idempresa;
        $entry->importe = $this->payment->importe;
    }
}
