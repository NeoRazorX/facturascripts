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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\EmailSent;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class EmailSentTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos un email enviado
        $emailSent = new EmailSent();
        $emailSent->addressee = 'test@example.com';
        $emailSent->email_from = 'sender@example.com';
        $emailSent->subject = 'Test Subject';
        $emailSent->body = 'Test body content';
        $this->assertTrue($emailSent->save());

        // comprobamos que existe en la base de datos
        $this->assertTrue($emailSent->exists());

        // comprobamos valores por defecto
        $this->assertFalse($emailSent->opened);
        $this->assertNotNull($emailSent->date);

        // eliminamos
        $this->assertTrue($emailSent->delete());
    }

    public function testClear(): void
    {
        // creamos un email y llamamos a clear
        $emailSent = new EmailSent();
        $emailSent->clear();

        // comprobamos valores por defecto después del clear
        $this->assertFalse($emailSent->opened);
        $this->assertEquals(Tools::dateTime(), $emailSent->date);
    }

    public function testVerify(): void
    {
        // creamos un email enviado
        $emailSent = new EmailSent();
        $emailSent->addressee = 'verify@example.com';
        $emailSent->email_from = 'sender@example.com';
        $emailSent->subject = 'Test Verify';
        $emailSent->body = 'Test body';
        $emailSent->verificode = 'test-verify-code';
        $this->assertTrue($emailSent->save());

        // verificamos que inicialmente no está abierto
        $this->assertFalse($emailSent->opened);

        // verificamos el email
        $this->assertTrue(EmailSent::verify('test-verify-code', 'verify@example.com'));

        // recargamos y comprobamos que está marcado como abierto
        $emailSent->reload();
        $this->assertTrue($emailSent->opened);

        // eliminamos
        $this->assertTrue($emailSent->delete());
    }

    public function testVerifyWithoutAddressee(): void
    {
        // creamos un email enviado
        $emailSent = new EmailSent();
        $emailSent->addressee = 'verify2@example.com';
        $emailSent->email_from = 'sender@example.com';
        $emailSent->subject = 'Test Verify 2';
        $emailSent->body = 'Test body 2';
        $emailSent->verificode = 'test-verify-code2';
        $this->assertTrue($emailSent->save());

        // verificamos sin especificar addressee
        $this->assertTrue(EmailSent::verify('test-verify-code2'));

        // recargamos y comprobamos que está marcado como abierto
        $emailSent->reload();
        $this->assertTrue($emailSent->opened);

        // eliminamos
        $this->assertTrue($emailSent->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
