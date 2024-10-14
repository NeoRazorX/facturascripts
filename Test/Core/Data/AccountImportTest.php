<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Data;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Subcuenta;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class AccountImportTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    private const CODEJERCICIO = '1980';
    private const TOTAL_ACCOUNT = 802;
    private const TOTAL_SUBACCOUNT = 721;

    public static function setUpBeforeClass(): void
    {
        $db = new DataBase();
        $db->exec("DELETE FROM subcuentas WHERE codejercicio = '" . self::CODEJERCICIO . "';");
        $db->exec("DELETE FROM cuentas WHERE codejercicio = '" . self::CODEJERCICIO . "';");
    }

    public function testInstallCSV()
    {
        $exercise = $this->getTestExercise();

        // comprobamos que no hay cuentas cargadas
        $account = new Cuenta();
        $where = [new DataBaseWhere('codejercicio', $exercise->codejercicio)];
        $this->assertEquals(0, $account->count($where), 'account-count-not-empty');

        // comprobamos que no hay subcuentas
        $subaccount = new Subcuenta();
        $this->assertEquals(0, $subaccount->count($where), 'subaccount-count-not-empty');

        // importamos el csv
        $planImport = new AccountingPlanImport();
        $filePath = FS_FOLDER . '/Core/Data/Codpais/ESP/defaultPlan.csv';
        $this->assertTrue($planImport->importCSV($filePath, $exercise->codejercicio), 'accounting-plan-import-fail');

        // comprobamos el total de cuentas importadas
        $this->assertEquals(self::TOTAL_ACCOUNT, $account->count($where), 'account-count-error');

        // comprobamos el total de subcuentas importadas
        $this->assertEquals(self::TOTAL_SUBACCOUNT, $subaccount->count($where), 'subaccount-count-error');

        // comprobamos que exista la subcuenta 1000000000 y que pertenezca al grupo correcto
        $where1 = [
            new DataBaseWhere('codejercicio', $exercise->codejercicio),
            new DataBaseWhere('codsubcuenta', '1000000000'),
        ];
        $this->assertTrue($subaccount->loadFromCode('', $where1), 'subaccount-1000000000-not-found');
        $this->assertEquals('100', $subaccount->codcuenta, 'subaccount-1000000000-account-error');

        // comprobamos que exista la cuenta 100 y que pertenezca al grupo correcto
        $where2 = [
            new DataBaseWhere('codejercicio', $exercise->codejercicio),
            new DataBaseWhere('codcuenta', '100'),
        ];
        $this->assertTrue($account->loadFromCode('', $where2), 'account-100-not-found');
        $this->assertEquals('10', $account->parent_codcuenta, 'account-100-parent-error');

        // comprobamos que exista la cuenta 10 y que pertenezca al grupo correcto
        $where3 = [
            new DataBaseWhere('codejercicio', $exercise->codejercicio),
            new DataBaseWhere('codcuenta', '10'),
        ];
        $this->assertTrue($account->loadFromCode('', $where3), 'account-10-not-found');
        $this->assertEquals('1', $account->parent_codcuenta, 'account-10-parent-error');

        // eliminamos
        $this->assertTrue($exercise->delete(), 'can-not-delete-exercise');
    }

    private function getTestExercise(): Ejercicio
    {
        $ejercicio = new Ejercicio();
        if (false === $ejercicio->loadFromCode(self::CODEJERCICIO)) {
            $ejercicio->codejercicio = self::CODEJERCICIO;
            $ejercicio->idempresa = Tools::settings('default', 'idempresa', 1);
            $ejercicio->fechainicio = '01-01-' . self::CODEJERCICIO;
            $ejercicio->fechafin = '31-12-' . self::CODEJERCICIO;
            $ejercicio->nombre = self::CODEJERCICIO;
            $ejercicio->save();
        }

        return $ejercicio;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
