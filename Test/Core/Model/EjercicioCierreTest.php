<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Core\Lib\Accounting\ClosingToAcounting;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class EjercicioCierreTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testCloseExercise(): void
    {
        // comprobamos si existe la tabla de cuentas
        $db = new DataBase();
        $this->assertTrue($db->tableExists(Cuenta::tableName()));

        // creamos una nueva empresa
        $empresa = $this->getRandomCompany();
        $this->assertTrue($empresa->save());

        // obtenemos el almacén por defecto
        $almacen = new Almacen();
        $where = [new DataBaseWhere('idempresa', $empresa->idempresa)];
        $this->assertTrue($almacen->loadWhere($where));

        // creamos el ejercicio para 2020
        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = $ejercicio->newCode();
        $ejercicio->fechainicio = '2020-01-01';
        $ejercicio->fechafin = '2020-12-31';
        $ejercicio->idempresa = $empresa->idempresa;
        $ejercicio->nombre = '2020';
        $this->assertTrue($ejercicio->save());

        // copiamos el plan contable
        $filePath = FS_FOLDER . '/Core/Data/Codpais/' . Paises::default()->codpais . '/defaultPlan.csv';
        $planImport = new AccountingPlanImport();
        $planImport->importCSV($filePath, $ejercicio->codejercicio);

        // creamos una factura de compra con fecha 04-01-2020
        $facturaCompra = $this->getRandomSupplierInvoice('2020-01-04', $almacen->codalmacen);
        $this->assertTrue($facturaCompra->exists());

        // creamos una factura de venta con fecha 05-01-2020
        $facturaVenta = $this->getRandomCustomerInvoice('2020-01-05', $almacen->codalmacen);
        $this->assertTrue($facturaVenta->exists());

        // cerramos el ejercicio
        $data = [
            'journalClosing' => '',
            'journalOpening' => '',
            'copySubAccounts' => true
        ];
        $closing = new ClosingToAcounting();
        $this->assertTrue($ejercicio->reload());
        $this->assertTrue($closing->exec($ejercicio, $data));

        // comprobamos que el ejercicio está cerrado
        $this->assertTrue($ejercicio->reload());
        $this->assertFalse($ejercicio->isOpened());

        // comprobamos que todas las subcuentas del ejercicio anterior tienen saldo 0
        $whereExercise = [new DataBaseWhere('codejercicio', $ejercicio->codejercicio)];
        foreach (Subcuenta::all($whereExercise, [], 0, 0) as $subcuenta) {
            $this->assertEquals(0, $subcuenta->saldo);
        }

        // reabrimos el ejercicio
        $ejercicio->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($ejercicio->save());

        // eliminamos las facturas
        $this->assertTrue($facturaVenta->delete());
        $this->assertTrue($facturaVenta->getSubject()->getDefaultAddress()->delete());
        $this->assertTrue($facturaVenta->getSubject()->delete());
        $this->assertTrue($facturaCompra->delete());
        $this->assertTrue($facturaCompra->getSubject()->getDefaultAddress()->delete());
        $this->assertTrue($facturaCompra->getSubject()->delete());

        // eliminamos los asientos del ejercicio
        foreach (Asiento::all($whereExercise, [], 0, 0) as $asiento) {
            $this->assertTrue($asiento->delete());
        }

        // eliminamos el ejercicio y la empresa
        $this->assertTrue($ejercicio->delete());
        $this->assertTrue($empresa->delete());
    }

    public function testCloseExerciseWithMissingEntries(): void
    {
        // creamos una nueva empresa
        $empresa = $this->getRandomCompany();
        $this->assertTrue($empresa->save());

        // obtenemos el almacén por defecto
        $almacen = new Almacen();
        $where = [new DataBaseWhere('idempresa', $empresa->idempresa)];
        $this->assertTrue($almacen->loadWhere($where));

        // creamos el ejercicio para 2026
        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = $ejercicio->newCode();
        $ejercicio->fechainicio = '2026-01-01';
        $ejercicio->fechafin = '2026-12-31';
        $ejercicio->idempresa = $empresa->idempresa;
        $ejercicio->nombre = '2026';
        $this->assertTrue($ejercicio->save());

        // copiamos el plan contable
        $filePath = FS_FOLDER . '/Core/Data/Codpais/' . Paises::default()->codpais . '/defaultPlan.csv';
        $planImport = new AccountingPlanImport();
        $planImport->importCSV($filePath, $ejercicio->codejercicio);

        // creamos una factura de compra con fecha 04-01-2026
        $facturaCompra = $this->getRandomSupplierInvoice('2026-01-04', $almacen->codalmacen);
        $this->assertTrue($facturaCompra->exists());

        // creamos una factura de venta con fecha 05-01-2026
        $facturaVenta = $this->getRandomCustomerInvoice('2026-01-05', $almacen->codalmacen);
        $this->assertTrue($facturaVenta->exists());

        // eliminamos el asiento de la factura de venta
        $this->assertTrue($facturaVenta->getAccountingEntry()->delete());

        // cerramos el ejercicio, no debería cerrarse
        $data = [
            'journalClosing' => '',
            'journalOpening' => '',
            'copySubAccounts' => true
        ];
        $closing = new ClosingToAcounting();
        $this->assertFalse($closing->exec($ejercicio, $data));

        // eliminamos
        $this->assertTrue($facturaVenta->delete());
        $this->assertTrue($facturaVenta->getSubject()->getDefaultAddress()->delete());
        $this->assertTrue($facturaVenta->getSubject()->delete());
        $this->assertTrue($facturaCompra->delete());
        $this->assertTrue($facturaCompra->getSubject()->getDefaultAddress()->delete());
        $this->assertTrue($facturaCompra->getSubject()->delete());
        $this->assertTrue($ejercicio->delete());
        $this->assertTrue($empresa->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
