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
        $recibo = new ReciboCliente();
        $recibo->codcliente = $invoice->codcliente;
        $recibo->coddivisa = $invoice->coddivisa;
        $recibo->idempresa = $invoice->idempresa;
        $recibo->idfactura = $invoice->idfactura;
        $recibo->importe = $invoice->total;
        $recibo->nick = $invoice->nick;
        $recibo->save();
    }

    /**
     * 
     * @param FacturaProveedor $invoice
     */
    protected function generateReciboProveedor(&$invoice)
    {
        $recibo = new ReciboProveedor();
        $recibo->codproveedor = $invoice->codproveedor;
        $recibo->coddivisa = $invoice->coddivisa;
        $recibo->idempresa = $invoice->idempresa;
        $recibo->idfactura = $invoice->idfactura;
        $recibo->importe = $invoice->total;
        $recibo->nick = $invoice->nick;
        $recibo->save();
    }
}
