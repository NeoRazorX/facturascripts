<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\BusinessDocumentTools;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Base\PurchaseDocument;
use FacturaScripts\Core\Model\FormaPago;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Core\Model\Serie;

/**
 * Description of PurchasesTest
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class PurchasesTest extends CustomTest
{

    /**
     * @var PurchaseDocument
     */
    public $model;

    public function testPurchaseSave()
    {
        /// create supplier
        $supplier = new Proveedor();
        $supplier->cifnif = '1234';
        $supplier->nombre = 'Paco Purchases';
        $this->assertTrue($supplier->save(), 'proveedor-save-error');

        /// create document
        $model = clone $this->model;
        $model->clear();
        $model->setSubject($supplier);
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

        $model->dtopor1 = 10;
        $model->dtopor2 = 10;
        $this->assertTrue($model->save(), $model->modelClassName() . '-save-error');

        /// create line
        $newLine = $model->getNewLine();
        $newLine->descripcion = 'test';
        $newLine->cantidad = 1;
        $newLine->pvpunitario = 100;
        $newLine->dtopor = 10;
        $newLine->iva = 21;
        $newLine->irpf = 13;
        $newLine->recargo = 0.0;
        $this->assertTrue($newLine->save(), $newLine->modelClassName() . '-save-error');

        /// recalculate totals
        $tool = new BusinessDocumentTools();
        $tool->recalculate($model);
        $this->assertEquals(90, $model->netosindto, $model->modelClassName() . '-netosindto-error');
        $this->assertEquals(72.9, $model->neto, $model->modelClassName() . '-neto-error');
        $this->assertEquals(15.31, $model->totaliva, $model->modelClassName() . '-totaliva-error');
        $this->assertEquals(9.48, $model->totalirpf, $model->modelClassName() . '-totalirpf-error');
        $this->assertEquals(0.0, $model->totalrecargo, $model->modelClassName() . '-totalrecargo-error');
        $this->assertEquals(78.73, $model->total, $model->modelClassName() . '-total-error');
        $this->assertTrue($model->save(), $model->modelClassName() . '-save2-error');

        /// remove document
        $this->assertTrue($model->delete(), $model->modelClassName() . '-delete-error');

        /// test line deletion
        $this->assertFalse($newLine->exists(), $newLine->modelClassName() . '-still-exists');

        /// get the contacto to remove
        $contact = $supplier->getDefaultAddress();

        /// remove supplier
        $this->assertTrue($supplier->delete(), 'proveedor-delete-error');

        /// remove the pending contact
        $this->assertTrue($contact->delete(), 'contacto-delete-error');
    }
}
