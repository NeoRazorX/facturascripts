<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\CuentaBanco;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class CuentaBancoTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
    {
        // creamos una cuenta bancaria
        $cuenta = new CuentaBanco();
        $cuenta->codcuenta = '999';
        $cuenta->descripcion = 'Test Account';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // comprobamos que se ha guardado correctamente
        $this->assertNotNull($cuenta->primaryColumnValue(), 'cuenta-not-stored');
        $this->assertTrue($cuenta->exists(), 'cuenta-cant-persist');

        // eliminamos la cuenta bancaria
        $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
    }

    public function testCreateWithoutCode()
    {
        // creamos una cuenta sin código
        $cuenta = new CuentaBanco();
        $cuenta->descripcion = 'Test Account';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // eliminamos la cuenta bancaria
        $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
    }

    public function testHtmlOnFields()
    {
        // desactivamos la validación de IBAN
        Tools::settingsSet('default', 'validate_iban', '0');

        // creamos una cuenta bancaria con html en los campos
        $cuenta = new CuentaBanco();
        $cuenta->descripcion = '<p>Test Account</p>';
        $cuenta->iban = '<test>';
        $cuenta->swift = '<t>';
        $cuenta->codsubcuenta = '<test>';
        $cuenta->codsubcuentagasto = '<test>';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // comprobamos que el html se ha escapado
        $this->assertEquals('&lt;p&gt;Test Account&lt;/p&gt;', $cuenta->descripcion, 'cuenta-html-not-escaped');
        $this->assertEquals('&lt;test&gt;', $cuenta->iban, 'cuenta-html-not-escaped');
        $this->assertEquals('&lt;t&gt;', $cuenta->swift, 'cuenta-html-not-escaped');
        $this->assertEquals('&lt;test&gt;', $cuenta->codsubcuenta, 'cuenta-html-not-escaped');
        $this->assertEquals('&lt;test&gt;', $cuenta->codsubcuentagasto, 'cuenta-html-not-escaped');

        // eliminamos la cuenta bancaria
        $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
    }

    public function testCreateEmptyWithIbanValidation()
    {
        // activamos la validación de IBAN
        Tools::settingsSet('default', 'validate_iban', '1');

        // creamos una cuenta bancaria sin IBAN
        $cuenta = new CuentaBanco();
        $cuenta->descripcion = 'Test Account';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save-without-iban');

        // eliminamos la cuenta bancaria
        $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
    }

    public function testValidateGoodIban()
    {
        $list = [
            'ES9121000418450200051332', 'ES79 2100 0813 6101 2345 6789', 'PT50002700000001234567833',
            'AD1400080001001234567890', 'DE91100000000123456789', 'FR7630006000011234567890189'
        ];

        foreach ($list as $iban) {
            // activamos la validación de IBAN
            Tools::settingsSet('default', 'validate_iban', '1');

            // creamos una cuenta bancaria con IBAN correcto
            $cuenta = new CuentaBanco();
            $cuenta->descripcion = 'Test Account';
            $cuenta->iban = $iban;
            $this->assertTrue($cuenta->save(), 'cuenta-cant-save-iban-' . $iban);

            // desactivamos la validación de IBAN
            Tools::settingsSet('default', 'validate_iban', '0');

            // comprobamos que se sigue guardando correctamente
            $this->assertTrue($cuenta->save(), 'cuenta-cant-save-good-iban');

            // eliminamos la cuenta bancaria
            $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
        }
    }

    public function testValidateWrongIban()
    {
        $list = [
            '-', 'ES9121000418450200051332X', 'ES79 2100 0813 6101 2345 6789X', 'PT50002700000001234567833X',
            'AD1400080001001234567890X', 'DE91100000000123456789X', 'FR7630006000011234567890189X'
        ];

        foreach ($list as $iban) {
            // activamos la validación de IBAN
            Tools::settingsSet('default', 'validate_iban', '1');

            // creamos una cuenta bancaria con IBAN incorrecto
            $cuenta = new CuentaBanco();
            $cuenta->descripcion = 'Test Account';
            $cuenta->iban = $iban;
            $this->assertFalse($cuenta->save(), 'cuenta-can-save-iban-' . $iban);

            // desactivamos la validación de IBAN
            Tools::settingsSet('default', 'validate_iban', '0');

            // comprobamos que ahora si se puede guardar
            $this->assertTrue($cuenta->save(), 'cuenta-cant-save-with-iban-error');

            // eliminamos la cuenta bancaria
            $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
