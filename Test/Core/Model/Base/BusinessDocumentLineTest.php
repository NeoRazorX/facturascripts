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
        // creamos una retención de prueba
        $retencion = new Retencion();
        $retencion->codretencion = 'T' . Tools::randomString(5);
        $retencion->descripcion = 'Retención de prueba';
        $retencion->porcentaje = 7;
        $this->assertTrue($retencion->save(), 'retention-cant-save');

        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'customer-cant-save');

        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject), 'document-cant-set-subject');
        $this->assertTrue($doc->save(), 'document-cant-save');

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->descripcion = 'Linea con retención';
        $line->pvpunitario = 100;

        // asignamos la retención de prueba y comprobamos codretencion e irpf
        $line->setRetention($retencion->codretencion);
        $this->assertEquals($retencion->codretencion, $line->codretencion, 'bad-codretencion');
        $this->assertEquals((float)$retencion->porcentaje, $line->irpf, 'bad-irpf');

        // quitamos la retención pasando null
        $line->setRetention(null);
        $this->assertNull($line->codretencion, 'codretencion-should-be-null');
        $this->assertEquals(0.0, $line->irpf, 'irpf-should-be-zero');

        // volvemos a asignarla y luego probamos con cadena vacía
        $line->setRetention($retencion->codretencion);
        $line->setRetention('');
        $this->assertNull($line->codretencion, 'codretencion-should-be-null-with-empty-string');
        $this->assertEquals(0.0, $line->irpf, 'irpf-should-be-zero-with-empty-string');

        // probamos con un código de retención que no existe
        $line->setRetention($retencion->codretencion);
        $line->setRetention('CODIGO-NO-EXISTE');
        $this->assertNull($line->codretencion, 'codretencion-should-be-null-with-unknown-code');
        $this->assertEquals(0.0, $line->irpf, 'irpf-should-be-zero-with-unknown-code');

        // guardamos la línea con la retención asignada y comprobamos que persiste
        $line->setRetention($retencion->codretencion);
        $this->assertTrue($line->save(), 'line-cant-save');

        $loadedLine = $doc->getLines()[0];
        $this->assertEquals($retencion->codretencion, $loadedLine->codretencion, 'bad-persisted-codretencion');
        $this->assertEquals((float)$retencion->porcentaje, $loadedLine->irpf, 'bad-persisted-irpf');

        // limpiamos
        $this->assertTrue($doc->delete(), 'document-cant-delete');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'address-cant-delete');
        $this->assertTrue($subject->delete(), 'customer-cant-delete');
        $this->assertTrue($retencion->delete(), 'retention-cant-delete');
    }
}
