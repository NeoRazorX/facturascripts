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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\DataSrc\Series;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\RegimenIVA;
use FacturaScripts\Core\Model\Impuesto;
use FacturaScripts\Core\Model\ImpuestoZona;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\PresupuestoProveedor;
use FacturaScripts\Core\Model\Serie;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class CalculatorTest extends TestCase
{
    use RandomDataTrait;

    public function testEmptyDoc(): void
    {
        $doc = new PresupuestoCliente();
        $lines = [];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos
        $this->assertEquals(0.0, $doc->neto, 'bad-neto');
        $this->assertEquals(0.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(0.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');
        $this->assertEquals(0.0, $doc->totalcoste, 'bad-totalcoste');
        $this->assertEquals(0.0, $doc->totalbeneficio, 'bad-totalbeneficio');
    }

    public function testEmptyLine(): void
    {
        $doc = new PresupuestoCliente();
        $lines = [$doc->getNewLine()];

        // comprobamos el documento
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');
        $this->assertEquals(0.0, $doc->neto, 'bad-neto');
        $this->assertEquals(0.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(0.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');
        $this->assertEquals(0.0, $doc->totalcoste, 'bad-totalcoste');
        $this->assertEquals(0.0, $doc->totalbeneficio, 'bad-totalbeneficio');

        // comprobamos la línea
        $this->assertEquals(0.0, $lines[0]->pvpsindto, 'bad-line-pvpsindto');
        $this->assertEquals(0.0, $lines[0]->pvptotal, 'bad-line-pvptotal');
    }

    public function testLines(): void
    {
        $doc = new PresupuestoCliente();

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;

        // segunda línea
        $line2 = $doc->getNewLine();
        $line2->cantidad = 2;
        $line2->coste = 3;
        $line2->pvpunitario = 10;
        $line2->iva = 4;

        $lines = [$line1, $line2];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(120.0, $doc->neto, 'bad-neto');
        $this->assertEquals(120.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(141.8, $doc->total, 'bad-total');
        $this->assertEquals(21.8, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');
        $this->assertEquals(6.0, $doc->totalcoste, 'bad-totalcoste');
        $this->assertEquals(114.0, $doc->totalbeneficio, 'bad-totalbeneficio');

        // comprobamos la primera línea
        $this->assertEquals(100.0, $lines[0]->pvpsindto, 'bad-line1-pvpsindto');
        $this->assertEquals(100.0, $lines[0]->pvptotal, 'bad-line1-pvptotal');

        // comprobamos la segunda línea
        $this->assertEquals(20.0, $lines[1]->pvpsindto, 'bad-line2-pvpsindto');
        $this->assertEquals(20.0, $lines[1]->pvptotal, 'bad-line2-pvptotal');
    }

    public function testDiscounts(): void
    {
        $doc = new PresupuestoCliente();
        $doc->dtopor1 = 5;
        $doc->dtopor2 = 3;

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;

        // segunda línea
        $line2 = $doc->getNewLine();
        $line2->cantidad = 2;
        $line2->pvpunitario = 10;
        $line2->dtopor = 10;
        $line2->dtopor2 = 5;
        $line2->iva = 4;

        $lines = [$line1, $line2];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(107.91, $doc->neto, 'bad-neto');
        $this->assertEquals(117.1, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(127.89, $doc->total, 'bad-total');
        $this->assertEquals(19.98, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');

        // comprobamos la primera línea
        $this->assertEquals(100.0, $lines[0]->pvpsindto, 'bad-line1-pvpsindto');
        $this->assertEquals(100.0, $lines[0]->pvptotal, 'bad-line1-pvptotal');

        // comprobamos la segunda línea
        $this->assertEquals(20.0, $lines[1]->pvpsindto, 'bad-line2-pvpsindto');
        $this->assertEquals(17.1, $lines[1]->pvptotal, 'bad-line2-pvptotal');
    }

    public function testCostWithFullDiscount(): void
    {
        // creamos el documento
        $doc = new PresupuestoCliente();

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->coste = 50;
        $line1->iva = 21;
        $line1->dtopor = 100;

        $lines = [$line1];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(0.0, $doc->neto, 'bad-neto');
        $this->assertEquals(0.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(0.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');
        $this->assertEquals(50, $doc->totalcoste, 'bad-totalcoste');
    }

    public function testRetention(): void
    {
        $doc = new PresupuestoCliente();

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;
        $line1->irpf = 15;

        // segunda línea
        $line2 = $doc->getNewLine();
        $line2->cantidad = 2;
        $line2->pvpunitario = 10;
        $line2->iva = 4;
        $line2->irpf = 15;

        // tercera línea
        $line3 = $doc->getNewLine();
        $line3->cantidad = 5;
        $line3->pvpunitario = 11;
        $line3->iva = 21;

        $lines = [$line1, $line2, $line3];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(175.0, $doc->neto, 'bad-neto');
        $this->assertEquals(175.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(190.35, $doc->total, 'bad-total');
        $this->assertEquals(33.35, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(18.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');
    }

    public function testCustomerRe(): void
    {
        // creamos un cliente con recargo de equivalencia
        $subject = $this->getRandomCustomer();
        $subject->regimeniva = RegimenIVA::TAX_SYSTEM_SURCHARGE;
        $this->assertTrue($subject->save(), 'can-not-create-re-customer');

        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-re-customer');

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;
        $line1->recargo = 5.2;

        // segunda línea
        $line2 = $doc->getNewLine();
        $line2->cantidad = 2;
        $line2->pvpunitario = 10;
        $line2->iva = 21;
        $line2->recargo = 5.2;

        // tercera línea
        $line3 = $doc->getNewLine();
        $line3->cantidad = 5;
        $line3->pvpunitario = 11;
        $line3->iva = 21;
        $line3->recargo = 0.0;

        $lines = [$line1, $line2, $line3];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(175.0, $doc->neto, 'bad-neto');
        $this->assertEquals(175.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(217.99, $doc->total, 'bad-total');
        $this->assertEquals(36.75, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(6.24, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
    }

    public function testSupplierRe(): void
    {
        // creamos un proveedor con recargo de equivalencia
        $subject = $this->getRandomSupplier();
        $subject->regimeniva = RegimenIVA::TAX_SYSTEM_SURCHARGE;
        $this->assertTrue($subject->save(), 'can-not-create-re-customer');

        $doc = new PresupuestoProveedor();
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-re-customer');

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 2;
        $line1->pvpunitario = 100;
        $line1->iva = 21;
        $line1->recargo = 5.2;

        $lines = [$line1];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(200.0, $doc->neto, 'bad-neto');
        $this->assertEquals(200.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(252.4, $doc->total, 'bad-total');
        $this->assertEquals(42.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(10.4, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'proveedor-cant-delete');
    }

    public function testSupplied(): void
    {
        $doc = new PresupuestoCliente();

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 3;
        $line1->pvpunitario = 50;
        $line1->iva = 21;

        // segunda línea
        $line2 = $doc->getNewLine();
        $line2->cantidad = 1;
        $line2->pvpunitario = 20;
        $line2->iva = 0.0;
        $line2->suplido = true;

        // tercera línea
        $line3 = $doc->getNewLine();
        $line3->cantidad = 2;
        $line3->pvpunitario = 15;
        $line3->iva = 0.0;
        $line3->suplido = true;

        $lines = [$line1, $line2, $line3];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(150.0, $doc->neto, 'bad-neto');
        $this->assertEquals(150.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(231.5, $doc->total, 'bad-total');
        $this->assertEquals(31.5, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(50.0, $doc->totalsuplidos, 'bad-totalsuplidos');
    }

    public function testNoTaxSerie(): void
    {
        // creamos una serie sin impuestos
        $serie = new Serie();
        $serie->codserie = 'NT';
        if (false === $serie->exists()) {
            $serie->descripcion = 'NO TAX';
            $serie->siniva = true;
            $this->assertTrue($serie->save(), 'can-not-save-no-tax-serie');

            // limpiamos la caché
            Series::clear();
        }

        // creamos el documento con la serie sin impuestos
        $doc = new PresupuestoCliente();
        $doc->codserie = $serie->codserie;

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;

        // segunda línea
        $line2 = $doc->getNewLine();
        $line2->cantidad = 2;
        $line2->pvpunitario = 10;
        $line2->iva = 4;

        $lines = [$line1, $line2];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(120.0, $doc->neto, 'bad-neto');
        $this->assertEquals(120.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(120.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');

        // eliminamos
        $serie->delete();
    }

    public function testCustomerExempt(): void
    {
        // creamos un cliente exento
        $subject = $this->getRandomCustomer();
        $subject->regimeniva = RegimenIVA::TAX_SYSTEM_EXEMPT;
        $this->assertTrue($subject->save(), 'can-not-create-customer-exempt');

        // creamos el documento
        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-customer-exempt');

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;

        $lines = [$line1];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'cliente-cant-delete');
    }

    public function testSupplierExempt(): void
    {
        // creamos un proveedor exento
        $subject = $this->getRandomSupplier();
        $subject->regimeniva = RegimenIVA::TAX_SYSTEM_EXEMPT;
        $this->assertTrue($subject->save(), 'can-not-create-supplier-exempt');

        // creamos el documento
        $doc = new PresupuestoProveedor();
        $this->assertTrue($doc->setSubject($subject), 'can-not-assign-customer-exempt');

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;

        $lines = [$line1];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'proveedor-cant-delete');
    }

    public function testTaxZone(): void
    {
        // creamos un impuesto del 20%
        $tax1 = new Impuesto();
        $tax1->codimpuesto = 'IVA20';
        if (false === $tax1->exists()) {
            $tax1->descripcion = $tax1->codimpuesto;
            $tax1->iva = 20;
            $this->assertTrue($tax1->save(), 'can-not-save-iva20');
        }

        // creamos un impuesto del 19%
        $tax2 = new Impuesto();
        $tax2->codimpuesto = 'IVA19';
        if (false === $tax2->exists()) {
            $tax2->descripcion = $tax2->codimpuesto;
            $tax2->iva = 19;
            $this->assertTrue($tax2->save(), 'can-not-save-iva19');
        }

        // creamos una zona o excepción para el IVA 21 en Andorra
        $zone1 = new ImpuestoZona();
        $zone1->codimpuesto = $tax1->codimpuesto;
        $zone1->codimpuestosel = null;
        $zone1->codpais = 'AND';
        $zone1->prioridad = 2;
        $this->assertTrue($zone1->save(), 'can-not-save-tax-zone1');

        // creamos una segunda zona con menor prioridad
        $zone2 = new ImpuestoZona();
        $zone2->codimpuesto = $tax1->codimpuesto;
        $zone2->codimpuestosel = $tax2->codimpuesto;
        $zone2->codpais = 'AND';
        $zone2->prioridad = 1;
        $this->assertTrue($zone2->save(), 'can-not-save-tax-zone2');

        // creamos el documento
        $doc = new PresupuestoCliente();
        $doc->codpais = 'AND';

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->codimpuesto = $tax1->codimpuesto;
        $line1->iva = $tax1->iva;

        $lines = [$line1];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(100.0, $doc->total, 'bad-total');
        $this->assertEquals(0.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');

        // eliminamos
        $zone1->delete();
        $zone2->delete();
        $tax1->delete();
        $tax2->delete();
    }

    public function testNoTaxZone(): void
    {
        // creamos un impuesto del 20%
        $tax1 = new Impuesto();
        $tax1->codimpuesto = 'IVA20';
        if (false === $tax1->exists()) {
            $tax1->descripcion = $tax1->codimpuesto;
            $tax1->iva = 20;
            $this->assertTrue($tax1->save(), 'can-not-save-iva20');
        }

        // creamos una zona o excepción para el IVA 21 en Andorra
        $zone1 = new ImpuestoZona();
        $zone1->codimpuesto = $tax1->codimpuesto;
        $zone1->codimpuestosel = null;
        $zone1->codpais = 'AND';
        $zone1->prioridad = 2;
        $this->assertTrue($zone1->save(), 'can-not-save-tax-zone1');

        // creamos el documento
        $doc = new PresupuestoCliente();
        $doc->codpais = 'ESP';

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;

        $lines = [$line1];
        $this->assertFalse(Calculator::calculate($doc, $lines, false), 'doc-saved');

        // comprobamos el documento
        $this->assertEquals(100.0, $doc->neto, 'bad-neto');
        $this->assertEquals(100.0, $doc->netosindto, 'bad-netosindto');
        $this->assertEquals(121.0, $doc->total, 'bad-total');
        $this->assertEquals(21.0, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(0.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');

        // eliminamos
        $zone1->delete();
        $tax1->delete();
    }
}
