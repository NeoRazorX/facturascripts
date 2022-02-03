<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Subcuenta;
use FacturaScripts\Test\Core\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class AccountImportTest extends TestCase {

    private const TOTAL_ACCOUNT = 805;
    private const TOTAL_SUBACCOUNT = 721;

    use RandomDataTrait;

    public static function setUpBeforeClass()
    {
        // borramos todas las cuentas y subcuentas existentes
        $database = new DataBase();
        $database->exec('DELETE FROM ' . Subcuenta::tableName());
        $database->exec('DELETE FROM ' . Cuenta::tableName());
    }

    public function testInstallCSV()
    {
        // comprobamos que exista el csv con el plan contable español
        $filePath = FS_FOLDER . '/Dinamic/Data/Codpais/ESP/defaultPlan.csv';
        $this->assertTrue(file_exists($filePath), 'esp-default-plan-must-be-exists');

        // importamos el csv
        $planImport = new AccountingPlanImport();
        $planImport->importCSV($filePath, $this->getRandomExercise()->codejercicio);

        // comprobamos que se haya importado el csv
        $account = new Cuenta();
        $this->assertNotEmpty($account->count() > 0, 'account-data-not-installed-from-csv');

        $subaccount = new Subcuenta();
        $this->assertNotEmpty($subaccount->count() > 0, 'account-data-not-installed-from-csv');
    }

    public function testAccountCount()
    {
        // comprobamos el total de cuentas importadas
        $account = new Cuenta();
        $this->assertTrue($account->count() == self::TOTAL_ACCOUNT, 'account-count-error');
    }

    public function testSubAccountCount()
    {
        // comprobamos el total de subcuentas importadas
        $subaccount = new Subcuenta();
        $this->assertTrue($subaccount->count() == self::TOTAL_SUBACCOUNT, 'subaccount-count-error');
    }

    public function testAccountTree()
    {
        $exercise = $this->getRandomExercise()->codejercicio;
        $subaccount = new Subcuenta();
        $where1 = [
            new DataBaseWhere('codejercicio', $exercise),
            new DataBaseWhere('codsubcuenta', '1000000000'),
        ];

        // comprobamos que exista la subcuenta 1000000000 y que pertenezca al grupo correcto
        $this->assertTrue($subaccount->loadFromCode('', $where1), 'subaccount-1000000000-not-found');
        $this->assertTrue($subaccount->codcuenta == '100', 'subaccount-1000000000-account-error');

        $account = new Cuenta();
        $where2 = [
            new DataBaseWhere('codejercicio', $exercise),
            new DataBaseWhere('codcuenta', '100'),
        ];

        // comprobamos que exista la cuenta 100 y que pertenezca al grupo correcto
        $this->assertTrue($account->loadFromCode('', $where2), 'account-100-not-found');
        $this->assertTrue($account->parent_codcuenta == '10', 'account-100-parent-error');

        $where3 = [
            new DataBaseWhere('codejercicio', $exercise),
            new DataBaseWhere('codcuenta', '10'),
        ];

        // comprobamos que exista la cuenta 10 y que pertenezca al grupo correcto
        $this->assertTrue($account->loadFromCode('', $where3), 'account-10-not-found');
        $this->assertTrue($account->parent_codcuenta == '1', 'account-10-parent-error');
    }
}
