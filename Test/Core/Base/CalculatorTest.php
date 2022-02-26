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

namespace FacturaScripts\Test\Core\Base;

use FacturaScripts\Core\Base\Calculator;
use FacturaScripts\Core\Model\PresupuestoCliente;
use PHPUnit\Framework\TestCase;

final class CalculatorTest extends TestCase
{
    public function testEmptyDoc()
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
    }

    public function testEmptyLine()
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

        // comprobamos la línea
        $this->assertEquals(0.0, $lines[0]->pvpsindto, 'bad-line-pvpsindto');
        $this->assertEquals(0.0, $lines[0]->pvptotal, 'bad-line-pvptotal');
    }

    public function testLines()
    {
        $doc = new PresupuestoCliente();

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;
        $line1->recargo = 0.0;

        // segunda línea
        $line2 = $doc->getNewLine();
        $line2->cantidad = 2;
        $line2->pvpunitario = 10;
        $line2->iva = 4;
        $line2->recargo = 0.0;

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

        // comprobamos la primera línea
        $this->assertEquals(100.0, $lines[0]->pvpsindto, 'bad-line1-pvpsindto');
        $this->assertEquals(100.0, $lines[0]->pvptotal, 'bad-line1-pvptotal');

        // comprobamos la segunda línea
        $this->assertEquals(20.0, $lines[1]->pvpsindto, 'bad-line2-pvpsindto');
        $this->assertEquals(20.0, $lines[1]->pvptotal, 'bad-line2-pvptotal');
    }

    public function testDiscounts()
    {
        $doc = new PresupuestoCliente();
        $doc->dtopor1 = 5;
        $doc->dtopor2 = 3;

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;
        $line1->recargo = 0.0;

        // segunda línea
        $line2 = $doc->getNewLine();
        $line2->cantidad = 2;
        $line2->pvpunitario = 10;
        $line2->dtopor = 10;
        $line2->dtopor2 = 5;
        $line2->iva = 4;
        $line2->recargo = 0.0;

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

    public function testRetention()
    {
        $doc = new PresupuestoCliente();

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 100;
        $line1->iva = 21;
        $line1->irpf = 15;
        $line1->recargo = 0.0;

        // segunda línea
        $line2 = $doc->getNewLine();
        $line2->cantidad = 2;
        $line2->pvpunitario = 10;
        $line2->iva = 4;
        $line2->irpf = 15;
        $line2->recargo = 0.0;

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
        $this->assertEquals(190.35, $doc->total, 'bad-total');
        $this->assertEquals(33.35, $doc->totaliva, 'bad-totaliva');
        $this->assertEquals(18.0, $doc->totalirpf, 'bad-totalirpf');
        $this->assertEquals(0.0, $doc->totalrecargo, 'bad-totalrecargo');
        $this->assertEquals(0.0, $doc->totalsuplidos, 'bad-totalsuplidos');
    }

    public function testRE()
    {
        $doc = new PresupuestoCliente();

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
    }

    public function testSupplied()
    {
        $doc = new PresupuestoCliente();

        // primera línea
        $line1 = $doc->getNewLine();
        $line1->cantidad = 3;
        $line1->pvpunitario = 50;
        $line1->iva = 21;
        $line1->recargo = 0.0;

        // segunda línea
        $line2 = $doc->getNewLine();
        $line2->cantidad = 1;
        $line2->pvpunitario = 20;
        $line2->iva = 0.0;
        $line2->recargo = 0.0;
        $line2->suplido = true;

        // tercera línea
        $line3 = $doc->getNewLine();
        $line3->cantidad = 2;
        $line3->pvpunitario = 15;
        $line3->iva = 0.0;
        $line3->recargo = 0.0;
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
}
