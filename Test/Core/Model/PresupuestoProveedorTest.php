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
use FacturaScripts\Core\Model\Almacen;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Model\PresupuestoProveedor;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class PresupuestoProveedorTest extends TestCase
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
        // creamos un presupuesto
        $doc = new PresupuestoProveedor();

        // comprobamos que ya tiene almacén, divisa, serie, fecha y hora predeterminada
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
        $this->assertTrue($warehouse->save());

        // creamos un usuario y le asignamos el almacén
        $user = $this->getRandomUser();
        $user->codalmacen = $warehouse->codalmacen;

        // creamos un presupuesto y le asignamos el usuario
        $doc = new PresupuestoProveedor();
        $this->assertTrue($doc->setAuthor($user), 'can-not-set-user');

        // comprobamos que se le ha asignado usuario y almacén
        $this->assertEquals($user->codalmacen, $doc->codalmacen, 'presupuesto-proveedor-bad-warehouse');
        $this->assertEquals($user->nick, $doc->nick, 'presupuesto-proveedor-bad-nick');

        // eliminamos
        $this->assertTrue($warehouse->delete(), 'can-not-delete-warehouse');
    }

    public function testSetSubject(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save());

        // creamos un presupuesto y le asignamos el proveedor
        $doc = new PresupuestoProveedor();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject-1');

        // comprobamos que se han asignado los datos del proveedor
        $this->assertEquals($subject->cifnif, $doc->cifnif, 'presupuesto-proveedor-bad-cifnif-1');
        $this->assertEquals($subject->codproveedor, $doc->codproveedor, 'presupuesto-proveedor-bad-codproveedor-1');
        $this->assertEquals($subject->razonsocial, $doc->nombre, 'presupuesto-proveedor-bad-nombre-1');

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-1');
    }

    public function testCreateEmpty(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save());

        // creamos un presupuesto y le asignamos el proveedor
        $doc = new PresupuestoProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-proveedor-1');

        // comprobamos valores por defecto
        $this->assertEquals(date('d-m-Y'), $doc->fecha, 'presupuesto-proveedor-bad-date-1');
        $this->assertEquals(0, $doc->dtopor1, 'presupuesto-proveedor-bad-dtopor1-1');
        $this->assertEquals(0, $doc->dtopor2, 'presupuesto-proveedor-bad-dtopor2-1');
        $this->assertEquals(0, $doc->netosindto, 'presupuesto-proveedor-bad-netosindto-1');
        $this->assertEquals(0, $doc->neto, 'presupuesto-proveedor-bad-neto-1');
        $this->assertEquals(0, $doc->total, 'presupuesto-proveedor-bad-total-1');
        $this->assertEquals(0, $doc->totaliva, 'presupuesto-proveedor-bad-totaliva-1');
        $this->assertEquals(0, $doc->totalrecargo, 'presupuesto-proveedor-bad-totalrecargo-1');
        $this->assertEquals(0, $doc->totalirpf, 'presupuesto-proveedor-bad-totalirpf-1');
        $this->assertEquals(0, $doc->totalsuplidos, 'presupuesto-proveedor-bad-totalsuplidos-1');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-presupuesto-proveedor-1');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-1');
    }

    public function testCreateWithoutSubject(): void
    {
        $doc = new PresupuestoProveedor();
        $this->assertFalse($doc->save(), 'can-create-presupuesto-proveedor-without-subject');
    }

    public function testCreateOneLine(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save());

        // creamos un presupuesto y le asignamos el proveedor
        $doc = new PresupuestoProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-proveedor-2');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line-2');
        $this->assertNotEmpty($line->idlinea, 'empty-line-id-2');
        $this->assertTrue($line->exists(), 'line-not-persist-2');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-presupuesto-proveedor-2');

        // obtenemos el impuesto predeterminado
        $default_tax = Impuestos::default();
        $total_iva = (100 * $default_tax->iva / 100);
        $total = 100 + $total_iva;

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'presupuesto-proveedor-bad-neto-2');
        $this->assertEquals($total, $doc->total, 'presupuesto-proveedor-bad-total-2');
        $this->assertEquals($total_iva, $doc->totaliva, 'presupuesto-proveedor-bad-totaliva-2');
        $this->assertEquals(0, $doc->totalrecargo, 'presupuesto-proveedor-bad-totalrecargo-2');
        $this->assertEquals(0, $doc->totalirpf, 'presupuesto-proveedor-bad-totalirpf-2');
        $this->assertEquals(0, $doc->totalsuplidos, 'presupuesto-proveedor-bad-totalsuplidos-2');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-presupuesto-proveedor-2');
        $this->assertFalse($line->exists(), 'linea-presupuesto-proveedor-still-exists-2');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-2');
    }

    public function testCreateProductLine(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save());

        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save());

        // creamos un presupuesto y le asignamos el proveedor
        $doc = new PresupuestoProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-proveedor-3');

        // añadimos el producto al presupuesto
        $line = $doc->getNewProductLine($product->referencia);
        $line->pvpunitario = 10;
        $this->assertTrue($line->save(), 'can-not-save-line-3');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-presupuesto-proveedor-3');

        // obtenemos el impuesto predeterminado
        $default_tax = Impuestos::default();
        $total_iva = (10 * $default_tax->iva / 100);
        $total = 10 + $total_iva;

        // comprobamos
        $this->assertEquals(10, $doc->neto, 'presupuesto-proveedor-bad-neto-3');
        $this->assertEquals($total, $doc->total, 'presupuesto-proveedor-bad-total-3');
        $this->assertEquals($total_iva, $doc->totaliva, 'presupuesto-proveedor-bad-totaliva-3');

        // modificamos la cantidad de la línea
        $line->cantidad = 10;
        $this->assertTrue($line->save(), 'can-not-update-line-3');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-presupuesto-proveedor-3');

        // obtenemos el impuesto predeterminado
        $total_iva = (100 * $default_tax->iva / 100);
        $total = 100 + $total_iva;

        // comprobamos
        $this->assertEquals(100, $doc->neto, 'presupuesto-proveedor-bad-neto-3');
        $this->assertEquals($total, $doc->total, 'presupuesto-proveedor-bad-total-3');
        $this->assertEquals($total_iva, $doc->totaliva, 'presupuesto-proveedor-bad-totaliva-3');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-presupuesto-proveedor-3');
        $this->assertFalse($line->exists(), 'linea-presupuesto-proveedor-still-exists-3');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-3');
        $this->assertTrue($product->delete(), 'can-not-delete-product-3');
    }

    public function testCreateProductNotFoundLine(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save());

        // creamos un producto
        $product = $this->getRandomProduct();
        $this->assertTrue($product->save());

        // eliminamos el producto para asegurar que no existe
        $this->assertTrue($product->delete(), 'can-not-delete-product-3');

        // creamos un presupuesto
        $doc = new PresupuestoProveedor();
        $doc->setSubject($subject);
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-proveedor-3');

        // añadimos el producto al presupuesto
        $line = $doc->getNewProductLine($product->referencia);
        $line->pvpunitario = 10;
        $this->assertTrue($line->save(), 'can-not-save-line-3');

        // la línea debe tener la referencia del producto incluso aunque este no exista
        $this->assertEquals($product->referencia, $line->referencia, 'line-bad-referencia-3');

        // eliminamos
        $this->assertTrue($doc->delete(), 'can-not-delete-presupuesto-proveedor-3');
        $this->assertFalse($line->exists(), 'linea-presupuesto-proveedor-still-exists-3');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'can-not-delete-proveedor-3');
    }

    public function testPropertiesLength(): void
    {
        // Definir los campos a validar: campo => [longitud_máxima, longitud_invalida]
        $campos = [
            'cifnif' => [30, 31],
            'codigo' => [20, 21],
            'nombre' => [100, 101],
            'numproveedor' => [50, 51],
            'operacion' => [20, 21],
        ];

        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save());

        foreach ($campos as $campo => [$valido, $invalido]) {
            // Creamos un nuevo almacén
            $doc = new PresupuestoProveedor();

            // campo obligatorio (not null)
            $doc->setSubject($subject);

            // Asignamos el valor inválido en el campo a probar
            $doc->{$campo} = Tools::randomString($invalido);
            $this->assertFalse($doc->save(), "can-save-pedidoProveedor-bad-{$campo}");

            // Corregimos el campo y comprobamos que ahora sí se puede guardar
            $doc->{$campo} = Tools::randomString($valido);
            $this->assertTrue($doc->save(), "cannot-save-pedidoProveedor-fixed-{$campo}");

            // Limpiar
            $this->assertTrue($doc->delete(), "cannot-delete-pedidoProveedor-{$campo}");
        }

        // eliminamos
        $this->assertTrue($subject->getDefaultAddress()->delete());
        $this->assertTrue($subject->delete());
    }

    public function testSecondCompany(): void
    {
        // creamos la empresa 2
        $company2 = new Empresa();
        $company2->nombre = 'Company 2';
        $company2->nombrecorto = 'Company-2';
        $this->assertTrue($company2->save());

        // obtenemos el almacén de la empresa 2
        $warehouse = new Almacen();
        $where = [new DataBaseWhere('idempresa', $company2->idempresa)];
        $warehouse->loadWhere($where);

        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save());

        // creamos un presupuesto en la empresa 2 y le asignamos el proveedor
        $doc = new PresupuestoProveedor();
        $doc->setSubject($subject);
        $doc->codalmacen = $warehouse->codalmacen;
        $this->assertTrue($doc->save(), 'presupuesto-cant-save');

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
            $this->assertTrue($doc->save(), 'presupuesto-cant-save');

            // comprobamos que el pedido se ha creado
            $children = $doc->childrenDocuments();
            $this->assertNotEmpty($children, 'pedidos-no-creadas');
            foreach ($children as $child) {
                // comprobamos que el pedido se ha creado en la empresa 2
                $this->assertEquals($company2->idempresa, $child->idempresa, 'pedido-bad-idempresa');
                $this->assertEquals($warehouse->codalmacen, $child->codalmacen, 'pedido-bad-codalmacen');
            }
        }

        // eliminamos
        $children = $doc->childrenDocuments();
        $this->assertNotEmpty($children, 'pedidos-no-creadas');
        foreach ($children as $child) {
            $this->assertTrue($child->delete(), 'pedido-cant-delete');
        }
        $this->assertTrue($doc->delete(), 'presupuesto-cant-delete');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'contacto-cant-delete');
        $this->assertTrue($subject->delete(), 'proveedor-cant-delete');
        $this->assertTrue($company2->delete(), 'empresa-cant-delete');
    }

    public function testApprove(): void
    {
        // creamos un proveedor
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save());

        // creamos un presupuesto y le asignamos el proveedor
        $doc = new PresupuestoProveedor();
        $this->assertTrue($doc->setSubject($subject));
        $this->assertTrue($doc->save(), 'can-not-create-presupuesto-proveedor-4');

        // añadimos una línea
        $line = $doc->getNewLine();
        $line->cantidad = 2;
        $line->descripcion = 'Presupuesto de proveedor';
        $line->pvpunitario = 50;
        $this->assertTrue($line->save(), 'can-not-save-line-4');

        // actualizamos los totales
        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-update-presupuesto-proveedor-4');

        // aprobamos
        foreach ($doc->getAvailableStatus() as $status) {
            if (empty($status->generadoc)) {
                continue;
            }

            // al cambiar el estado genera un nuevo pedido
            $doc->idestado = $status->idestado;
            $this->assertTrue($doc->save(), 'pedido-cant-save');
        }

        // comprobamos que el pedido se ha creado
        $children = $doc->childrenDocuments();
        $this->assertCount(1, $children);
        $this->assertEquals(100, $children[0]->neto, 'pedido-bad-neto');

        // comprobamos las líneas del pedido
        $childLines = $children[0]->getLines();
        $this->assertCount(1, $childLines, 'pedido-bad-lines-count');
        $this->assertEquals(50, $childLines[0]->pvpunitario, 'pedido-bad-line-pvpunitario');

        // comprobamos que no podemos eliminar el presupuesto
        $this->assertFalse($doc->delete(), 'pedido-can-delete-approved');

        // eliminamos
        $this->assertTrue($children[0]->delete(), 'pedido-cant-delete');

        // recargamos el presupuesto
        $this->assertTrue($doc->reload());

        // comprobamos que el presupuesto sigue existiendo y es editable
        $this->assertTrue($doc->exists());
        $this->assertTrue($doc->editable);

        // eliminamos
        $this->assertTrue($doc->delete());
        $this->assertTrue($subject->getDefaultAddress()->delete());
        $this->assertTrue($subject->delete());
    }

    protected function setUp(): void
    {
        $this->logErrors();
    }
}
