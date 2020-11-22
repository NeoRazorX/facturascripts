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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\ReciboCliente;
use FacturaScripts\Dinamic\Model\ReciboProveedor;

/**
 * Description of ReceiptGenerator
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ReceiptGenerator
{

    const MAX_RECEIPTS = 100;
    const PARTIAL_AMOUNT_MULTIPLIER = 1.5;

    /**
     * 
     * @param FacturaCliente|FacturaProveedor $invoice
     * @param int                             $number
     *
     * @return bool
     */
    public function generate($invoice, $number = 0)
    {
        switch ($invoice->modelClassName()) {
            case 'FacturaCliente':
                return empty($number) ? $this->updateCustomerReceipts($invoice) : $this->generateCustomerReceipts($invoice, $number);

            case 'FacturaProveedor':
                return empty($number) ? $this->updateSupplierReceipts($invoice) : $this->generateSupplierReceipts($invoice, $number);
        }

        return false;
    }

    /**
     * 
     * @param FacturaCliente|FacturaProveedor $invoice
     */
    public function update(&$invoice)
    {
        /// check current invoice receipts
        $receipts = $invoice->getReceipts();

        $paidAmount = 0.0;
        foreach ($receipts as $receipt) {
            if ($receipt->pagado) {
                $paidAmount += $receipt->importe;
            }
        }

        $invoice->pagada = $this->isCero($invoice->total - $paidAmount);
    }

    /**
     * 
     * @param FacturaCliente $invoice
     * @param int            $number
     *
     * @return bool
     */
    protected function generateCustomerReceipts($invoice, $number)
    {
        /// check current invoice receipts
        $receipts = $invoice->getReceipts();

        /// calculate outstanding amount
        $amount = $this->getOutstandingAmount($receipts, $invoice->total);
        if (empty($amount)) {
            $this->toolBox()->i18nLog()->warning('no-outstanding-amount');
            return false;
        }

        /// calculate new receipt number
        $newNum = 1;
        foreach ($receipts as $receipt) {
            if ($receipt->numero >= $newNum) {
                $newNum = 1 + $receipt->numero;
            }
        }

        /// create new receipts
        $partialAmount = $number > 1 ? \round($amount / $number, \FS_NF0) : $amount;
        while (!$this->isCero($amount) || $newNum > self::MAX_RECEIPTS) {
            $receiptAmount = $amount > self::PARTIAL_AMOUNT_MULTIPLIER * $partialAmount ? $partialAmount : $amount;
            if (!$this->newCustomerReceipt($invoice, $newNum, $receiptAmount)) {
                return false;
            }

            $amount -= $receiptAmount;
            $newNum++;
        }

        return true;
    }

    /**
     * 
     * @param FacturaProveedor $invoice
     * @param int              $number
     *
     * @return bool
     */
    protected function generateSupplierReceipts($invoice, $number)
    {
        /// check current invoice receipts
        $receipts = $invoice->getReceipts();

        /// calculate outstanding amount
        $amount = $this->getOutstandingAmount($receipts, $invoice->total);
        if (empty($amount)) {
            $this->toolBox()->i18nLog()->warning('no-outstanding-amount');
            return false;
        }

        /// calculate new receipt number
        $newNum = 1;
        foreach ($receipts as $receipt) {
            if ($receipt->numero >= $newNum) {
                $newNum = 1 + $receipt->numero;
            }
        }

        /// create new receipts
        $partialAmount = $number > 1 ? \round($amount / $number, \FS_NF0) : $amount;
        while (!$this->isCero($amount) || $newNum > self::MAX_RECEIPTS) {
            $receiptAmount = $amount > self::PARTIAL_AMOUNT_MULTIPLIER * $partialAmount ? $partialAmount : $amount;
            if (!$this->newSupplierReceipt($invoice, $newNum, $receiptAmount)) {
                return false;
            }

            $amount -= $receiptAmount;
            $newNum++;
        }

        return true;
    }

    /**
     * 
     * @param ReciboCliente[]|ReciboProveedor[] $receipts
     * @param float                             $amount
     *
     * @return float
     */
    protected function getOutstandingAmount($receipts, $amount)
    {
        $pending = $amount;
        foreach ($receipts as $receipt) {
            $pending -= $receipt->importe;
        }

        return \round($pending, \FS_NF0);
    }

    /**
     * Returns TRUE if $amount is cero.
     * 
     * @param float $amount
     *
     * @return bool
     */
    protected function isCero($amount)
    {
        return $this->toolBox()->utils()->floatcmp($amount, 0.0, \FS_NF0, true);
    }

    /**
     * 
     * @param FacturaCliente $invoice
     * @param int            $number
     * @param float          $amount
     *
     * @return bool
     */
    protected function newCustomerReceipt($invoice, $number, $amount)
    {
        $newReceipt = new ReciboCliente();
        $newReceipt->codcliente = $invoice->codcliente;
        $newReceipt->coddivisa = $invoice->coddivisa;
        $newReceipt->idempresa = $invoice->idempresa;
        $newReceipt->idfactura = $invoice->idfactura;
        $newReceipt->importe = $amount;
        $newReceipt->nick = $invoice->nick;
        $newReceipt->numero = $number;
        $newReceipt->fecha = $invoice->fecha;
        $newReceipt->setPaymentMethod($invoice->codpago);
        $newReceipt->disableInvoiceUpdate(true);
        return $newReceipt->save();
    }

    /**
     * 
     * @param FacturaProveedor $invoice
     * @param int              $number
     * @param float            $amount
     *
     * @return bool
     */
    protected function newSupplierReceipt($invoice, $number, $amount)
    {
        $newReceipt = new ReciboProveedor();
        $newReceipt->codproveedor = $invoice->codproveedor;
        $newReceipt->coddivisa = $invoice->coddivisa;
        $newReceipt->idempresa = $invoice->idempresa;
        $newReceipt->idfactura = $invoice->idfactura;
        $newReceipt->importe = $amount;
        $newReceipt->nick = $invoice->nick;
        $newReceipt->numero = $number;
        $newReceipt->setPaymentMethod($invoice->codpago);
        $newReceipt->disableInvoiceUpdate(true);
        return $newReceipt->save();
    }

    /**
     * 
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
    }

    /**
     * 
     * @param FacturaCliente $invoice
     *
     * @return bool
     */
    protected function updateCustomerReceipts($invoice)
    {
        /// check current invoice receipts
        $receipts = $invoice->getReceipts();

        /// calculate outstanding amount
        $amount = $this->getOutstandingAmount($receipts, $invoice->total);

        /// calculate new receipt number
        $newNum = 1;
        foreach ($receipts as $receipt) {
            /// try to update open receipts
            if ($receipt->pagado === false) {
                $receipt->importe += $amount;
                $receipt->save();
                return true;
            }

            if ($receipt->numero >= $newNum) {
                $newNum = 1 + $receipt->numero;
            }
        }

        /// create new receipt
        return $this->newCustomerReceipt($invoice, $newNum, $amount);
    }

    /**
     * 
     * @param FacturaProveedor $invoice
     *
     * @return bool
     */
    protected function updateSupplierReceipts($invoice)
    {
        /// check current invoice receipts
        $receipts = $invoice->getReceipts();

        /// calculate outstanding amount
        $amount = $this->getOutstandingAmount($receipts, $invoice->total);

        /// calculate new receipt number
        $newNum = 1;
        foreach ($receipts as $receipt) {
            /// try to update open receipts
            if ($receipt->pagado === false) {
                $receipt->importe += $amount;
                $receipt->save();
                return true;
            }

            if ($receipt->numero >= $newNum) {
                $newNum = 1 + $receipt->numero;
            }
        }

        /// create new receipt
        return $this->newSupplierReceipt($invoice, $newNum, $amount);
    }
}
