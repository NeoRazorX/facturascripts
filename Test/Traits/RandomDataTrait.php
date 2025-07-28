<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\Calculator;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Almacen;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\Cuenta;
use FacturaScripts\Dinamic\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\FacturaProveedor;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Proveedor;
use FacturaScripts\Dinamic\Model\Provincia;
use FacturaScripts\Dinamic\Model\Serie;
use FacturaScripts\Dinamic\Model\User;

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

    protected function getRandomCity(int $id_provincia): Ciudad
    {
        $city = new Ciudad();
        $city->ciudad = 'Test City ' . mt_rand(1, 99);
        $city->alias = 'TC' . mt_rand(1, 999);
        $city->idprovincia = $id_provincia;

        return $city;
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

    protected function getRandomCountry(): Pais
    {
        $country = new Pais();
        $country->nombre = 'Test Country ' . mt_rand(1, 99);
        $country->codpais = 'T' . mt_rand(1, 99);

        return $country;
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

    protected function getRandomProvince(string $codpais): Provincia
    {
        $province = new Provincia();
        $province->provincia = 'Test Province ' . mt_rand(1, 99);
        $province->codpais = $codpais;
        $province->alias = 'TP' . mt_rand(1, 999);

        return $province;
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

    protected function getRandomTax(): Impuesto
    {
        $tax = new Impuesto();
        $tax->descripcion = 'Test Tax ' . mt_rand(1, 99);
        $tax->iva = mt_rand(1, 21); // IVA rates in Spain range from 0% to 21%

        return $tax;
    }

    protected function getRandomUser(): User
    {
        $user = new User();
        $user->nick = 'user_' . mt_rand(1, 999);
        $user->email = $user->nick . '@facturascripts.com';
        $user->setPassword(Tools::password());

        return $user;
    }

    protected function getRandomWarehouse(): Almacen
    {
        $warehouse = new Almacen();
        $warehouse->nombre = 'Warehouse ' . mt_rand(1, 99);

        return $warehouse;
    }
}
