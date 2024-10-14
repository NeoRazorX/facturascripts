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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\Divisa;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class DivisaTest extends TestCase
{
    use LogErrorsTrait;

    public function testDataInstalled(): void
    {
        // llamamos a la función para que se instalen los datos
        $this->assertNotEmpty(Divisa::all(), 'currency-data-not-installed');

        // llamamos de forma dinámica
        $currency = new Divisa();
        $this->assertNotEmpty($currency->all(), 'currency-data-not-installed-from-csv');
    }

    public function testCreate(): void
    {
        $currency = new Divisa();
        $currency->coddivisa = 'Tes';
        $currency->descripcion = 'Test Currency';
        $this->assertTrue($currency->save(), 'currency-cant-save');
        $this->assertNotNull($currency->primaryColumnValue(), 'currency-not-stored');
        $this->assertTrue($currency->exists(), 'currency-cant-persist');

        // eliminamos
        $this->assertTrue($currency->delete(), 'currency-cant-delete');
    }

    public function testCreateHtml(): void
    {
        // creamos una divisa con una descripción con html
        $currency = new Divisa();
        $currency->coddivisa = 'Tes';
        $currency->descripcion = '<b>Test Currency</b>';
        $this->assertTrue($currency->save(), 'currency-cant-save');

        // comprobamos que el html ha sido escapado
        $noHtml = Tools::noHtml('<b>Test Currency</b>');
        $this->assertEquals($noHtml, $currency->descripcion, 'currency-wrong-html');

        // eliminamos
        $this->assertTrue($currency->delete(), 'currency-cant-delete');
    }

    public function testCreateWithNewCode(): void
    {
        $currency = new Divisa();
        $currency->descripcion = 'Test Currency with new code';
        $this->assertFalse($currency->save(), 'currency-can-save');

        // No se pueden añadir espacios en el código
        $currency->coddivisa = 'Te ';
        $this->assertFalse($currency->save(), 'currency-can-save');
    }

    public function testDeleteDefault(): void
    {
        $currency = new Divisa();
        foreach ($currency->all([], [], 0, 0) as $row) {
            if ($row->isDefault()) {
                $this->assertFalse($row->delete(), 'currency-default-can-delete');
                break;
            }
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
