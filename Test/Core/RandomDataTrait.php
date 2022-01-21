<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021  Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Lib\BusinessDocumentTools;
use FacturaScripts\Core\Model\Agente;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\FacturaProveedor;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\User;

trait RandomDataTrait
{
    protected function getRandomAgent(): Agente
    {
        $agente = new Agente();
        $agente->nombre = 'Pepe ' . mt_rand(1, 9999);
        return $agente;
    }

    protected function getRandomCustomer(): Cliente
    {
        $cliente = new Cliente();
        $cliente->cifnif = 'B' . mt_rand(1, 999999);
        $cliente->nombre = 'Customer ' . mt_rand(1, 99999);
        $cliente->razonsocial = 'Empresa ' . mt_rand(1, 99999);
        return $cliente;
    }

    protected function getRandomCustomerInvoice(): FacturaCliente
    {
        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $subject->save();

        $invoice = new FacturaCliente();
        $invoice->setSubject($subject);
        if ($invoice->save()) {
            $line = $invoice->getNewLine();
            $line->cantidad = 1;
            $line->pvpunitario = mt_rand(100, 9999);
            $line->save();

            $tool = new BusinessDocumentTools();
            $tool->recalculate($invoice);
            $invoice->save();
        }

        return $invoice;
    }

    protected function getRandomProduct(): Producto
    {
        $num = mt_rand(1, 9999);
        $product = new Producto();
        $product->referencia = 'test' . $num;
        $product->descripcion = 'Test Product ' . $num;
        return $product;
    }

    protected function getRandomSupplier(): Proveedor
    {
        $proveedor = new Proveedor();
        $proveedor->cifnif = mt_rand(1, 99999999) . 'J';
        $proveedor->nombre = 'Proveedor ' . mt_rand(1, 999);
        $proveedor->razonsocial = 'Empresa ' . mt_rand(1, 999);
        return $proveedor;
    }

    protected function getRandomSupplierInvoice(): FacturaProveedor
    {
        // creamos el proveedor
        $subject = $this->getRandomSupplier();
        $subject->save();

        $invoice = new FacturaProveedor();
        $invoice->setSubject($subject);
        if ($invoice->save()) {
            $line = $invoice->getNewLine();
            $line->cantidad = 1;
            $line->pvpunitario = mt_rand(100, 9999);
            $line->save();

            $tool = new BusinessDocumentTools();
            $tool->recalculate($invoice);
            $invoice->save();
        }

        return $invoice;
    }

    protected function getRandomUser(): User
    {
        $user = new User();
        $user->nick = 'user_' . mt_rand(1, 999);
        $user->email = $user->nick . '@facturascripts.com';
        $user->setPassword(mt_rand(1, 999999));
        return $user;
    }

    protected function getRandomWarehouse(): Almacen
    {
        $warehouse = new Almacen();
        $warehouse->nombre = 'Warehouse ' . mt_rand(1, 99);
        return $warehouse;
    }
}
