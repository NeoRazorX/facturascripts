<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\Email\NewMail;
use FacturaScripts\Core\Model\EmailSent;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
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
        $this->assertNull($emailSent->notification);

        // eliminamos
        $this->assertTrue($emailSent->delete());
    }

    public function testNotification(): void
    {
        $emailSent = new EmailSent();
        $emailSent->addressee = 'notification@example.com';
        $emailSent->email_from = 'sender@example.com';
        $emailSent->notification = str_repeat('n', 100);
        $emailSent->subject = 'Test notification';
        $emailSent->body = 'Test body';
        $this->assertTrue($emailSent->save());

        $emailSent->reload();
        $this->assertSame(str_repeat('n', 100), $emailSent->notification);

        $this->assertTrue($emailSent->delete());
    }

    public function testNewMailSavesNotification(): void
    {
        $notificationName = 'test-new-mail-notification';
        $mailer = new class extends NewMail {
            public function saveTestMail(): void
            {
                $this->saveMailSent();
            }
        };
        $mailer->notification($notificationName)
            ->to('new-mail-notification@example.com')
            ->subject('Test NewMail notification')
            ->body('Test body');
        $mailer->saveTestMail();

        $where = [
            Where::eq('addressee', 'new-mail-notification@example.com'),
            Where::eq('notification', $notificationName),
        ];
        $emails = EmailSent::all($where);
        $this->assertCount(1, $emails);
        $this->assertSame($notificationName, $emails[0]->notification);

        foreach (EmailSent::allWhereEq('notification', $notificationName) as $email) {
            $this->assertTrue($email->delete());
        }

        $plainMailer = new class extends NewMail {
            public function saveTestMail(): void
            {
                $this->saveMailSent();
            }
        };
        $plainMailer->to('new-mail-without-notification@example.com')
            ->subject('Test NewMail without notification')
            ->body('Test body');
        $plainMailer->saveTestMail();

        $plainEmails = EmailSent::allWhereEq('addressee', 'new-mail-without-notification@example.com');
        $this->assertCount(1, $plainEmails);
        $this->assertNull($plainEmails[0]->notification);
        foreach (EmailSent::allWhereEq('uuid', $plainEmails[0]->uuid) as $email) {
            $this->assertTrue($email->delete());
        }
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
