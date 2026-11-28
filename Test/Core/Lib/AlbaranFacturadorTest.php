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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\Calculator;
use FacturaScripts\Dinamic\Model\AlbaranCliente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\LogMessage;
use FacturaScripts\Core\Lib\AlbaranFacturador;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests del Megafacturador: agrupación de albaranes pendientes por cliente y
 * generación de una única factura con todos ellos.
 *
 * @author Esteban Sánchez Martínez <esteban@factura.city>
 */
final class AlbaranFacturadorTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var AlbaranCliente[] Albaranes creados en el test, para borrarlos en tearDown. */
    private array $deliveryNotesToDelete = [];

    /** @var Cliente[] Clientes creados en el test, para borrarlos en tearDown. */
    private array $customersToDelete = [];

    /** @var FacturaCliente[] Facturas creadas en el test, para borrarlas en tearDown. */
    private array $invoicesToDelete = [];

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
        self::warmUpTables();
    }

    /**
     * Crea y borra una factura con su albarán de origen fuera de transacción,
     * para que existan todas las tablas que se usan al facturar (facturas,
     * asientos, recibos, relaciones, logs...). En una base de datos vacía, como la
     * de CI, esas tablas no se pueden crear dentro de la transacción del
     * Megafacturador.
     */
    private static function warmUpTables(): void
    {
        // la tabla de logs: con cientos de albaranes, el log de auditoría se
        // vuelca a la base de datos en mitad de la transacción
        new LogMessage();

        $customer = new Cliente();
        $customer->nombre = 'AlbaranFacturadorTest calentamiento';
        $customer->cifnif = '';
        $deliveryNote = new AlbaranCliente();
        $invoice = null;
        try {
            $customer->save();
            $deliveryNote->setSubject($customer);
            $deliveryNote->save();
            $line = $deliveryNote->getNewLine(['cantidad' => 1, 'pvpunitario' => 1]);
            $line->save();
            $lines = $deliveryNote->getLines();
            Calculator::calculate($deliveryNote, $lines, true);

            // aprobamos el albarán para que el core genere su factura
            foreach ($deliveryNote->getAvailableStatus() as $status) {
                if ($status->generadoc === 'FacturaCliente') {
                    $deliveryNote->idestado = $status->idestado;
                    $deliveryNote->save();
                    break;
                }
            }
            foreach ($deliveryNote->childrenDocuments() as $child) {
                $invoice = $child;
            }
        } finally {
            try {
                $invoice?->delete();
                $deliveryNote->delete();
                foreach ($customer->getAddresses() as $address) {
                    $address->delete();
                }
                $customer->delete();
            } catch (\Throwable $e) {
                error_log('AlbaranFacturadorTest: no se pudo limpiar el calentamiento: ' . $e->getMessage());
            }
        }
    }

    public function testPendingGroupsCountAndTotal(): void
    {
        $customer = $this->createCustomer();
        $albaran1 = $this->createDeliveryNote($customer, 100);
        $albaran2 = $this->createDeliveryNote($customer, 50);
        $albaran3 = $this->createDeliveryNote($customer, 25);

        // un albarán cerrado (estado no editable, no pendiente) no debe contarse
        $closed = $this->createDeliveryNote($customer, 1000);
        $closed->idestado = $this->getClosedStatusId();
        $this->assertTrue($closed->save());
        $this->assertFalse($closed->editable);

        $group = $this->findGroup($customer);
        $this->assertNotNull($group, 'El cliente no aparece entre los pendientes');
        $this->assertSame(3, $group['albaranes']);
        $this->assertEqualsWithDelta(
            $albaran1->total + $albaran2->total + $albaran3->total,
            $group['total'],
            0.01,
            'El total del grupo no es la suma de los albaranes pendientes'
        );
        $this->assertSame($customer->nombre, $group['nombre']);
    }

    public function testInvoiceCreatesSingleInvoice(): void
    {
        $customer = $this->createCustomer();
        $other = $this->createCustomer();

        // albaranes con fecha anterior a la de la factura, para comprobar que
        // la factura toma la fecha elegida y no la del primer albarán
        $albaranDate = date('Y') === date('Y', strtotime('-1 day')) ? Tools::date('-1 day') : Tools::date();
        $albaranes = [
            $this->createDeliveryNote($customer, 100, $albaranDate),
            $this->createDeliveryNote($customer, 50, $albaranDate),
            $this->createDeliveryNote($customer, 25, $albaranDate),
        ];
        $otherAlbaran = $this->createDeliveryNote($other, 10);

        $expectedTotal = 0.0;
        foreach ($albaranes as $albaran) {
            $expectedTotal += $albaran->total;
        }

        $facturador = new AlbaranFacturador();
        $invoice = $facturador->invoice($this->findGroup($customer), date('Y-m-d'));
        $this->assertNotNull($invoice, 'No se ha generado la factura');
        $this->invoicesToDelete[] = $invoice;

        // una única factura, del cliente, con la fecha elegida y el total de los albaranes
        $this->assertTrue($invoice->load($invoice->idfactura));
        $this->assertEquals($customer->codcliente, $invoice->codcliente);
        $this->assertEquals(Tools::date(), $invoice->fecha);
        $this->assertEqualsWithDelta($expectedTotal, $invoice->total, 0.01);
        $this->assertCount(1, FacturaCliente::all([Where::eq('codcliente', $customer->codcliente)]));

        // la factura tiene una línea informativa y una de producto por albarán
        $this->assertCount(6, $invoice->getLines());

        // la factura queda vinculada a los 3 albaranes, que pasan a facturados
        $this->assertCount(3, $invoice->parentDocuments());
        foreach ($albaranes as $albaran) {
            $this->assertTrue($albaran->load($albaran->idalbaran));
            $this->assertFalse($albaran->editable, 'El albarán ' . $albaran->codigo . ' sigue pendiente');
            foreach ($albaran->getLines() as $line) {
                $this->assertEquals($line->cantidad, $line->servido);
            }
        }

        // el cliente desaparece de los pendientes y el otro cliente no se toca
        $this->assertNull($this->findGroup($customer), 'El cliente facturado sigue entre los pendientes');
        $otherGroup = $this->findGroup($other);
        $this->assertNotNull($otherGroup, 'El otro cliente ya no aparece entre los pendientes');
        $this->assertSame(1, $otherGroup['albaranes']);
        $this->assertTrue($otherAlbaran->load($otherAlbaran->idalbaran));
        $this->assertTrue($otherAlbaran->editable);
    }

    public function testInvoiceManyDeliveryNotes(): void
    {
        // cientos de albaranes de un mismo cliente
        $customer = $this->createCustomer();
        $total = 0.0;
        for ($num = 0; $num < 200; $num++) {
            $total += $this->createDeliveryNote($customer, 10)->total;
        }

        // el listado se calcula con una consulta agregada, sin cargar los albaranes
        $start = microtime(true);
        $group = $this->findGroup($customer);
        $this->assertLessThan(2, microtime(true) - $start, 'El listado de pendientes es demasiado lento');
        $this->assertSame(200, $group['albaranes']);
        $this->assertEqualsWithDelta($total, $group['total'], 0.01);

        $facturador = new AlbaranFacturador();
        $invoice = $facturador->invoice($group, date('Y-m-d'));
        $this->assertNotNull($invoice, 'No se ha generado la factura');
        $this->invoicesToDelete[] = $invoice;

        $this->assertEqualsWithDelta($total, $invoice->total, 0.01);
        $this->assertCount(200, $invoice->parentDocuments());
        $this->assertNull($this->findGroup($customer), 'El cliente facturado sigue entre los pendientes');
    }

    public function testInvoiceWithoutPendingOrInvalidDate(): void
    {
        $customer = $this->createCustomer();
        $facturador = new AlbaranFacturador();

        // sin albaranes pendientes
        $group = [
            'codcliente' => $customer->codcliente,
            'idempresa' => Tools::settings('default', 'idempresa'),
            'codserie' => Tools::settings('default', 'codserie'),
            'coddivisa' => Tools::settings('default', 'coddivisa'),
            'codalmacen' => Tools::settings('default', 'codalmacen'),
            'dtopor1' => 0,
            'dtopor2' => 0,
        ];
        $this->assertNull($facturador->invoice($group, date('Y-m-d')));

        // con un albarán pendiente, pero sin fecha o con una fecha no válida
        $albaran = $this->createDeliveryNote($customer, 100);
        $group = $this->findGroup($customer);
        $this->assertNull($facturador->invoice($group, ''));
        $this->assertNull($facturador->invoice($group, 'no-es-una-fecha'));

        // el albarán sigue pendiente y no se ha creado ninguna factura
        $this->assertTrue($albaran->load($albaran->idalbaran));
        $this->assertTrue($albaran->editable);
        $this->assertEmpty(FacturaCliente::all([Where::eq('codcliente', $customer->codcliente)]));
    }

    public function testGroupsSplitIncompatibleDeliveryNotes(): void
    {
        // dos albaranes del mismo cliente con distinto descuento global no se
        // pueden facturar juntos: aparecen en grupos separados
        $customer = $this->createCustomer();
        $this->createDeliveryNote($customer, 100);
        $discounted = $this->createDeliveryNote($customer, 100);
        $discounted->dtopor1 = 10;
        $lines = $discounted->getLines();
        $this->assertTrue(Calculator::calculate($discounted, $lines, true));

        $groups = array_values(array_filter(
            (new AlbaranFacturador())->getPendingGroups(),
            fn($group) => $group['codcliente'] === $customer->codcliente
        ));
        $this->assertCount(2, $groups);
        foreach ($groups as $group) {
            $this->assertSame(1, $group['albaranes']);
        }
    }

    public function testDocumentGenerationRestored(): void
    {
        // tras el Megafacturador, aprobar un albarán suelto debe seguir generando su factura
        $customer = $this->createCustomer();
        $this->createDeliveryNote($customer, 100);
        $invoice = (new AlbaranFacturador())->invoice($this->findGroup($customer), date('Y-m-d'));
        $this->assertNotNull($invoice);
        $this->invoicesToDelete[] = $invoice;

        $albaran = $this->createDeliveryNote($customer, 30);
        foreach ($albaran->getAvailableStatus() as $status) {
            if ($status->generadoc === 'FacturaCliente') {
                $albaran->idestado = $status->idestado;
                break;
            }
        }
        $this->assertTrue($albaran->save());

        $children = $albaran->childrenDocuments();
        foreach ($children as $child) {
            $this->invoicesToDelete[] = $child;
        }
        $this->assertCount(1, $children, 'Aprobar un albarán ya no genera su factura');
    }

    public function testOwnerRestriction(): void
    {
        $customer = $this->createCustomer();
        $albaran = $this->createDeliveryNote($customer, 100);

        // un usuario que solo ve sus datos no ve ni factura albaranes ajenos
        $owner = ['nick' => 'megafacturador-nobody', 'codagente' => ''];
        $this->assertNull($this->findGroup($customer, $owner));

        $group = $this->findGroup($customer);
        $this->assertNull((new AlbaranFacturador())->invoice($group, date('Y-m-d'), $owner));
        $this->assertTrue($albaran->load($albaran->idalbaran));
        $this->assertTrue($albaran->editable);
    }

    /**
     * Crea y guarda un cliente, y lo registra para borrarlo en tearDown.
     *
     * @return Cliente
     */
    private function createCustomer(): Cliente
    {
        $customer = $this->getRandomCustomer('AlbaranFacturadorTest');
        $this->assertTrue($customer->save());
        $this->customersToDelete[] = $customer;
        return $customer;
    }

    /**
     * Crea y guarda un albarán con una línea para el cliente indicado, y lo
     * registra para borrarlo en tearDown.
     *
     * @param Cliente $customer
     * @param float $price Precio unitario de la única línea.
     * @param string|null $fecha Fecha del albarán, o null para hoy.
     *
     * @return AlbaranCliente
     */
    private function createDeliveryNote(Cliente $customer, float $price, ?string $fecha = null): AlbaranCliente
    {
        $doc = new AlbaranCliente();
        $doc->setSubject($customer);
        if ($fecha) {
            $doc->setDate($fecha, $doc->hora);
        }
        $this->assertTrue($doc->save(), 'Error al crear el albarán');
        $this->deliveryNotesToDelete[] = $doc;

        $line = $doc->getNewLine();
        $line->cantidad = 1;
        $line->pvpunitario = $price;
        $this->assertTrue($line->save());

        $lines = $doc->getLines();
        $this->assertTrue(Calculator::calculate($doc, $lines, true));

        return $doc;
    }

    /**
     * Devuelve el id de un estado de albarán de cliente no editable que no
     * genera documentos (por ejemplo, Devuelto).
     *
     * @return int
     */
    private function getClosedStatusId(): int
    {
        foreach ((new AlbaranCliente())->getAvailableStatus() as $status) {
            if (false === (bool)$status->editable && empty($status->generadoc)) {
                return $status->idestado;
            }
        }

        $this->fail('No hay ningún estado de albarán cerrado sin generar documentos');
    }

    /**
     * Devuelve el grupo de pendientes del cliente indicado, o null si no tiene.
     *
     * @param Cliente $customer
     * @param array $owner Restricción de propietario.
     *
     * @return array|null
     */
    private function findGroup(Cliente $customer, array $owner = []): ?array
    {
        foreach ((new AlbaranFacturador())->getPendingGroups($owner) as $group) {
            if ($group['codcliente'] === $customer->codcliente) {
                return $group;
            }
        }

        return null;
    }

    protected function tearDown(): void
    {
        // primero las facturas: al borrarlas, los albaranes vuelven a su estado anterior
        foreach ($this->invoicesToDelete as $invoice) {
            try {
                if ($invoice->load($invoice->idfactura)) {
                    $invoice->delete();
                }
            } catch (\Throwable $e) {
                error_log('AlbaranFacturadorTest: no se pudo borrar la factura: ' . $e->getMessage());
            }
        }
        $this->invoicesToDelete = [];

        foreach ($this->deliveryNotesToDelete as $albaran) {
            try {
                if ($albaran->load($albaran->idalbaran)) {
                    $albaran->delete();
                }
            } catch (\Throwable $e) {
                error_log('AlbaranFacturadorTest: no se pudo borrar el albarán: ' . $e->getMessage());
            }
        }
        $this->deliveryNotesToDelete = [];

        foreach ($this->customersToDelete as $customer) {
            try {
                foreach ($customer->getAddresses() as $address) {
                    $address->delete();
                }
                $customer->delete();
            } catch (\Throwable $e) {
                error_log('AlbaranFacturadorTest: no se pudo borrar el cliente: ' . $e->getMessage());
            }
        }
        $this->customersToDelete = [];

        $this->logErrors();
    }
}
