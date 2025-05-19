<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Traits;

use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\Agente;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Contacto;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Model\FacturaProveedor;
use FacturaScripts\Core\Model\Producto;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\Serie;
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

    protected function getRandomCompany(): Empresa
    {
        $company = new Empresa();
        $company->direccion = 'Calle falsa 123';
        $company->cifnif = 'B' . mt_rand(1, 999999);
        $company->nombre = 'Company ' . mt_rand(1, 99999);
        $company->nombrecorto = 'Comp' . mt_rand(1, 99999);
        return $company;
    }

    protected function getRandomContact(string $test_name = ''): Contacto
    {
        $contact = new Contacto();
        $contact->cifnif = 'B' . mt_rand(1, 999999);
        $contact->nombre = 'Contact Rand ' . mt_rand(1, 99999);
        $contact->empresa = 'Empresa ' . mt_rand(1, 99999);
        $contact->observaciones = $test_name;
        return $contact;
    }

    protected function getRandomCustomer(string $test_name = ''): Cliente
    {
        $cliente = new Cliente();
        $cliente->cifnif = 'B' . mt_rand(1, 999999);
        $cliente->nombre = 'Customer Rand ' . mt_rand(1, 99999);
        $cliente->observaciones = $test_name;
        $cliente->razonsocial = 'Empresa ' . mt_rand(1, 99999);
        return $cliente;
    }

    protected function getRandomCustomerInvoice(string $date = '', string $codalmacen = ''): FacturaCliente
    {
        // creamos el cliente
        $subject = $this->getRandomCustomer('RandomDataTrait');
        $subject->save();

        $invoice = new FacturaCliente();
        $invoice->setSubject($subject);
        if ($codalmacen) {
            $invoice->codalmacen = $codalmacen;
        }
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

        // no hemos encontrado ninguno, creamos uno
        $model->loadFromDate(date('d-m-Y'));
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

    protected function getRandomSerie(): Serie
    {
        $serie = new Serie();
        $serie->codserie = 'T' . mt_rand(1, 999);
        $serie->descripcion = 'Test Serie';
        return $serie;
    }

    protected function getRandomSupplier(string $test_name = ''): Proveedor
    {
        $proveedor = new Proveedor();
        $proveedor->cifnif = mt_rand(1, 99999999) . 'J';
        $proveedor->nombre = 'Proveedor Rand ' . mt_rand(1, 999);
        $proveedor->observaciones = $test_name;
        $proveedor->razonsocial = 'Empresa ' . mt_rand(1, 999);
        return $proveedor;
    }

    protected function getRandomSupplierInvoice(string $date = '', string $codalmacen = ''): FacturaProveedor
    {
        // creamos el proveedor
        $subject = $this->getRandomSupplier('RandomDataTrait');
        $subject->save();

        $invoice = new FacturaProveedor();
        $invoice->setSubject($subject);
        if ($codalmacen) {
            $invoice->codalmacen = $codalmacen;
        }
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
