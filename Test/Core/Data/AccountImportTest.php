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

namespace FacturaScripts\Test\Core\Data;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Lib\Accounting\AccountingPlanExport;
use FacturaScripts\Core\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\CuentaEspecial;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Subcuenta;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class AccountImportTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    private const CODEJERCICIO = '1980';
    private const TOTAL_ACCOUNT = 915;
    private const TOTAL_SUBACCOUNT = 745;

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
        $where = [Where::eq('codejercicio', $exercise->codejercicio)];
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
            Where::eq('codejercicio', $exercise->codejercicio),
            Where::eq('codsubcuenta', '1000000000'),
        ];
        $this->assertTrue($subaccount->loadWhere($where1), 'subaccount-1000000000-not-found');
        $this->assertEquals('100', $subaccount->codcuenta, 'subaccount-1000000000-account-error');

        // comprobamos que exista la cuenta 100 y que pertenezca al grupo correcto
        $where2 = [
            Where::eq('codejercicio', $exercise->codejercicio),
            Where::eq('codcuenta', '100'),
        ];
        $this->assertTrue($account->loadWhere($where2), 'account-100-not-found');
        $this->assertEquals('10', $account->parent_codcuenta, 'account-100-parent-error');

        // comprobamos que exista la cuenta 10 y que pertenezca al grupo correcto
        $where3 = [
            Where::eq('codejercicio', $exercise->codejercicio),
            Where::eq('codcuenta', '10'),
        ];
        $this->assertTrue($account->loadWhere($where3), 'account-10-not-found');
        $this->assertEquals('1', $account->parent_codcuenta, 'account-10-parent-error');

        // eliminamos
        $this->assertTrue($exercise->delete(), 'can-not-delete-exercise');
    }

    public function countryPlanProvider(): array
    {
        $plans = [];
        foreach (glob(FS_FOLDER . '/Core/Data/Codpais/*/defaultPlan.csv') as $filePath) {
            $plans[basename(dirname($filePath))] = [$filePath];
        }
        return $plans;
    }

    /**
     * @dataProvider countryPlanProvider
     */
    public function testCountryPlanHasCashAndBankAccounts(string $filePath)
    {
        $exercise = $this->getTestExercise();

        // importamos el plan contable del país
        $planImport = new AccountingPlanImport();
        $this->assertTrue($planImport->importCSV($filePath, $exercise->codejercicio), 'accounting-plan-import-fail');

        // las cuentas especiales de caja y bancos deben estar asignadas
        foreach (['CAJA', 'BANCO'] as $code) {
            $special = new CuentaEspecial();
            $this->assertTrue($special->load($code), 'special-account-not-found-' . $code);
            $this->assertNotEmpty($special->getCuenta($exercise->codejercicio)->codcuenta, 'special-account-not-assigned-' . $code);
        }

        // eliminamos
        $this->assertTrue($exercise->delete(), 'can-not-delete-exercise');
    }

    public function testExportedPlanCanBeImported()
    {
        $exercise = $this->getTestExercise();

        // importamos el plan por defecto y lo exportamos, como desde EditEjercicio
        $planImport = new AccountingPlanImport();
        $filePath = FS_FOLDER . '/Core/Data/Codpais/ESP/defaultPlan.csv';
        $this->assertTrue($planImport->importCSV($filePath, $exercise->codejercicio), 'accounting-plan-import-fail');
        $content = (new AccountingPlanExport())->exportCSV($exercise->codejercicio);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content, 'exported-csv-without-bom');
        $this->assertTrue($exercise->delete(), 'can-not-delete-exercise');

        // importamos el CSV exportado en un ejercicio vacío
        $exportPath = FS_FOLDER . '/MyFiles/Tmp/test-exported-plan.csv';
        Tools::folderCheckOrCreate(dirname($exportPath));
        file_put_contents($exportPath, $content);
        $exercise = $this->getTestExercise();
        $this->assertTrue($planImport->importCSV($exportPath, $exercise->codejercicio), 'exported-plan-import-fail');
        unlink($exportPath);

        // los textos acentuados se conservan
        $account = new Cuenta();
        $where = [
            Where::eq('codejercicio', $exercise->codejercicio),
            Where::eq('codcuenta', '1'),
        ];
        $this->assertTrue($account->loadWhere($where), 'account-1-not-found');
        $this->assertEquals('Financiación básica', $account->descripcion, 'account-1-description-error');
        $this->assertEquals(self::TOTAL_ACCOUNT, $account->count([Where::eq('codejercicio', $exercise->codejercicio)]), 'account-count-error');

        // eliminamos
        $this->assertTrue($exercise->delete(), 'can-not-delete-exercise');
    }

    public function encodingProvider(): array
    {
        $csv = "cuenta;descripcion;cuentaesp\n"
            . "1;Financiación básica;\n"
            . "10;Capital;\n"
            . "100;Capital social;\n"
            . "1000000000;Capital social, Peña y Muñoz;\n";

        return [
            'utf8-bom' => ["\xEF\xBB\xBF" . $csv],
            'utf8' => [$csv],
            'windows-1252' => [mb_convert_encoding($csv, 'Windows-1252', 'UTF-8')],
        ];
    }

    /**
     * @dataProvider encodingProvider
     */
    public function testImportCSVEncodings(string $content)
    {
        $exercise = $this->getTestExercise();

        $filePath = FS_FOLDER . '/MyFiles/Tmp/test-plan-encoding.csv';
        Tools::folderCheckOrCreate(dirname($filePath));
        file_put_contents($filePath, $content);

        $planImport = new AccountingPlanImport();
        $this->assertTrue($planImport->importCSV($filePath, $exercise->codejercicio), 'accounting-plan-import-fail');
        unlink($filePath);

        // las descripciones se guardan en UTF-8, sea cual sea la codificación del fichero
        $account = new Cuenta();
        $where = [
            Where::eq('codejercicio', $exercise->codejercicio),
            Where::eq('codcuenta', '1'),
        ];
        $this->assertTrue($account->loadWhere($where), 'account-1-not-found');
        $this->assertEquals('Financiación básica', $account->descripcion, 'account-1-description-error');

        $subaccount = new Subcuenta();
        $where = [
            Where::eq('codejercicio', $exercise->codejercicio),
            Where::eq('codsubcuenta', '1000000000'),
        ];
        $this->assertTrue($subaccount->loadWhere($where), 'subaccount-1000000000-not-found');
        $this->assertEquals('Capital social, Peña y Muñoz', $subaccount->descripcion, 'subaccount-description-error');

        // eliminamos
        $this->assertTrue($exercise->delete(), 'can-not-delete-exercise');
    }

    private function getTestExercise(): Ejercicio
    {
        $ejercicio = new Ejercicio();
        if (false === $ejercicio->load(self::CODEJERCICIO)) {
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
