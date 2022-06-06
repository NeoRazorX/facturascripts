<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Core\Model\Agente;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\FacturaProveedor;
use FacturaScripts\Core\Model\GrupoClientes;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\User;

trait RandomDataTrait
{
    protected function getRandomAccount(string $codejercicio): Cuenta
    {
        $account = new Cuenta();
        $account->codcuenta = '9999';
        $account->codejercicio = $codejercicio;
        $account->descripcion = 'Test';
        return $account;
    }

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

    protected function getRandomCustomerGroup(): GrupoClientes
    {
        $group = new GrupoClientes();
        $group->codgrupo = 'Test';
        $group->nombre = 'Test Group';
        return $group;
    }

    protected function getRandomCustomerInvoice(string $date = ''): FacturaCliente
    {
        // creamos el cliente
        $subject = $this->getRandomCustomer();
        $subject->save();

        $invoice = new FacturaCliente();
        $invoice->setSubject($subject);
        if ($date) {
            $invoice->setDate($date, $invoice->hora);
        }
        if ($invoice->save()) {
            $line = $invoice->getNewLine();
            $line->cantidad = 1;
            $line->pvpunitario = mt_rand(100, 9999);
            $line->save();

            $lines = $invoice->getLines();
            Calculator::calculate($invoice, $lines, true);
        }

        return $invoice;
    }

    protected function getRandomExercise(): Ejercicio
    {
        $model = new Ejercicio();
        foreach ($model->all() as $ejercicio) {
            return $ejercicio;
        }

        return $model;
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

    protected function getRandomSupplierInvoice(string $date = ''): FacturaProveedor
    {
        // creamos el proveedor
        $subject = $this->getRandomSupplier();
        $subject->save();

        $invoice = new FacturaProveedor();
        $invoice->setSubject($subject);
        if ($date) {
            $invoice->setDate($date, $invoice->hora);
        }
        if ($invoice->save()) {
            $line = $invoice->getNewLine();
            $line->cantidad = 1;
            $line->pvpunitario = mt_rand(100, 9999);
            $line->save();

            $lines = $invoice->getLines();
            Calculator::calculate($invoice, $lines, true);
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
