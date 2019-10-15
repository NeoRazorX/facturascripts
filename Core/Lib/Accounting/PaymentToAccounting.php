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
namespace FacturaScripts\Core\Lib\Accounting;

use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;
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

        /// initial checks
        switch ($model->modelClassName()) {
            case 'PagoCliente':
            case 'PagoProveedor':
                $this->receipt = $this->document->getReceipt();
                $this->exercise->idempresa = $this->receipt->idempresa;
                if (!$this->exercise->loadFromDate($this->document->fecha)) {
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
        /// Get Subaccounts
        $customer = $this->receipt->getSubject();
        $customerSubaccount = $this->getCustomerAccount($customer);
        $paymentSubaccount = $this->getPaymentAccount($this->document->codpago);
        if (!$customerSubaccount->exists() || !$paymentSubaccount->exists()) {
            return false;
        }

        /// Create account entry header
        $accountEntry = new Asiento();
        $concept = $this->toolBox()->i18n()->trans('receipt-payment-concept', ['%document%' => $this->receipt->getCode()]);
        $this->setCommonData($accountEntry, $concept);
        $accountEntry->importe += $this->document->gastos;
        if (!$accountEntry->save()) {
            $this->toolBox()->i18nLog()->warning('accounting-entry-error');
            return false;
        }

        /// Create account entry detail
        /// Add Customer Receipt Amount Line
        $line = $accountEntry->getNewLine();
        $this->setCommonDataLine($line, $customerSubaccount, $paymentSubaccount, $concept);
        $line->documento = $accountEntry->documento;
        $line->haber = $this->document->importe;
        $line->save();

        /// Add Expense Amount Line
        if ($this->document->gastos != 0) {
            $concept2 = $this->toolBox()->i18n()->trans('receipt-expense-account', ['%document%' => $accountEntry->documento]);
            $this->setCommonDataLine($line, $customerSubaccount, $paymentSubaccount, $concept2);
            $line->haber = $this->document->gastos;
            $line->save();
        }

        /// Add Cash/Bank Import Line
        $this->setCommonDataLine($line, $paymentSubaccount, $customerSubaccount, $concept);
        $line->debe = $this->document->importe + $this->document->gastos;
        $line->save();

        /// Save account id relation
        $this->document->idasiento = $accountEntry->idasiento;
        if ($this->document->save()) {
            return true;
        }

        $accountEntry->delete();
        return false;
    }

    protected function supplierPaymentAccountingEntry()
    {
        /// Get Subaccounts
        $supplier = $this->receipt->getSubject();
        $supplierSubaccount = $this->getSupplierAccount($supplier);
        $paymentSubaccount = $this->getPaymentAccount($this->document->codpago);
        if (!$supplierSubaccount->exists() || !$paymentSubaccount->exists()) {
            return false;
        }

        /// Create account entry header
        $accountEntry = new Asiento();
        $concept = $this->toolBox()->i18n()->trans('receipt-payment-concept', ['%document%' => $this->receipt->getCode()]);
        $this->setCommonData($accountEntry, $concept);
        if (!$accountEntry->save()) {
            $this->toolBox()->i18nLog()->warning('accounting-entry-error');
            return false;
        }

        /// Create account entry detail
        /// Add Customer Receipt Amount Line
        $line = $accountEntry->getNewLine();
        $this->setCommonDataLine($line, $supplierSubaccount, $paymentSubaccount, $concept);
        $line->documento = $accountEntry->documento;
        $line->debe = $this->document->importe;
        $line->save();

        /// Add Cash/Bank Import Line
        $this->setCommonDataLine($line, $paymentSubaccount, $supplierSubaccount, $concept);
        $line->haber = $this->document->importe;
        $line->save();

        /// Save account id relation
        $this->document->idasiento = $accountEntry->idasiento;
        if ($this->document->save()) {
            return true;
        }

        $accountEntry->delete();
        return false;
    }

    /**
     * Establishes the common data of the accounting entry
     *
     * @param Asiento $accountEntry
     * @param string  $concept
     */
    protected function setCommonData(&$entry, string $concept)
    {
        $entry->codejercicio = $this->exercise->codejercicio;
        $entry->concepto = $concept;
        $entry->editable = false;
        $entry->documento = $this->receipt->getInvoice()->codigo;
        $entry->fecha = $this->document->fecha;
        $entry->idempresa = $this->exercise->idempresa;
        $entry->importe = $this->document->importe;
    }

    /**
     * Establishes the common data of the entries of the accounting entry
     *
     * @param Partida   $line
     * @param Subcuenta $account
     * @param Subcuenta $offsetting
     * @param string    $concept
     */
    protected function setCommonDataLine(&$line, $account, $offsetting, string $concept)
    {
        $line->concepto = $concept;
        $line->idsubcuenta = $account->idsubcuenta;
        $line->codsubcuenta = $account->codsubcuenta;
        $line->debe = 0.00;
        $line->haber = 0.00;
        $line->idpartida = null; // Force insert new line

        if ($offsetting !== null) {
            $line->idcontrapartida = $offsetting->idsubcuenta;
            $line->codcontrapartida = $offsetting->codsubcuenta;
        } else {
            $line->idcontrapartida = null;
            $line->codcontrapartida = null;
        }
    }
}
