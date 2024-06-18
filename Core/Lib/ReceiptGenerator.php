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

namespace FacturaScripts\Core\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\FacturaProveedor;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\WorkQueue;
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
     * @param FacturaCliente|FacturaProveedor $invoice
     * @param int $number
     *
     * @return bool
     */
    public function generate($invoice, int $number = 0): bool
    {
        if ($number > self::MAX_RECEIPTS) {
            $number = self::MAX_RECEIPTS;
        } elseif ($number < 0) {
            $number = 0;
        }

        switch ($invoice->modelClassName()) {
            case 'FacturaCliente':
                return empty($number) ?
                    $this->updateCustomerReceipts($invoice) :
                    $this->generateCustomerReceipts($invoice, $number);

            case 'FacturaProveedor':
                return empty($number) ?
                    $this->updateSupplierReceipts($invoice) :
                    $this->generateSupplierReceipts($invoice, $number);
        }

        return false;
    }

    /**
     * @param FacturaCliente|FacturaProveedor $invoice
     */
    public function update(&$invoice)
    {
        // obtenemos los recibos de la factura
        $receipts = $invoice->getReceipts();

        // sumamos los importes pagados
        $paidAmount = 0.0;
        $invoice->vencida = false;
        foreach ($receipts as $receipt) {
            if ($receipt->pagado) {
                $paidAmount += $receipt->importe;
            } elseif ($receipt->vencido) {
                $invoice->vencida = true;
            }
        }
        // la factura está pagada si el importe pagado es igual o superior al importe total
        $invoice->pagada = $this->isCero($invoice->total - $paidAmount) || ($invoice->total > 0 && $invoice->total <= $paidAmount);

        // actualizamos la factura por sql
        $dataBase = new DataBase();
        $sql = 'UPDATE ' . $invoice::tableName() . ' SET pagada = ' . $dataBase->var2str($invoice->pagada)
            . ', vencida = ' . $dataBase->var2str($invoice->vencida)
            . ' WHERE ' . $invoice::primaryColumn() . ' = ' . $dataBase->var2str($invoice->primaryColumnValue()) . ';';
        $dataBase->exec($sql);

        WorkQueue::send('Model.' . $invoice->modelClassName() . '.Paid', $invoice->primaryColumnValue(), $invoice->toArray());
    }

    /**
     * @param FacturaCliente $invoice
     * @param int $number
     *
     * @return bool
     */
    protected function generateCustomerReceipts($invoice, $number): bool
    {
        // check current invoice receipts
        $receipts = $invoice->getReceipts();

        // calculate outstanding amount
        $amount = $this->getOutstandingAmount($receipts, $invoice->total);
        if (empty($amount)) {
            Tools::log()->warning('no-outstanding-amount');
            return false;
        }

        // calculate new receipt number
        $newNum = 1;
        foreach ($receipts as $receipt) {
            if ($receipt->numero >= $newNum) {
                $newNum = 1 + $receipt->numero;
            }
        }

        // create new receipts
        $partialAmount = $number > 1 ? round($amount / $number, FS_NF0) : $amount;
        while (false === $this->isCero($amount)) {
            $receiptAmount = $amount > self::PARTIAL_AMOUNT_MULTIPLIER * $partialAmount ? $partialAmount : $amount;
            if (false === $this->newCustomerReceipt($invoice, $newNum, $receiptAmount)) {
                return false;
            }

            $amount -= $receiptAmount;
            $newNum++;
        }

        return true;
    }

    /**
     * @param FacturaProveedor $invoice
     * @param int $number
     *
     * @return bool
     */
    protected function generateSupplierReceipts($invoice, $number): bool
    {
        // check current invoice receipts
        $receipts = $invoice->getReceipts();

        // calculate outstanding amount
        $amount = $this->getOutstandingAmount($receipts, $invoice->total);
        if (empty($amount)) {
            Tools::log()->warning('no-outstanding-amount');
            return false;
        }

        // calculate new receipt number
        $newNum = 1;
        foreach ($receipts as $receipt) {
            if ($receipt->numero >= $newNum) {
                $newNum = 1 + $receipt->numero;
            }
        }

        // create new receipts
        $partialAmount = $number > 1 ? round($amount / $number, FS_NF0) : $amount;
        while (false === $this->isCero($amount)) {
            $receiptAmount = $amount > self::PARTIAL_AMOUNT_MULTIPLIER * $partialAmount ? $partialAmount : $amount;
            if (false === $this->newSupplierReceipt($invoice, $newNum, $receiptAmount)) {
                return false;
            }

            $amount -= $receiptAmount;
            $newNum++;
        }

        return true;
    }

    /**
     * @param ReciboCliente[]|ReciboProveedor[] $receipts
     * @param float $amount
     *
     * @return float
     */
    protected function getOutstandingAmount($receipts, $amount): float
    {
        $pending = $amount;
        foreach ($receipts as $receipt) {
            $pending -= $receipt->importe;
        }

        return round($pending, FS_NF0);
    }

    /**
     * Returns TRUE if $amount is cero.
     *
     * @param float $amount
     *
     * @return bool
     */
    protected function isCero($amount): bool
    {
        return Utils::floatcmp($amount, 0.0, FS_NF0, true);
    }

    /**
     * @param FacturaCliente $invoice
     * @param int $number
     * @param float $amount
     *
     * @return bool
     */
    protected function newCustomerReceipt($invoice, $number, $amount): bool
    {
        if (empty($amount)) {
            return true;
        }

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
     * @param FacturaProveedor $invoice
     * @param int $number
     * @param float $amount
     *
     * @return bool
     */
    protected function newSupplierReceipt($invoice, $number, $amount): bool
    {
        if (empty($amount)) {
            return true;
        }

        $newReceipt = new ReciboProveedor();
        $newReceipt->codproveedor = $invoice->codproveedor;
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
     * @return ToolBox
     */
    protected function toolBox(): ToolBox
    {
        return new ToolBox();
    }

    /**
     * @param FacturaCliente $invoice
     *
     * @return bool
     */
    protected function updateCustomerReceipts($invoice): bool
    {
        // check current invoice receipts
        $receipts = $invoice->getReceipts();

        // calculate outstanding amount
        $amount = $this->getOutstandingAmount($receipts, $invoice->total);
        if (empty($amount)) {
            return true;
        }

        // calculate new receipt number
        $newNum = 1;
        foreach ($receipts as $receipt) {
            // try to update open receipts
            if ($receipt->pagado === false) {
                $receipt->importe += $amount;
                return $receipt->save();
            }

            if ($receipt->numero >= $newNum) {
                $newNum = 1 + $receipt->numero;
            }
        }

        // create new receipt
        return $this->newCustomerReceipt($invoice, $newNum, $amount);
    }

    /**
     * @param FacturaProveedor $invoice
     *
     * @return bool
     */
    protected function updateSupplierReceipts($invoice): bool
    {
        // check current invoice receipts
        $receipts = $invoice->getReceipts();

        // calculate outstanding amount
        $amount = $this->getOutstandingAmount($receipts, $invoice->total);
        if (empty($amount)) {
            return true;
        }

        // calculate new receipt number
        $newNum = 1;
        foreach ($receipts as $receipt) {
            // try to update open receipts
            if ($receipt->pagado === false) {
                $receipt->importe += $amount;
                return $receipt->save();
            }

            if ($receipt->numero >= $newNum) {
                $newNum = 1 + $receipt->numero;
            }
        }

        // create new receipt
        return $this->newSupplierReceipt($invoice, $newNum, $amount);
    }
}
