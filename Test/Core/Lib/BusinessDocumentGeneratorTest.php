<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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

use FacturaScripts\Core\Lib\BusinessDocumentGenerator;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\AlbaranProveedor;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class BusinessDocumentGeneratorTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testSetNewDocDateAppliedOnPurchaseApproval(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier');

        // creamos un albarán de compra con una línea
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-save-albaran-proveedor');

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line');

        // buscamos un estado que genere documento
        $targetStatus = null;
        foreach ($doc->getAvailableStatus() as $sta) {
            if ($sta->generadoc) {
                $targetStatus = $sta;
                break;
            }
        }
        $this->assertNotNull($targetStatus, 'no-status-with-generadoc-found');

        // establecemos la fecha deseada para el nuevo documento
        $expectedDate = '01-06-2025';
        BusinessDocumentGenerator::setNewDocDate('2025-06-01');

        // aprobamos el albarán
        $doc->idestado = $targetStatus->idestado;
        $this->assertTrue($doc->save(), 'can-not-approve-albaran-proveedor');

        // comprobamos que la fecha del documento generado es la indicada
        $children = $doc->childrenDocuments();
        $this->assertNotEmpty($children, 'no-child-document-generated');
        foreach ($children as $child) {
            $this->assertEquals($expectedDate, $child->fecha, 'child-document-has-wrong-date');
        }

        // limpiamos
        foreach ($children as $child) {
            $this->assertTrue($child->delete(), 'can-not-delete-child');
        }
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-proveedor');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-supplier');
    }

    public function testSetNewDocDateAppliedOnSalesApproval(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos un albarán de venta con una línea
        $doc = new AlbaranCliente();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-save-albaran-cliente');

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 50;
        $this->assertTrue($line->save(), 'can-not-save-line');

        // buscamos un estado que genere documento
        $targetStatus = null;
        foreach ($doc->getAvailableStatus() as $sta) {
            if ($sta->generadoc) {
                $targetStatus = $sta;
                break;
            }
        }
        $this->assertNotNull($targetStatus, 'no-status-with-generadoc-found');

        // establecemos la fecha deseada para el nuevo documento
        $expectedDate = '15-03-2024';
        BusinessDocumentGenerator::setNewDocDate('2024-03-15');

        // aprobamos el albarán
        $doc->idestado = $targetStatus->idestado;
        $this->assertTrue($doc->save(), 'can-not-approve-albaran-cliente');

        // comprobamos que la fecha del documento generado es la indicada
        $children = $doc->childrenDocuments();
        $this->assertNotEmpty($children, 'no-child-document-generated');
        foreach ($children as $child) {
            $this->assertEquals($expectedDate, $child->fecha, 'child-document-has-wrong-date');
        }

        // limpiamos
        foreach ($children as $child) {
            $this->assertTrue($child->delete(), 'can-not-delete-child');
        }
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-cliente');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testNewDocDateResetAfterUse(): void
    {
        // verificamos que setNewDocDate se resetea tras el primer generate()
        // y el segundo documento generado usa la fecha del día
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier');

        // primer albarán: aprobamos con fecha específica
        $doc1 = new AlbaranProveedor();
        $doc1->setSubject($subject);
        $this->assertTrue($doc1->save(), 'can-not-save-doc1');

        $line1 = $doc1->getNewLine();
        $line1->cantidad = 1;
        $line1->pvpunitario = 10;
        $this->assertTrue($line1->save(), 'can-not-save-line1');

        $targetStatus = null;
        foreach ($doc1->getAvailableStatus() as $sta) {
            if ($sta->generadoc) {
                $targetStatus = $sta;
                break;
            }
        }
        $this->assertNotNull($targetStatus, 'no-status-with-generadoc-found');

        BusinessDocumentGenerator::setNewDocDate('2023-01-20');
        $doc1->idestado = $targetStatus->idestado;
        $this->assertTrue($doc1->save(), 'can-not-approve-doc1');

        // segundo albarán: aprobamos SIN setNewDocDate
        $doc2 = new AlbaranProveedor();
        $doc2->setSubject($subject);
        $this->assertTrue($doc2->save(), 'can-not-save-doc2');

        $line2 = $doc2->getNewLine();
        $line2->cantidad = 1;
        $line2->pvpunitario = 10;
        $this->assertTrue($line2->save(), 'can-not-save-line2');

        $doc2->idestado = $targetStatus->idestado;
        $this->assertTrue($doc2->save(), 'can-not-approve-doc2');

        // el segundo documento generado NO debe tener la fecha del primero
        $children2 = $doc2->childrenDocuments();
        $this->assertNotEmpty($children2, 'no-child-document-generated-2');
        foreach ($children2 as $child) {
            $this->assertNotEquals('20-01-2023', $child->fecha, 'new-doc-date-was-not-reset');
        }

        // limpiamos
        foreach ($doc1->childrenDocuments() as $child) {
            $child->delete();
        }
        foreach ($children2 as $child) {
            $child->delete();
        }
        $doc1->delete();
        $doc2->delete();
        $subject->getDefaultAddress()->delete();
        $subject->delete();
    }

    public function testWithoutNewDocDateUsesTodayDate(): void
    {
        // sin setNewDocDate, el documento generado tiene la fecha de hoy
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier');

        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-save-albaran');

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 10;
        $this->assertTrue($line->save(), 'can-not-save-line');

        $targetStatus = null;
        foreach ($doc->getAvailableStatus() as $sta) {
            if ($sta->generadoc) {
                $targetStatus = $sta;
                break;
            }
        }
        $this->assertNotNull($targetStatus, 'no-status-with-generadoc-found');

        $doc->idestado = $targetStatus->idestado;
        $this->assertTrue($doc->save(), 'can-not-approve-albaran');

        $children = $doc->childrenDocuments();
        $this->assertNotEmpty($children, 'no-child-document-generated');
        foreach ($children as $child) {
            $this->assertEquals(date('d-m-Y'), $child->fecha, 'child-without-new-doc-date-has-wrong-date');
        }

        // limpiamos
        foreach ($children as $child) {
            $child->delete();
        }
        $doc->delete();
        $subject->getDefaultAddress()->delete();
        $subject->delete();
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
