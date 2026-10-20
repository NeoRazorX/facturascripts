<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Lib\Accounting\InvoiceToAccounting;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

final class InvoiceToAccountingTest extends TestCase
{
    public function testCustomerNotFoundShowsCode(): void
    {
        // factura de un cliente que no existe
        $invoice = new FacturaCliente();
        $invoice->codcliente = 'TESTMISS9';

        $accounting = new InvoiceToAccounting();
        $property = new ReflectionProperty(InvoiceToAccounting::class, 'document');
        $property->setValue($accounting, $invoice);

        MiniLog::clear();
        $method = new ReflectionMethod(InvoiceToAccounting::class, 'addCustomerLine');
        $this->assertFalse($method->invoke($accounting, new Asiento()), 'customer-line-added-without-customer');

        // el aviso indica el código del cliente
        $messages = array_column(MiniLog::read('', ['warning']), 'message');
        $this->assertNotEmpty(array_filter($messages, function (string $message) {
            return str_contains($message, 'TESTMISS9');
        }), 'customer-not-found-without-code');
        MiniLog::clear();
    }
}
