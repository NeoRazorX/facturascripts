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

use FacturaScripts\Core\Model\EmailNotification;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class EmailNotificationTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos una notificación de email
        $notification = new EmailNotification();
        $notification->name = 'test-notification';
        $notification->subject = 'Test Subject';
        $notification->body = 'Test body content';
        $this->assertTrue($notification->save());

        // comprobamos que existe en la base de datos
        $this->assertTrue($notification->exists());

        // comprobamos valores por defecto
        $this->assertTrue($notification->enabled);
        $this->assertNotNull($notification->creationdate);

        // eliminamos
        $this->assertTrue($notification->delete());
    }

    public function testCreateHtml(): void
    {
        // creamos una notificación con html en name y subject
        $notification = new EmailNotification();
        $notification->name = '<script>alert("xss")</script>test-notification';
        $notification->subject = '<b>Test Subject</b>';
        $notification->body = 'Test body content';
        $this->assertTrue($notification->save());

        // comprobamos que el html ha sido escapado en name y subject
        $this->assertEquals(Tools::noHtml('<script>alert("xss")</script>test-notification'), $notification->name);
        $this->assertEquals(Tools::noHtml('<b>Test Subject</b>'), $notification->subject);

        // eliminamos
        $this->assertTrue($notification->delete());
    }

    public function testClear(): void
    {
        // creamos una notificación y llamamos a clear
        $notification = new EmailNotification();
        $notification->clear();

        // comprobamos valores por defecto después del clear
        $this->assertTrue($notification->enabled);
        $this->assertEquals(Tools::date(), $notification->creationdate);
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
