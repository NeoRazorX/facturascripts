<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021  Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\BusinessDocumentTools;
use FacturaScripts\Core\Model\AlbaranProveedor;
use FacturaScripts\Test\Core\LogErrorsTrait;
use FacturaScripts\Test\Core\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class AlbaranProveedorTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreateEmpty()
    {
        // creamos el proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier-1');

        // creamos el albarán
        $doc = new AlbaranProveedor();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject-1');
        $this->assertTrue($doc->save(), 'can-not-create-albaran-proveedor-1');

        // comprobamos valores por defecto
        $this->assertEquals($subject->cifnif, $doc->cifnif, 'albaran-proveedor-bad-cifnif-1');
        $this->assertEquals($subject->codproveedor, $doc->codproveedor, 'albaran-proveedor-bad-codproveedor-1');
        $this->assertEquals($subject->razonsocial, $doc->nombre, 'albaran-proveedor-bad-nombre-1');
        $this->assertEquals(date('d-m-Y'), $doc->fecha, 'albaran-proveedor-bad-date-1');
        $this->assertEquals(0, $doc->dtopor1, 'albaran-proveedor-bad-dtopor1-1');
        $this->assertEquals(0, $doc->dtopor2, 'albaran-proveedor-bad-dtopor2-1');
        $this->assertEquals(0, $doc->netosindto, 'albaran-proveedor-bad-netosindto-1');
        $this->assertEquals(0, $doc->neto, 'albaran-proveedor-bad-neto-1');
        $this->assertEquals(0, $doc->total, 'albaran-proveedor-bad-total-1');
        $this->assertEquals(0, $doc->totaliva, 'albaran-proveedor-bad-totaliva-1');
        $this->assertEquals(0, $doc->totalrecargo, 'albaran-proveedor-bad-totalrecargo-1');
        $this->assertEquals(0, $doc->totalirpf, 'albaran-proveedor-bad-totalirpf-1');
        $this->assertEquals(0, $doc->totalsuplidos, 'albaran-proveedor-bad-totalsuplidos-1');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-proveedor-1');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-1');
    }

    public function testCreateOneLine()
    {
        // creamos el proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier-2');

        // creamos el albarán
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-proveedor-2');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line-2');

        // actualizamos los totales
        $tool = new BusinessDocumentTools();
        $tool->recalculate($doc);
        $this->assertTrue($doc->save(), 'can-not-update-albaran-proveedor-2');

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'albaran-proveedor-bad-neto-2');
        $this->assertEquals(121, $doc->total, 'albaran-proveedor-bad-total-2');
        $this->assertEquals(21, $doc->totaliva, 'albaran-proveedor-bad-totaliva-2');
        $this->assertEquals(0, $doc->totalrecargo, 'albaran-proveedor-bad-totalrecargo-2');
        $this->assertEquals(0, $doc->totalirpf, 'albaran-proveedor-bad-totalirpf-2');
        $this->assertEquals(0, $doc->totalsuplidos, 'albaran-proveedor-bad-totalsuplidos-2');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-proveedor-2');
        $this->assertFalse($line->exists(), 'linea-albaran-proveedor-still-exists-2');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-2');
    }

    protected function tearDown()
    {
        $this->logErrors();
    }
}
