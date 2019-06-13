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

use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Core\Lib\BusinessDocumentTools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Class to automate the creation of purchase and sales invoices
 *
 * @author Artex Trading s.a. <jcuello@artextrading.com>
 */
class InvoiceGenerator
{

    /**
     * Generate a sales invoice
     *
     * @param string $subject
     * @param array $lines
     * @return int|null
     */
    public function generateSaleInvoice($subject, $lines)
    {
        $customer = new Cliente();
        $customer->loadFromCode($subject);

        $invoice = new FacturaCliente();
        if ($this->createHeader($invoice, $customer)) {
            $this->createLines($invoice, $lines);
        }
        return $invoice->idfactura;
    }

    /**
     * Generate a purchase invoice
     *
     * @param string $subject
     * @param array $lines
     * @return int|null
     */
    public function generatePurchaseInvoice($subject, $lines)
    {
        $supplier = new Proveedor();
        $supplier->loadFromCode($subject);

        $invoice = new FacturaProveedor();
        if ($this->createHeader($invoice, $supplier)) {
            $this->createLines($invoice, $lines);
        }
        return $invoice->idfactura;
    }

    /**
     *
     * @param TransformerDocument $invoice
     * @param Cliente|Proveedor $subject
     * @return bool
     */
    private function createHeader(&$invoice, $subject)
    {
        $invoice->setSubject($subject);
        return $invoice->save();
    }

    /**
     *
     * @param TransformerDocument $invoice
     * @param array $lines
     */
    private function createLines(&$invoice, $lines)
    {
        $product = new Producto();
        foreach ($lines as $row) {
            /// get reference
            if (!isset($row['referencia'])) {
                $product->loadFromCode($row['idproducto']);
                $row['referencia'] = $product->referencia;
            }

            /// add new line to invoice
            $newLinea = $invoice->getNewProductLine($row['referencia']);
            $newLinea->cantidad = $row['cantidad'] ?? $newLinea->cantidad;
            $newLinea->descripcion = $row['descripcion'] ?? $newLinea->descripcion;
            $newLinea->pvpunitario = $row['pvpunitario'] ?? $newLinea->pvpunitario;
            $newLinea->save();
        }

        /// recalculate header
        $docTools = new BusinessDocumentTools();
        $docTools->recalculate($invoice);
        $invoice->save();
    }
}
