<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\AlbaranProveedor;
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\Stock;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class AlbaranProveedorTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testDefaultValues(): void
    {
        // creamos un albarán
        $doc = new AlbaranProveedor();

        // comprobamos que tiene valores por defecto
        $this->assertNotEmpty($doc->codalmacen, 'empty-warehouse');
        $this->assertNotEmpty($doc->coddivisa, 'empty-currency');
        $this->assertNotEmpty($doc->codserie, 'empty-serie');
        $this->assertNotEmpty($doc->fecha, 'empty-date');
        $this->assertNotEmpty($doc->hora, 'empty-time');
    }

    public function testSetAuthor(): void
    {
        // creamos un almacén
        $warehouse = $this->getRandomWarehouse();
        $this->assertTrue($warehouse->save(), 'can-not-create-warehouse');

        // creamos un usuario
        $user = $this->getRandomUser();
        $user->codalmacen = $warehouse->codalmacen;

        // creamos un albarán y le asignamos el usuario
        $doc = new AlbaranProveedor();
        $this->assertTrue($doc->setAuthor($user), 'can-not-set-user');

        // comprobamos que se le han asignado el usuario y el almacén
        $this->assertEquals($user->codalmacen, $doc->codalmacen, 'albaran-proveedor-bad-warehouse');
        $this->assertEquals($user->nick, $doc->nick, 'albaran-proveedor-bad-nick');

        // eliminamos
        $this->assertTrue($warehouse->delete(), 'can-not-delete-warehouse');
    }

    public function testSetSubject(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier-1');

        // creamos un albarán y le asignamos el proveedor
        $doc = new AlbaranProveedor();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject-1');

        // comprobamos que se han asignado los datos del proveedor
        $this->assertEquals($subject->cifnif, $doc->cifnif, 'albaran-proveedor-bad-cifnif-1');
        $this->assertEquals($subject->codproveedor, $doc->codproveedor, 'albaran-proveedor-bad-codproveedor-1');
        $this->assertEquals($subject->razonsocial, $doc->nombre, 'albaran-proveedor-bad-nombre-1');

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-1');
    }

    public function testCreateEmpty(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier-1');

        // creamos un albarán
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-proveedor-1');

        // comprobamos valores por defecto
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
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-1');
    }

    public function testCreateWithoutSubject(): void
    {
        $doc = new AlbaranProveedor();
        $this->assertFalse($doc->save(), 'can-create-albaran-proveedor-without-subject');
    }

    public function testCreateOneLine(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier-2');

        // creamos un albarán
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-proveedor-2');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line-2');
        $this->assertNotEmpty($line->idlinea, 'empty-line-id-2');
        $this->assertTrue($line->exists(), 'line-not-persist-2');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-albaran-proveedor-2');

        // obtenemos el impuesto predeterminado
        $default_tax = Impuestos::default();
        $total_iva = (100 * $default_tax->iva / 100);
        $total = 100 + $total_iva;

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'albaran-proveedor-bad-neto-2');
        $this->assertEquals($total, $doc->total, 'albaran-proveedor-bad-total-2');
        $this->assertEquals($total_iva, $doc->totaliva, 'albaran-proveedor-bad-totaliva-2');
        $this->assertEquals(0, $doc->totalrecargo, 'albaran-proveedor-bad-totalrecargo-2');
        $this->assertEquals(0, $doc->totalirpf, 'albaran-proveedor-bad-totalirpf-2');
        $this->assertEquals(0, $doc->totalsuplidos, 'albaran-proveedor-bad-totalsuplidos-2');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-proveedor-2');
        $this->assertFalse($line->exists(), 'linea-albaran-proveedor-still-exists-2');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-2');
    }

    public function testCreateProductLine(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier-3');

        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'can-not-save-supplier-3');

        // creamos un albarán
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-albaran-proveedor-3');

        // añadimos el producto
        $line = $doc->getNewProductLine($product->referencia);
        $line->pvpunitario = 10;
        $this->assertTrue($line->save(), 'can-not-save-line-3');

        // recargamos producto y comprobamos el stock
        $product->reload();
        $this->assertEquals(1, $product->stockfis, 'albaran-proveedor-product-do-not-update-stock');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-albaran-proveedor-3');

        // obtenemos el impuesto predeterminado
        $default_tax = Impuestos::default();
        $total_iva = (10 * $default_tax->iva / 100);
        $total = 10 + $total_iva;

        // comprobamos
        $this->assertEquals(10, $doc->neto, 'albaran-proveedor-bad-neto-3');
        $this->assertEquals($total, $doc->total, 'albaran-proveedor-bad-total-3');
        $this->assertEquals($total_iva, $doc->totaliva, 'albaran-proveedor-bad-totaliva-3');

        // modificamos la cantidad
        $line->cantidad = 10;
        $this->assertTrue($line->save(), 'can-not-update-line-3');

        // recargamos producto y comprobamos el stock
        $product->reload();
        $this->assertEquals(10, $product->stockfis, 'albaran-proveedor-product-do-not-update-stock');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-albaran-proveedor-3');

        // obtenemos el impuesto predeterminado
        $total_iva = (100 * $default_tax->iva / 100);
        $total = 100 + $total_iva;

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'albaran-proveedor-bad-neto-3');
        $this->assertEquals($total, $doc->total, 'albaran-proveedor-bad-total-3');
        $this->assertEquals($total_iva, $doc->totaliva, 'albaran-proveedor-bad-totaliva-3');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran-proveedor-3');
        $this->assertFalse($line->exists(), 'linea-albaran-proveedor-still-exists-3');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-3');

        // recargamos producto y comprobamos el stock
        $product->reload();
        $this->assertEquals(0, $product->stockfis, 'albaran-proveedor-product-do-not-update-stock');

        // eliminamos el producto
        $this->assertTrue($product->delete(), 'can-not-delete-product-3');
    }

    public function testPropertiesLength(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-customer-1');

        // Definir los campos a validar: campo => [longitud_máxima, longitud_invalida]
        $campos = [
            'cifnif' => [30, 31],
            'codigo' => [20, 21],
            'nombre' => [100, 101],
            'numproveedor' => [50, 51],
            'operacion' => [20, 21],
        ];

        foreach ($campos as $campo => [$valido, $invalido]) {
            // Creamos un nuevo albarán
            $doc = new AlbaranProveedor();
            $doc->setSubject($subject);

            // Asignamos el valor inválido en el campo a probar
            $doc->{$campo} = Tools::randomString($invalido);
            $this->assertFalse($doc->save(), "can-save-albaranProveedor-bad-{$campo}");

            // Corregimos el campo y comprobamos que ahora sí se puede guardar
            $doc->{$campo} = Tools::randomString($valido);
            $this->assertTrue($doc->save(), "cannot-save-albaranProveedor-fixed-{$campo}");

            // Limpiar
            $this->assertTrue($doc->delete(), "cannot-delete-albaranProveedor-{$campo}");
        }

        // Eliminamos el proveedor
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testSecondCompany(): void
    {
        // creamos la empresa 2
        $company2 = new Empresa();
        $company2->nombre = 'Company 2';
        $company2->nombrecorto = 'Company-2';
        $this->assertTrue($company2->save(), 'company-cant-save');

        // obtenemos el almacén de la empresa 2
        $warehouse = new Almacen();
        $where = [new DataBaseWhere('idempresa', $company2->idempresa)];
        $warehouse->loadWhere($where);

        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-customer-2');

        // creamos un albarán y le asignamos el proveedor y el almacén
        $doc = new AlbaranProveedor();
        $doc->setSubject($subject);
        $doc->codalmacen = $warehouse->codalmacen;
        $this->assertTrue($doc->save(), 'albaran-cant-save');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line-2');

        // aprobamos
        foreach ($doc->getAvailableStatus() as $status) {
            if (empty($status->generadoc)) {
                continue;
            }

            // al cambiar el estado genera una nueva factura
            $doc->idestado = $status->idestado;
            $this->assertTrue($doc->save(), 'albaran-cant-save');

            // comprobamos que la factura se ha creado
            $children = $doc->childrenDocuments();
            $this->assertNotEmpty($children, 'facturas-no-creadas');
            foreach ($children as $child) {
                // comprobamos que la factura tiene el mismo almacén y la misma empresa
                $this->assertEquals($warehouse->codalmacen, $child->codalmacen, 'factura-bad-idempresa');
                $this->assertEquals($company2->idempresa, $child->idempresa, 'factura-bad-idempresa');
            }
        }

        // eliminamos
        $children = $doc->childrenDocuments();
        $this->assertNotEmpty($children, 'facturas-no-creadas');
        foreach ($children as $child) {
            $this->assertTrue($child->delete(), 'factura-cant-delete');
        }
        $this->assertTrue($doc->delete());
        $this->assertTrue($subject->getDefaultAddress()->delete());
        $this->assertTrue($subject->delete());
        $this->assertTrue($company2->delete());
    }

    /**
     * Testea que al eliminar una linea de un albarán (generado de un pedido) se restaura el stock
     */
    public function testDeleteLineFromOrderRestoresServed(): void
    {
        // creamos un producto con stock
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save(), 'fallo al guardar el producto');

        $stock = new Stock();
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $stock->cantidad = 100;
        $this->assertTrue($stock->save(), 'fallo al guardar el stock');

        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier-2');

        // creamos un pedido
        $order = new PedidoProveedor();
        $order->setSubject($subject);
        $this->assertTrue($order->save(), 'can-not-create-pedido-proveedor-2');

        // añadimos una línea al pedido
        $orderLine = $order->getNewProductLine($product->referencia);
        $orderLine->cantidad = 10;
        $this->assertTrue($orderLine->save(), 'fallo al guardar la línea del pedido');

        // Para convertir este pedido en albarán, simplemente tenemos que cambiar el estado del pedido
        // a uno que en la columna generadoc tenga AlbaranProveedor.
        // En la configuración por defecto, el idestado = 16 se usa para pasar de PedidoProveedor a AlbaranProveedor.
        $order->idestado = 16;
        $this->assertTrue($order->save(), 'fallo al cambiar el estado del pedido');

        // Obtenemos el albarán generado
        $children = $order->childrenDocuments();
        $this->assertNotEmpty($children, 'No se ha generado el albarán');
        $deliveryNote = $children[0];
        $this->assertInstanceOf(AlbaranProveedor::class, $deliveryNote);

        // obtenemos la línea del albarán
        $deliveryNoteLines = $deliveryNote->getLines();
        $this->assertCount(1, $deliveryNoteLines, 'el albarán no tiene líneas');
        $deliveryNoteLine = $deliveryNoteLines[0];

        // primero comprobar que la cantidad servida es 10 y correcta
        // recargamos la línea del pedido
        $this->assertTrue($orderLine->reload(), 'fallo al recargar la línea del pedido');
        // la cantidad servida en el pedido debe ser 10
        $this->assertEquals(10, $orderLine->servido, 'la cantidad servida del pedido no es 10');

        // comprobamos que la cantidad servida se resta si hay menos en el albarán
        // actualizamos la cantidad de la línea (restar cantidad)
        $deliveryNoteLine->cantidad = 5;
        $this->assertTrue($deliveryNoteLine->save(), 'fallo al actualizar la cantidad');

        // recargamos la línea del pedido
        $this->assertTrue($orderLine->reload(), 'fallo al recargar la línea del pedido');
        // la cantidad servida en el pedido debe ser 5
        $this->assertEquals(5, $orderLine->servido, 'la cantidad servida del pedido no es 5');

        // comprobamos que la cantidad servida se mantiene máxima y no sobrepasa el pedido
        // actualizamos la cantidad de la línea para sumar 10 extra
        $deliveryNoteLine->cantidad = 15;
        $this->assertTrue($deliveryNoteLine->save(), 'fallo al actualizar la cantidad');

        // recargamos la línea del pedido
        $this->assertTrue($orderLine->reload(), 'fallo al recargar la línea del pedido');
        // la cantidad servida en el pedido debe ser 15
        $this->assertEquals(10, $orderLine->servido, 'la cantidad servida del pedido no es 15');

        // eliminamos la linea con la intención de ver que servido se mantiene en 0
        // eliminamos la línea del albarán
        $this->assertTrue($deliveryNoteLine->delete(), 'fallo al eliminar la línea del albarán');

        // recargamos la línea del pedido
        $this->assertTrue($orderLine->reload(), 'fallo al recargar la línea del pedido');
        // BUG: la cantidad servida debería volver a 0, pero no lo hace
        $this->assertEquals(0, $orderLine->servido, 'la cantidad servida no se ha restaurado a 0');

        // limpieza
        $this->assertTrue($deliveryNote->delete(), 'fallo al eliminar el albarán');
        $this->assertTrue($order->delete(), 'fallo al eliminar el pedido');
        $this->assertTrue($product->delete(), 'fallo al eliminar el producto');
        $this->assertTrue($subject->delete(), 'fallo al eliminar el proveedor');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
