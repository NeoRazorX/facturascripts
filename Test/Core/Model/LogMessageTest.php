<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base\ModelCore;
use FacturaScripts\Core\Model\LogMessage;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class LogMessageTest extends TestCase
{
    use LogErrorsTrait;

    public function testSave()
    {
        $item = new LogMessage();
        $item->channel = 'test';
        $item->level = 'error';
        $item->message = 'test';
        $this->assertTrue($item->save(), 'cant-save-model');
        $this->assertTrue($item->exists(), 'item-not-found-in-db');
        $this->assertTrue($item->delete(), 'cant-delete-model');
    }

    public function testCanNotDeleteAuditLogs()
    {
        $item = new LogMessage();
        $item->channel = ModelCore::AUDIT_CHANNEL;
        $item->level = 'info';
        $item->message = 'test-audit-to-delete';
        $this->assertTrue($item->save(), 'cant-save-model');
        $this->assertTrue($item->exists(), 'item-not-found-in-db');
        $this->assertFalse($item->delete(), 'can-delete-audit-log');
    }

    public function testCanNotUpdateAuditLogs()
    {
        $item = new LogMessage();
        $item->channel = ModelCore::AUDIT_CHANNEL;
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
