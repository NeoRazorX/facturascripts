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

use FacturaScripts\Core\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class AccountingPlanImportTest extends TestCase
{
    use LogErrorsTrait;

    // Comprobar la conversión de códigos de subcuenta a otra longitud.
    public function testConvertSubaccountCode(): void
    {
        $import = new AccountingPlanImport();
        $method = new ReflectionMethod(AccountingPlanImport::class, 'convertSubaccountCode');
        $method->setAccessible(true);

        // si el código ya tiene la longitud pedida, no cambia
        $this->assertEquals('4300000001', $method->invoke($import, '4300000001', '430', 10));

        // acortar quita ceros tras la cuenta padre
        $this->assertEquals('5700001', $method->invoke($import, '5700000001', '570', 7));
        $this->assertEquals('5700000', $method->invoke($import, '5700000000', '570', 7));

        // acortar conserva los dígitos significativos posteriores al padre
        $this->assertEquals('4721004', $method->invoke($import, '4721000004', '472', 7));

        // alargar añade ceros en la mayor secuencia de ceros tras el padre
        $this->assertEquals('5700000001', $method->invoke($import, '5700001', '570', 10));
        $this->assertEquals('4721000004', $method->invoke($import, '4721004', '472', 10));

        // los ceros a la izquierda del código padre se conservan
        $this->assertEquals('020104001', $method->invoke($import, '02010401', '020104', 9));

        // sin ceros en el sufijo, se insertan tras la cuenta padre
        $this->assertEquals('201010235', $method->invoke($import, '20101235', '20101', 9));

        // si el código no cabe en la longitud pedida, devuelve cadena vacía
        $this->assertEquals('', $method->invoke($import, '20101235', '20101', 7));
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
