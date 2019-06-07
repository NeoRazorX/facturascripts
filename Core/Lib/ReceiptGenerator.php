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
namespace FacturaScripts\Core\Lib;

use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Core\Model\ReciboCliente;
use FacturaScripts\Core\Model\ReciboProveedor;

/**
 * Description of ReceiptGenerator
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ReceiptGenerator
{

    /**
     * 
     * @param FacturaCliente|FacturaProveedor $invoice
     */
    public function generate(&$invoice)
    {
        switch ($invoice->modelClassName()) {
            case 'FacturaCliente':
                $this->generateReciboCliente($invoice);
                break;

            case 'FacturaProveedor':
                $this->generateReciboProveedor($invoice);
                break;
        }
    }

    /**
     * 
     * @param FacturaCliente $invoice
     */
    protected function generateReciboCliente(&$invoice)
    {
        /// check current invoice receipts
        $receipts = $invoice->getReceipts();

        /// calculate pending amount
        $amount = $this->getPendingAmount($receipts, $invoice->total);
        if (empty($amount)) {
            return;
        }

        /// try to update open receipts
        $newNum = 1;
        foreach ($receipts as $receipt) {
            if ($receipt->pagado === false) {
                $receipt->importe = $amount;
                $receipt->save();
                return;
            }

            if ($receipt->numero == $newNum) {
                $newNum++;
            }
        }

        /// create new receipt
        $newReceipt = new ReciboCliente();
        $newReceipt->codcliente = $invoice->codcliente;
        $newReceipt->coddivisa = $invoice->coddivisa;
        $newReceipt->idempresa = $invoice->idempresa;
        $newReceipt->idfactura = $invoice->idfactura;
        $newReceipt->importe = $amount;
        $newReceipt->nick = $invoice->nick;
        $newReceipt->numero = $newNum;
        $newReceipt->setPaymentMethod($invoice->codpago);
        $newReceipt->save();
    }

    /**
     * 
     * @param FacturaProveedor $invoice
     */
    protected function generateReciboProveedor(&$invoice)
    {
        /// check current invoice receipts
        $receipts = $invoice->getReceipts();

        /// calculate pending amount
        $amount = $this->getPendingAmount($receipts, $invoice->total);
        if (empty($amount)) {
            return;
        }

        /// try to update open receipts
        $newNum = 1;
        foreach ($receipts as $receipt) {
            if ($receipt->pagado === false) {
                $receipt->importe = $amount;
                $receipt->save();
                return;
            }

            if ($receipt->numero == $newNum) {
                $newNum++;
            }
        }

        /// create new receipt
        $newReceipt = new ReciboProveedor();
        $newReceipt->codproveedor = $invoice->codproveedor;
        $newReceipt->coddivisa = $invoice->coddivisa;
        $newReceipt->idempresa = $invoice->idempresa;
        $newReceipt->idfactura = $invoice->idfactura;
        $newReceipt->importe = $amount;
        $newReceipt->nick = $invoice->nick;
        $newReceipt->numero = $newNum;
        $newReceipt->setPaymentMethod($invoice->codpago);
        $newReceipt->save();
    }

    /**
     * 
     * @param ReciboCliente[]|ReciboProveedor[] $receipts
     * @param float                             $amount
     *
     * @return float
     */
    protected function getPendingAmount($receipts, $amount)
    {
        $pending = $amount;
        foreach ($receipts as $receipt) {
            if ($receipt->pagado) {
                $pending -= $receipt->importe;
            }
        }

        return $pending;
    }
}
