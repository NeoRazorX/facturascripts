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
use FacturaScripts\Core\Model\LineaPedidoCliente;
use FacturaScripts\Core\Model\PedidoCliente;
use FacturaScripts\Core\Model\PresupuestoCliente;
use FacturaScripts\Core\Model\Stock;
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

    /**
     * Al eliminar una línea de un albarán generado desde un pedido, se debe restaurar
     * el servido de la línea del pedido y, con ello, el stock reservado del producto.
     * La otra línea del albarán no debe verse afectada.
     */
    public function testDeleteChildLineRestoresParentServedAndStock(): void
    {
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'customer-cant-save');

        // creamos dos productos que permiten vender sin existencias
        $productA = $this->getRandomProduct();
        $productA->ventasinstock = true;
        $this->assertTrue($productA->save(), 'product-a-cant-save');

        $productB = $this->getRandomProduct();
        $productB->ventasinstock = true;
        $this->assertTrue($productB->save(), 'product-b-cant-save');

        // creamos el pedido con una línea de 10 unidades y otra de 5
        $order = new PedidoCliente();
        $this->assertTrue($order->setSubject($subject), 'order-cant-set-subject');
        $this->assertTrue($order->save(), 'order-cant-save');

        $orderLineA = $order->getNewProductLine($productA->referencia);
        $orderLineA->cantidad = 10;
        $this->assertTrue($orderLineA->save(), 'order-line-a-cant-save');

        $orderLineB = $order->getNewProductLine($productB->referencia);
        $orderLineB->cantidad = 5;
        $this->assertTrue($orderLineB->save(), 'order-line-b-cant-save');

        // pedido aprobado: todo reservado
        $stockA = new Stock();
        $stockA->loadWhereEq('referencia', $productA->referencia);
        $this->assertEquals(10, $stockA->reservada, 'order-bad-reserved-a');

        // generamos el albarán con las dos líneas completas
        $generator = new BusinessDocumentGenerator();
        $this->assertTrue($generator->generate(
            $order,
            'AlbaranCliente',
            [$orderLineA, $orderLineB],
            [$orderLineA->id() => 10, $orderLineB->id() => 5]
        ), 'delivery-note-cant-generate');
        $deliveryNote = $generator->getLastDocs()[0];

        // albarán generado: ya no queda nada reservado del producto A
        $stockA->loadWhereEq('referencia', $productA->referencia);
        $this->assertEquals(0, $stockA->reservada, 'delivery-note-bad-reserved-a');

        // eliminamos solo la línea del producto A del albarán
        foreach ($deliveryNote->getLines() as $line) {
            if ($line->referencia === $productA->referencia) {
                $this->assertTrue($line->delete(), 'delivery-line-a-cant-delete');
            }
        }

        // el producto A vuelve a estar reservado y su línea del pedido pendiente de servir
        $stockA->loadWhereEq('referencia', $productA->referencia);
        $this->assertEquals(10, $stockA->reservada, 'bad-reserved-a-after-delete');

        $reloadedLineA = new LineaPedidoCliente();
        $reloadedLineA->loadFromCode($orderLineA->id());
        $this->assertEquals(0, $reloadedLineA->servido, 'bad-served-a-after-delete');

        // la línea del producto B no ha cambiado
        $reloadedLineB = new LineaPedidoCliente();
        $reloadedLineB->loadFromCode($orderLineB->id());
        $this->assertEquals(5, $reloadedLineB->servido, 'bad-served-b-after-delete');

        // limpieza: cada entidad por separado para que un fallo no impida borrar el resto
        foreach ([$deliveryNote, $order, $subject->getDefaultAddress(), $subject, $productA, $productB] as $model) {
            try {
                $model->delete();
            } catch (\Throwable $e) {
            }
        }
    }
}
