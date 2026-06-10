<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\LogMessage;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class LogMessageTest extends TestCase
{
    use LogErrorsTrait;

    public function testSave(): void
    {
        $item = new LogMessage();
        $item->channel = 'test';
        $item->level = 'error';
        $item->message = 'test';
        $this->assertTrue($item->save(), 'cant-save-model');
        $this->assertTrue($item->exists(), 'item-not-found-in-db');
        $this->assertTrue($item->delete(), 'cant-delete-model');
    }

    public function testCanNotDeleteAuditLogs(): void
    {
        $item = new LogMessage();
        $item->channel = LogMessage::AUDIT_CHANNEL;
        $item->level = 'info';
        $item->message = 'test-audit-to-delete';
        $this->assertTrue($item->save(), 'cant-save-model');
        $this->assertTrue($item->exists(), 'item-not-found-in-db');
        $this->assertFalse($item->delete(), 'can-delete-audit-log');
    }

    public function testCanNotSaveInvalidLevel(): void
    {
        $item = new LogMessage();
        $item->channel = 'test';
        $item->level = 'invalid-level';
        $item->message = 'test';
        $this->assertFalse($item->save(), 'can-save-invalid-level');
    }

    public function testSanitizesNickAndIp(): void
    {
        $item = new LogMessage();
        $item->channel = 'test';
        $item->level = 'info';
        $item->message = 'test';
        $item->nick = '<b>admin</b>';
        $item->ip = '<b>127.0.0.1</b>';
        $this->assertTrue($item->save(), 'cant-save-model');

        $this->assertStringNotContainsString('<b>', $item->nick);
        $this->assertStringNotContainsString('<b>', $item->ip);

        $this->assertTrue($item->delete(), 'cant-delete-model');
    }

    public function testContextReturnsEmptyArrayOnNull(): void
    {
        $item = new LogMessage();
        $item->context = null;
        $this->assertEquals([], $item->context());
    }

    public function testContextReturnsEmptyArrayOnInvalidJson(): void
    {
        $item = new LogMessage();
        $item->context = 'not-json';
        $this->assertEquals([], $item->context());
    }

    public function testContextReturnsArray(): void
    {
        $item = new LogMessage();
        $item->context = json_encode(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $item->context());
    }

    public function testCanNotUpdateAuditLogs(): void
    {
        $item = new LogMessage();
        $item->channel = LogMessage::AUDIT_CHANNEL;
        $item->level = 'info';
        $item->message = 'test-audit-to-update';
        $this->assertTrue($item->save(), 'cant-save-model');

        $item->message = 'test-audit-to-update-2';
        $this->assertFalse($item->save(), 'can-update-audit-log');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
