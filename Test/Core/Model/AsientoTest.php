<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017       Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * Copyright (C) 2017-2018  Carlos Garcia Gomez     <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\LogMessage;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Asiento
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class AsientoTest extends TestCase
{
    use LogErrorsTrait;

    public function testCheckLogAudit()
    {
        $asiento = new Asiento();
        $asiento->concepto = 'Test';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');
        $this->assertNotNull($asiento->primaryColumnValue(), 'asiento-not-stored');
        $this->assertTrue($asiento->exists(), 'asiento-cant-persist');

        $found = $this->searchAuditLog($asiento->modelClassName(), $asiento->idasiento);
        $this->assertTrue($found, 'asiento-log-audit-cant-persist');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    protected function tearDown()
    {
        $this->logErrors();
    }
}
