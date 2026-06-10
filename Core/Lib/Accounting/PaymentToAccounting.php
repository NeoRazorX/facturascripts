<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Genera el asiento contable de un pago de cliente o proveedor: línea del sujeto
 * (cliente/proveedor) contra la subcuenta de la forma de pago, más una línea
 * adicional de gastos bancarios en los pagos de cliente.
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
     * Lanza la contabilización del pago: carga el ejercicio a partir de la fecha del
     * pago, valida que esté abierto y con plan, y delega en el flujo de cliente o
     * proveedor según el tipo.
     *
     * @param PagoCliente|PagoProveedor $payment
     * @return bool
     */
    public function generate($payment): bool
    {
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

    /**
     * Genera el asiento de un pago de cliente: subcuenta del cliente contra la
     * subcuenta bancaria de la forma de pago, más una línea de gastos bancarios si
     * el pago los lleva. Borra el asiento si alguna línea falla o queda descuadrado.
     */
    protected function customerPaymentAccountingEntry(): bool
    {
        $entry = new DinAsiento();

        $concept = $this->payment->importe > 0 ?
            Tools::trans('customer-payment-concept', ['%document%' => $this->receipt->getCode()]) :
            Tools::trans('refund-payment-concept', ['%document%' => $this->receipt->getCode()]);

        $invoice = $this->receipt->getInvoice();
        $concept .= $invoice->numero2 ?
            ' (' . $invoice->numero2 . ') - ' . $invoice->nombrecliente :
            ' - ' . $invoice->nombrecliente;

        $this->setCommonData($entry, $concept, $invoice);
        $entry->importe += $this->payment->gastos;
        if (false === $entry->save()) {
            Tools::log()->warning('accounting-entry-error', ['%document%' => $this->receipt->getCode()]);
            return false;
        }

        if (false === $this->customerPaymentLine($entry)) {
            Tools::log()->warning('customer-payment-line-error', [
                '%receipt%' => $this->receipt->getCode()
            ]);
            $entry->delete();
            return false;
        }

        if (false === $this->customerPaymentBankLine($entry)) {
            Tools::log()->warning('customer-payment-bank-line-error', [
                '%receipt%' => $this->receipt->getCode(),
                '%paymentMethod%' => $this->payment->getPaymentMethod()->descripcion
            ]);
            $entry->delete();
            return false;
        }

        if (false === $this->customerPaymentExpenseLine($entry)) {
            Tools::log()->warning('customer-payment-expense-line-error', [
                '%receipt%' => $this->receipt->getCode()
            ]);
            $entry->delete();
            return false;
        }

        if (false === $entry->isBalanced()) {
            Tools::log()->warning('unbalanced-accounting-entry', [
                '%document%' => $entry->documento,
                '%difference%' => abs($entry->debe - $entry->haber)
            ]);
            $entry->delete();
            return false;
        }

        $this->payment->idasiento = $entry->id();
        return true;
    }

    /**
     * Añade la línea bancaria del pago de cliente cargando en la subcuenta de la
     * forma de pago el importe del pago más los gastos bancarios.
     */
    protected function customerPaymentBankLine(Asiento &$entry): bool
    {
        $account = $this->payment->getPaymentMethod()->getSubcuenta($this->exercise->codejercicio, true);
        if (false === $account->exists()) {
            Tools::log()->warning('payment-method-account-not-found', [
                '%paymentMethod%' => $this->payment->getPaymentMethod()->descripcion,
                '%exercise%' => $this->exercise->codejercicio
            ]);
            return false;
        }

        $amount = $this->payment->importe + abs($this->payment->gastos);

        $newLine = $entry->getNewLine($account);
        $newLine->debe = max($amount, 0);
        $newLine->haber = $amount < 0 ? abs($amount) : 0;
        return $newLine->save();
    }

    /**
     * Añade la línea de gastos bancarios del pago, abonando la subcuenta de gastos
     * configurada en la forma de pago. Si el pago no tiene gastos, no hace nada.
     */
    protected function customerPaymentExpenseLine(Asiento &$entry): bool
    {
        if (empty($this->payment->gastos)) {
            return true;
        }

        $account = $this->payment->getPaymentMethod()->getSubcuentaGastos($this->exercise->codejercicio, true);
        if (false === $account->exists()) {
            Tools::log()->warning('payment-expense-account-not-found', [
                '%paymentMethod%' => $this->payment->getPaymentMethod()->descripcion,
                '%exercise%' => $this->exercise->codejercicio,
                '%amount%' => $this->payment->gastos
            ]);
            return false;
        }

        $expLine = $entry->getNewLine($account);
        $expLine->concepto = Tools::trans('receipt-expense-account', ['%document%' => $entry->documento]);
        $expLine->haber = abs($this->payment->gastos);
        return $expLine->save();
    }

    /**
     * Añade la línea del cliente abonando su subcuenta por el importe del pago
     * (cargo si el importe es negativo, abono si es positivo).
     */
    protected function customerPaymentLine(Asiento &$entry): bool
    {
        $account = $this->receipt->getSubject()->getSubcuenta($this->exercise->codejercicio, true);
        if (false === $account->exists()) {
            Tools::log()->warning('customer-account-not-found', [
                '%customer%' => $this->receipt->getSubject()->codcliente,
                '%exercise%' => $this->exercise->codejercicio
            ]);
            return false;
        }

        $newLine = $entry->getNewLine($account);
        $newLine->debe = $this->payment->importe < 0 ? abs($this->payment->importe) : 0;
        $newLine->haber = max($this->payment->importe, 0);
        return $newLine->save();
    }

    /**
     * Genera el asiento de un pago de proveedor: subcuenta del proveedor contra la
     * subcuenta bancaria de la forma de pago. Borra el asiento si alguna línea falla
     * o queda descuadrado.
     */
    protected function supplierPaymentAccountingEntry(): bool
    {
        $entry = new DinAsiento();

        $concept = $this->payment->importe > 0 ?
            Tools::trans('supplier-payment-concept', ['%document%' => $this->receipt->getCode()]) :
            Tools::trans('refund-payment-concept', ['%document%' => $this->receipt->getCode()]);

        $invoice = $this->receipt->getInvoice();
        $concept .= $invoice->numproveedor ?
            ' (' . $invoice->numproveedor . ') - ' . $invoice->nombre :
            ' - ' . $invoice->nombre;

        $this->setCommonData($entry, $concept, $invoice);
        if (false === $entry->save()) {
            Tools::log()->warning('accounting-entry-error', ['%document%' => $this->receipt->getCode()]);
            return false;
        }

        if (false === $this->supplierPaymentLine($entry)) {
            Tools::log()->warning('supplier-payment-line-error', [
                '%receipt%' => $this->receipt->getCode()
            ]);
            $entry->delete();
            return false;
        }

        if (false === $this->supplierPaymentBankLine($entry)) {
            Tools::log()->warning('supplier-payment-bank-line-error', [
                '%receipt%' => $this->receipt->getCode(),
                '%paymentMethod%' => $this->payment->getPaymentMethod()->descripcion
            ]);
            $entry->delete();
            return false;
        }

        if (false === $entry->isBalanced()) {
            Tools::log()->warning('unbalanced-accounting-entry', [
                '%document%' => $entry->documento,
                '%difference%' => abs($entry->debe - $entry->haber)
            ]);
            $entry->delete();
            return false;
        }

        $this->payment->idasiento = $entry->id();
        return true;
    }

    /**
     * Añade la línea bancaria del pago de proveedor abonando la subcuenta de la
     * forma de pago por el importe del pago.
     */
    protected function supplierPaymentBankLine(Asiento &$entry): bool
    {
        $account = $this->payment->getPaymentMethod()->getSubcuenta($this->exercise->codejercicio, true);
        if (false === $account->exists()) {
            Tools::log()->warning('payment-method-account-not-found', [
                '%paymentMethod%' => $this->payment->getPaymentMethod()->descripcion,
                '%exercise%' => $this->exercise->codejercicio
            ]);
            return false;
        }

        $newLine = $entry->getNewLine($account);
        $newLine->debe = $this->payment->importe < 0 ? abs($this->payment->importe) : 0;
        $newLine->haber = max($this->payment->importe, 0);
        return $newLine->save();
    }

    /**
     * Añade la línea del proveedor cargando su subcuenta por el importe del pago
     * (abono si el importe es negativo, cargo si es positivo).
     */
    protected function supplierPaymentLine(Asiento &$entry): bool
    {
        $account = $this->receipt->getSubject()->getSubcuenta($this->exercise->codejercicio, true);
        if (false === $account->exists()) {
            Tools::log()->warning('supplier-account-not-found', [
                '%supplier%' => $this->receipt->getSubject()->codproveedor,
                '%exercise%' => $this->exercise->codejercicio
            ]);
            return false;
        }

        $newLine = $entry->getNewLine($account);
        $newLine->debe = max($this->payment->importe, 0);
        $newLine->haber = $this->payment->importe < 0 ? abs($this->payment->importe) : 0;
        return $newLine->save();
    }

    /**
     * Rellena los datos comunes del asiento (ejercicio, concepto, documento, fecha,
     * empresa, importe) y copia el canal analítico desde la serie de la factura.
     */
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
