<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Model\CuentaBancoProveedor;
use FacturaScripts\Core\Model\LogMessage;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class CuentaBancoProveedorTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate(): void
    {
        // creamos un proveedor
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save(), 'proveedor-cant-save');

        // creamos una cuenta bancaria
        $cuenta = new CuentaBancoProveedor();
        $cuenta->codproveedor = $proveedor->codproveedor;
        $cuenta->descripcion = 'Test Account';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // comprobamos que se ha guardado correctamente
        $this->assertNotNull($cuenta->id(), 'cuenta-not-stored');
        $this->assertTrue($cuenta->exists(), 'cuenta-cant-persist');

        // eliminamos
        $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
        $this->assertTrue($proveedor->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($proveedor->delete(), 'proveedor-cant-delete');
    }

    public function testCreateWithoutSupplier(): void
    {
        // creamos una cuenta bancaria
        $cuenta = new CuentaBancoProveedor();
        $cuenta->descripcion = 'Test Account';
        $this->assertFalse($cuenta->save(), 'cuenta-can-save');
    }

    public function testHtmlOnFields(): void
    {
        // desactivamos la validación de IBAN
        Tools::settingsSet('default', 'validate_iban', '0');

        // creamos un proveedor
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save(), 'proveedor-cant-save');

        // creamos una cuenta bancaria con html en los campos
        $cuenta = new CuentaBancoProveedor();
        $cuenta->codproveedor = $proveedor->codproveedor;
        $cuenta->descripcion = '<p>Test Account</p>';
        $cuenta->iban = '<test>';
        $cuenta->swift = '<dd>';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // comprobamos que el html se ha escapado
        $this->assertEquals('&lt;p&gt;Test Account&lt;/p&gt;', $cuenta->descripcion);
        $this->assertEquals('&lt;test&gt;', $cuenta->iban);
        $this->assertEquals('&lt;dd&gt;', $cuenta->swift);

        // eliminamos
        $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
        $this->assertTrue($proveedor->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($proveedor->delete(), 'proveedor-cant-delete');
    }

    public function testDeleteWithSupplier(): void
    {
        // creamos un proveedor
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save(), 'proveedor-cant-save');

        // creamos una cuenta bancaria
        $cuenta = new CuentaBancoProveedor();
        $cuenta->codproveedor = $proveedor->codproveedor;
        $cuenta->descripcion = 'Test Account';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // eliminamos el proveedor
        $this->assertTrue($proveedor->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($proveedor->delete(), 'proveedor-cant-delete');

        // comprobamos que la cuenta bancaria se ha eliminado
        $this->assertFalse($cuenta->exists(), 'cuenta-persist');
    }

    public function testIbanChangeIsAudited(): void
    {
        // desactivamos la validación de IBAN
        Tools::settingsSet('default', 'validate_iban', '0');

        // creamos un proveedor
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save(), 'proveedor-cant-save');

        // creamos una cuenta bancaria sin IBAN
        $cuenta = new CuentaBancoProveedor();
        $cuenta->codproveedor = $proveedor->codproveedor;
        $cuenta->descripcion = 'Test Account';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // asignar el primer IBAN no se registra en auditoría
        MiniLog::clear(LogMessage::AUDIT_CHANNEL);
        $cuenta->iban = 'ES7921000813610123456789';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');
        $this->assertEmpty($this->getIbanChangedLogs(), 'first-iban-audited');

        // guardar sin cambiar el IBAN no se registra en auditoría
        $cuenta->descripcion = 'Test Account 2';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');
        $this->assertEmpty($this->getIbanChangedLogs(), 'unchanged-iban-audited');

        // cambiar el IBAN sí se registra en auditoría
        $cuenta->iban = 'ES9121000418450200051332';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');
        $logs = $this->getIbanChangedLogs();
        $this->assertCount(1, $logs, 'iban-change-not-audited');
        $this->assertEquals('ES7921000813610123456789', $logs[0]['context']['%old%']);
        $this->assertEquals('ES9121000418450200051332', $logs[0]['context']['%new%']);
        $this->assertEquals($proveedor->codproveedor, $logs[0]['context']['%codproveedor%']);
        MiniLog::clear(LogMessage::AUDIT_CHANNEL);

        // eliminamos
        $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
        $this->assertTrue($proveedor->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($proveedor->delete(), 'proveedor-cant-delete');
    }

    public function testValidateIban(): void
    {
        // activamos la validación de IBAN
        Tools::settingsSet('default', 'validate_iban', '1');

        // creamos un proveedor
        $proveedor = $this->getRandomSupplier();
        $this->assertTrue($proveedor->save(), 'proveedor-cant-save');

        // creamos una cuenta bancaria con IBAN incorrecto
        $cuenta = new CuentaBancoProveedor();
        $cuenta->codproveedor = $proveedor->codproveedor;
        $cuenta->descripcion = 'Test Account';
        $cuenta->iban = 'ES912100041840123456789';
        $this->assertFalse($cuenta->save(), 'cuenta-can-save');

        // creamos una cuenta bancaria con IBAN correcto
        $cuenta = new CuentaBancoProveedor();
        $cuenta->codproveedor = $proveedor->codproveedor;
        $cuenta->descripcion = 'Test Account';
        $cuenta->iban = 'ES79 2100 0813 6101 2345 6789';
        $this->assertTrue($cuenta->save(), 'cuenta-cant-save');

        // eliminamos
        $this->assertTrue($cuenta->delete(), 'cuenta-cant-delete');
        $this->assertTrue($proveedor->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($proveedor->delete(), 'proveedor-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function getIbanChangedLogs(): array
    {
        return array_values(array_filter(
            MiniLog::read(LogMessage::AUDIT_CHANNEL, ['warning']),
            fn($item) => $item['original'] === 'supplier-iban-changed'
        ));
    }
}
