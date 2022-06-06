<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017       Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * Copyright (C) 2017-2022  Carlos Garcia Gomez     <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Balance;
use FacturaScripts\Core\Model\BalanceCuentaA;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BalanceCuentaA
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class BalanceCuentaATest extends TestCase
{

    use LogErrorsTrait;

    public function testCreate()
    {
        $balance = new Balance();
        $balance->codbalance = 'TEST';
        $balance->naturaleza = 'TEST NATURALEZA';
        $this->assertTrue($balance->save(), 'balance-cant-save');

        $accountBalanceA = new BalanceCuentaA();
        $accountBalanceA->codbalance = $balance->codbalance;
        $accountBalanceA->codcuenta = 'TEST';
        $accountBalanceA->desccuenta = 'TEST DESCRIPTION';
        $this->assertTrue($accountBalanceA->save(), 'account-balance-a-cant-save');
        $this->assertNotNull($accountBalanceA->primaryColumnValue(), 'account-balance-a-not-stored');
        $this->assertTrue($accountBalanceA->exists(), 'account-balance-a-cant-persist');

        // eliminamos
        $this->assertTrue($accountBalanceA->delete(), 'account-balance-a-cant-delete');
        $this->assertTrue($balance->delete(), 'balance-cant-delete');
    }

    public function testAccountBalanceANoBalance()
    {
        $accountBalanceA = new BalanceCuentaA();
        $accountBalanceA->codcuenta = 'TEST';
        $accountBalanceA->desccuenta = 'TEST DESCRIPTION';
        $this->assertFalse($accountBalanceA->save(), 'account-balance-a-can-save-without-balance');
    }

    public function testHtmlOnFields()
    {
        $balance = new Balance();
        $balance->codbalance = 'TEST';
        $balance->naturaleza = 'TEST NATURALEZA';
        $this->assertTrue($balance->save(), 'balance-cant-save');

        $accountBalanceA = new BalanceCuentaA();
        $accountBalanceA->codbalance = $balance->codbalance;
        $accountBalanceA->codcuenta = 'TEST';
        $accountBalanceA->desccuenta = '<b>Test Html</b>';
        $this->assertTrue($accountBalanceA->save(), 'account-balance-a-cant-save');

        // comprobamos que el html ha sido escapado
        $noHtml = ToolBox::utils()::noHtml('<b>Test Html</b>');
        $this->assertEquals($noHtml, $accountBalanceA->desccuenta, 'account-balance-a-wrong-html');

        // eliminamos
        $this->assertTrue($accountBalanceA->delete(), 'account-balance-a-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
