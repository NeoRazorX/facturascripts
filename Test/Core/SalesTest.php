<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Core\Model\Serie;

/**
 * Description of SalesTest
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class SalesTest extends CustomTest
{

    /**
     * @var SalesDocument
     */
    public $model;

    public function testSaleSave()
    {
        /// create customer
        $customer = new Cliente();
        $customer->cifnif = '1234';
        $customer->nombre = 'Pepe Sales';
        $this->assertTrue($customer->save(), 'cliente-save-error');

        /// create document
        $model = clone $this->model;
        $model->clear();
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

        $this->assertTrue($model->save(), $model->modelClassName() . '-save-error');

        /// creating line
        $newLine = $model->getNewLine();
        $newLine->descripcion = 'test';
        $this->assertTrue($newLine->save(), $newLine->modelClassName() . '-save-error');

        /// remove line
        $this->assertTrue($newLine->delete(), $newLine->modelClassName() . '-delete-error');

        /// remove document
        $this->assertTrue($model->delete(), $model->modelClassName() . '-delete-error');

        /// get contact to remove
        $contact = $customer->getDefaultAddress();

        /// remove customer
        $this->assertTrue($customer->delete(), 'cliente-delete-error');

        /// remove the pending contact
        $this->assertTrue($contact->delete(), 'contacto-delete-error');
    }
}
