<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Model\Base;

use FacturaScripts\Core\Lib\BusinessDocumentGenerator;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\PresupuestoProveedor;
use FacturaScripts\Core\Model\Retencion;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class BusinessDocumentLineTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testLineWithoutRelations(): void
    {
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'customer-cant-save');

        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject), 'document-cant-set-subject');
        $this->assertTrue($doc->save(), 'document-cant-save');

        $line = $doc->getNewLine();
        $line->cantidad = 5;
        $line->descripcion = 'Linea sin relaciones';
        $line->pvpunitario = 10;
        $this->assertTrue($line->save(), 'line-cant-save');
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'document-cant-calculate');

        $this->assertNull($line->getParentLine(), 'line-parent-should-be-null');
        $this->assertSame([], $line->childrenLines(), 'line-children-should-be-empty');

        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($subject->delete(), 'customer-cant-delete');
    }

    public function testLineRelationsAfterTransformation(): void
    {
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'customer-cant-save');

        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject), 'document-cant-set-subject');
        $this->assertTrue($doc->save(), 'document-cant-save');

        $line = $doc->getNewLine();
        $line->cantidad = 5;
        $line->descripcion = 'Linea con transformaciones';
        $line->pvpunitario = 10;
        $this->assertTrue($line->save(), 'line-cant-save');
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'document-cant-calculate');

        $generator = new BusinessDocumentGenerator();
        $this->assertTrue(
            $generator->generate($doc, 'PedidoCliente', [$line], [$line->id() => 2]),
            'first-document-cant-generate'
        );
        $this->assertTrue(
            $generator->generate($doc, 'PedidoCliente', [$line], [$line->id() => 1]),
            'second-document-cant-generate'
        );

        $generatedDocs = $generator->getLastDocs();
        $this->assertCount(2, $generatedDocs, 'bad-generated-doc-count');

        $childLine1 = $generatedDocs[0]->getLines()[0];
        $childLine2 = $generatedDocs[1]->getLines()[0];
        $parentLine1 = $childLine1->getParentLine();
        $parentLine2 = $childLine2->getParentLine();

        $this->assertNotNull($parentLine1, 'first-child-parent-is-null');
        $this->assertNotNull($parentLine2, 'second-child-parent-is-null');
        $this->assertEquals($line->id(), $parentLine1->id(), 'first-child-bad-parent');
        $this->assertEquals($line->id(), $parentLine2->id(), 'second-child-bad-parent');

        $children = $line->childrenLines();
        $this->assertCount(2, $children, 'bad-child-line-count');
        $this->assertSame([$childLine1->id(), $childLine2->id()], array_map(static function ($childLine) {
            return $childLine->id();
        }, $children), 'bad-child-line-order');

        $this->assertTrue($generatedDocs[1]->delete(), 'second-generated-doc-cant-delete');
        $this->assertTrue($generatedDocs[0]->delete(), 'first-generated-doc-cant-delete');
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($subject->delete(), 'customer-cant-delete');
    }

    public function testSetRetention(): void
    {
        $retention = $this->createRetention('A', 7.5);

        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'customer-cant-save');

        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject), 'document-cant-set-subject');
        $this->assertTrue($doc->save(), 'document-cant-save');

        $line = $doc->getNewLine();
        $line->descripcion = 'test';
        $line->pvpunitario = 100;

        // asignamos la retención y se copia su porcentaje
        $line->setRetention($retention->codretencion);
        $this->assertSame($retention->codretencion, $line->codretencion, 'bad-codretencion');
        $this->assertEquals(7.5, $line->irpf, 'bad-irpf');

        // sin retención o con una que no existe, la línea se queda sin retención
        foreach ([null, '', 'NOT-EXISTS'] as $code) {
            $line->setRetention($retention->codretencion);
            $line->setRetention($code);
            $this->assertNull($line->codretencion, 'codretencion-not-removed');
            $this->assertEquals(0.0, $line->irpf, 'irpf-not-removed');
        }

        // se guarda y se recupera
        $line->setRetention($retention->codretencion);
        $this->assertTrue($line->save(), 'line-cant-save');
        $this->assertSame($retention->codretencion, $doc->getLines()[0]->codretencion, 'bad-saved-codretencion');

        // si el porcentaje no coincide con el de la retención, se quita el código al guardar
        $line->irpf = 15;
        $this->assertTrue($line->save(), 'line-cant-save-other-irpf');
        $this->assertNull($line->codretencion, 'codretencion-not-matching-irpf');

        // limpiamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($subject->delete(), 'customer-cant-delete');
        $this->assertTrue($retention->delete(), 'retention-cant-delete');
    }

    public function testNewLinesGetSubjectRetention(): void
    {
        // dos retenciones con el mismo porcentaje: el cliente y el proveedor usan la segunda
        $percentage = $this->randomPercentage();
        $retention1 = $this->createRetention('A', $percentage);
        $retention2 = $this->createRetention('B', $percentage);

        $customer = $this->getRandomCustomer();
        $customer->codretencion = $retention2->codretencion;
        $this->assertTrue($customer->save(), 'customer-cant-save');

        $supplier = $this->getRandomSupplier();
        $supplier->codretencion = $retention2->codretencion;
        $this->assertTrue($supplier->save(), 'supplier-cant-save');

        foreach ([[new PresupuestoCliente(), $customer], [new PresupuestoProveedor(), $supplier]] as [$doc, $subject]) {
            $this->assertTrue($doc->setSubject($subject), 'document-cant-set-subject');
            $this->assertTrue($doc->save(), 'document-cant-save');

            // la línea nueva lleva la retención del cliente/proveedor, no la primera con ese porcentaje
            $line = $doc->getNewLine();
            $this->assertSame($retention2->codretencion, $line->codretencion, 'new-line-without-subject-retention');
            $this->assertEquals($percentage, $line->irpf, 'new-line-bad-irpf');

            $line->descripcion = 'test';
            $line->pvpunitario = 100;
            $this->assertTrue($line->save(), 'line-cant-save');
            $this->assertTrue($doc->delete(), 'document-cant-delete');
        }

        // al borrar la retención, la línea se queda sin código pero conserva el porcentaje
        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($customer), 'document-cant-set-subject');
        $this->assertTrue($doc->save(), 'document-cant-save');
        $line = $doc->getNewLine();
        $line->descripcion = 'test';
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'line-cant-save');

        $this->assertTrue($retention2->delete(), 'retention-cant-delete');
        $this->assertTrue($line->reload(), 'line-cant-reload');
        $this->assertNull($line->codretencion, 'codretencion-not-set-null');
        $this->assertEquals($percentage, $line->irpf, 'irpf-lost');

        // limpiamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($customer->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($customer->delete(), 'customer-cant-delete');
        $this->assertTrue($supplier->getDefaultAddress()->delete(), 'supplier-address-cant-delete');
        $this->assertTrue($supplier->delete(), 'supplier-cant-delete');
        $this->assertTrue($retention1->delete(), 'retention-cant-delete');
    }

    private function createRetention(string $letter, float $percentage): Retencion
    {
        $retention = new Retencion();
        $retention->codretencion = 'TR' . $letter . Tools::randomString(6);
        $retention->descripcion = 'Test ' . $letter;
        $retention->porcentaje = $percentage;
        $this->assertTrue($retention->save(), 'retention-cant-save');

        return $retention;
    }

    private function randomPercentage(): float
    {
        // un porcentaje que no usen las retenciones del plan por defecto
        return round(30 + mt_rand(1, 60000) / 1000, 3);
    }
}
