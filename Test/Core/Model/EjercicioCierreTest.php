<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Core\Lib\Accounting\ClosingToAcounting;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Ejercicio;
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

    public function testCloseExercise()
    {
        // creamos una nueva empresa
        $empresa = $this->getRandomCompany();
        $this->assertTrue($empresa->save());

        // obtenemos el almacén por defecto
        $almacen = new Almacen();
        $where = [new DataBaseWhere('idempresa', $empresa->idempresa)];
        $this->assertTrue($almacen->loadFromCode('', $where));

        // creamos el ejercicio para 2020
        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = $ejercicio->newCode();
        $ejercicio->fechainicio = '2020-01-01';
        $ejercicio->fechafin = '2020-12-31';
        $ejercicio->idempresa = $empresa->idempresa;
        $ejercicio->nombre = '2020';
        $this->assertTrue($ejercicio->save());

        // copiamos el plan contable
        $filePath = FS_FOLDER . '/Core/Data/Codpais/ESP/defaultPlan.csv';
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
        $this->assertTrue($closing->exec($ejercicio, $data));

        // comprobamos que el ejercicio está cerrado
        $this->assertTrue($ejercicio->loadFromCode($ejercicio->codejercicio));
        $this->assertFalse($ejercicio->isOpened());

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
        $asiento = new Asiento();
        $where = [new DataBaseWhere('codejercicio', $ejercicio->codejercicio)];
        foreach ($asiento->all($where) as $asiento) {
            $this->assertTrue($asiento->delete());
        }

        // eliminamos el ejercicio y la empresa
        $this->assertTrue($ejercicio->delete());
        $this->assertTrue($empresa->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
