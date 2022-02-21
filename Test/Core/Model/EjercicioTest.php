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
use FacturaScripts\Core\Lib\Accounting\AccountingPlanImport;
use FacturaScripts\Core\Lib\Accounting\ClosingToAcounting;
use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Test\Core\CustomTest;
use FacturaScripts\Test\Core\RandomDataTrait;

/**
 * @covers \Ejercicio
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 */
final class EjercicioTest extends CustomTest
{
    use RandomDataTrait;

    protected function setUp()
    {
        $this->model = new Ejercicio();
    }

    public function testCreate()
    {
        // creamos el ejercicio
        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = '3000';
        $ejercicio->nombre = '3000';
        $ejercicio->fechainicio = '01-01-3000';
        $ejercicio->fechafin = '31-12-3000';
        $this->assertTrue($ejercicio->save(), 'ejercicio-cant-save');

        // importamos el csv
        $planImport = new AccountingPlanImport();
        $filePath = FS_FOLDER . '/Core/Data/Codpais/ESP/defaultPlan.csv';
        $this->assertTrue($planImport->importCSV($filePath, $ejercicio->codejercicio), 'accounting-plan-import-fail');

        // creamos 2 facturas de cliente
        $customerInvoice1 = $this->getRandomCustomerInvoice($ejercicio->codejercicio);
        $customerInvoice2 = $this->getRandomCustomerInvoice($ejercicio->codejercicio);

        // creamos 2 facturas de proveedor
        $supplierInvoice1 = $this->getRandomSupplierInvoice($ejercicio->codejercicio);
        $supplierInvoice2 = $this->getRandomSupplierInvoice($ejercicio->codejercicio);

        // cerramos el ejercicio
        $closing = new ClosingToAcounting();
        $data['copySubAccounts'] = true;
        $this->assertTrue($closing->exec($ejercicio, $data), 'ejercicio-cant-close');

        // recargamos el ejercicio
        $ejercicio->loadFromCode($ejercicio->codejercicio);

        // comprobamos el cierre del ejercicio
        $asiento = new Asiento();
        $where = [
            new DataBaseWhere('codejercicio', $ejercicio->codejercicio),
            new DataBaseWhere('operacion', 'C')
        ];
        $asiento->loadFromCode('', $where);
        $this->assertTrue($asiento->exists(), 'asiento-ejercicio-cierre-not-exists');
        $this->assertEquals('CERRADO', $ejercicio->estado, 'ejercicio-bad-close');

        // obtenemos el nuevo ejercicio del año siguiente
        $newEjercicio = new Ejercicio();
        $newEjercicio->loadFromCode('3001');

        // comprobamos que el nuevo ejercicio está abierto y tiene asiento de apertura
        $this->assertTrue($newEjercicio->exists(), 'new-ejercicio-not-exists');
        $this->assertEquals('ABIERTO', $newEjercicio->estado, 'new-ejercicio-not-open');
        $where = [
            new DataBaseWhere('codejercicio', $newEjercicio->codejercicio),
            new DataBaseWhere('operacion', 'A')
        ];
        $asiento->loadFromCode('', $where);
        $this->assertTrue($asiento->exists(), 'asiento-new-ejercicio-cierre-not-exists');

        // cargamos los clientes
        $customer1 = new Cliente();
        $customer1->loadFromCode($customerInvoice1->codcliente);
        $customer2 = new Cliente();
        $customer2->loadFromCode($customerInvoice2->codcliente);

        // cargamos los proveedores
        $supplier1 = new Cliente();
        $supplier1->loadFromCode($supplierInvoice1->codproveedor);
        $supplier2 = new Cliente();
        $supplier2->loadFromCode($supplierInvoice2->codproveedor);

        // cargamos los asientos del ejercicio 3000
        $asientos1 = $asiento->all([new DataBaseWhere('codejercicio', $ejercicio->codejercicio)]);

        // cargamos los asientos del ejercicio 3001
        $asientos2 = $asiento->all([new DataBaseWhere('codejercicio', $newEjercicio->codejercicio)]);

        // abrimos el ejercicio 1 para poder eliminar sus asientos
        $ejercicio->estado = 'ABIERTO';
        $this->assertTrue($ejercicio->save(), 'ejercicio-cant-save');

        // eliminamos
        $this->assertTrue($supplierInvoice1->delete(), 'can-not-delete-factura-proveedor-1');
        $this->assertTrue($supplierInvoice2->delete(), 'can-not-delete-factura-proveedor-2');
        $this->assertTrue($customerInvoice1->delete(), 'can-not-delete-factura-cliente-1');
        $this->assertTrue($customerInvoice2->delete(), 'can-not-delete-factura-cliente-2');
        $this->assertTrue($customer1->delete(), 'can-not-delete-cliente-1');
        $this->assertTrue($customer2->delete(), 'can-not-delete-cliente-2');
        $this->assertTrue($supplier1->delete(), 'can-not-delete-proveedor-1');
        $this->assertTrue($supplier2->delete(), 'can-not-delete-proveedor-2');

        foreach ($asientos1 as $a) {
            $this->assertTrue($a->delete(), 'can-not-delete-asiento-ejercicio-1');
        }

        foreach ($asientos2 as $a) {
            $this->assertTrue($a->delete(), 'can-not-delete-asiento-ejercicio-2');
        }

        $this->assertTrue($ejercicio->delete(), 'can-not-delete-ejercicio');
        $this->assertTrue($newEjercicio->delete(), 'can-not-delete-new-ejercicio');
    }
}
