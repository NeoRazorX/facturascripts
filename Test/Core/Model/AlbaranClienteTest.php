<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Core\Model\Serie;
use FacturaScripts\Test\Core\CustomTest;

/**
 * @covers AlbaranCliente
 */
final class AlbaranClienteTest extends CustomTest
{

    protected function setUp()
    {
        $this->model = new AlbaranCliente();
    }

    public function testSave()
    {
        /// create customer
        $customer = new Cliente();
        $customer->cifnif = '1234';
        $customer->nombre = 'Pepe';
        $this->assertTrue($customer->save(), 'cliente-save-error');

        /// create document
        $model = new AlbaranCliente();
        $model->setSubject($customer);
        $warehouseModel = new Almacen();
        foreach ($warehouseModel->all() as $warehouse) {
            $model->codalmacen = $warehouse->codalmacen;
        }
        $paymentModel = new FormaPago();
        foreach ($paymentModel->all() as $payment) {
            $model->codpago = $payment->codpago;
        }
        $serieModel = new Serie();
        foreach ($serieModel->all() as $serie) {
            $model->codserie = $serie->codserie;
        }
        $this->assertTrue($model->save(), 'albaran-cliente-save-error');

        /// creating line
        $newLine = $model->getNewLine();
        $newLine->descripcion = 'test';
        $this->assertTrue($newLine->save(), 'linea-albaran-cliente-save-error');

        /// remove line
        $this->assertTrue($newLine->delete(), 'linea-albaran-cliente-delete-error');

        /// remove document
        $this->assertTrue($model->delete(), 'albaran-cliente-delete-error');

        /// remove customer
        $this->assertTrue($customer->delete(), 'cliente-delete-error');
    }
}
