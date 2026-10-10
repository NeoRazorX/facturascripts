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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Lib\InvoiceOperation;
use FacturaScripts\Core\Lib\TaxExceptions;
use FacturaScripts\Core\Migrations;
use FacturaScripts\Core\Model\Cliente;
use FacturaScripts\Core\Model\Proveedor;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Tests de las migraciones que ajustan la excepción de IVA de clientes y proveedores
 * intracomunitarios a la que pone CalculatorModSpain en las líneas.
 */
final class MigrationsVatExceptionTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var DataBase */
    private $db;

    protected function setUp(): void
    {
        $this->db = new DataBase();
        $this->db->connect();
    }

    public function testFixClientesIntraCommunityVatException(): void
    {
        // cliente español que la migración antigua pasó a intracomunitaria
        $spanish = $this->createCustomer(InvoiceOperation::INTRA_COMMUNITY, TaxExceptions::ES_TAX_EXCEPTION_22, 'ESP');

        // cliente de la UE con exención de zona franca y cliente de la UE que compra servicios
        $foreign = $this->createCustomer(InvoiceOperation::INTRA_COMMUNITY, TaxExceptions::ES_TAX_EXCEPTION_23_24, 'BEL');
        $services = $this->createCustomer(InvoiceOperation::INTRA_COMMUNITY, TaxExceptions::ES_TAX_EXCEPTION_68_70, 'BEL');

        // cliente ya correcto, no debe cambiar
        $correct = $this->createCustomer(InvoiceOperation::INTRA_COMMUNITY, TaxExceptions::ES_TAX_EXCEPTION_25, 'BEL');

        $this->runMigration('fixClientesIntraCommunityVatException');

        $this->assertTrue($spanish->reload());
        $this->assertNull($spanish->operacion, 'spanish-customer-operation-not-cleared');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_22, $spanish->excepcioniva, 'spanish-customer-exception-changed');

        $this->assertTrue($foreign->reload());
        $this->assertEquals(InvoiceOperation::INTRA_COMMUNITY, $foreign->operacion, 'foreign-customer-operation-changed');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_25, $foreign->excepcioniva, 'foreign-customer-exception-not-fixed');

        $this->assertTrue($services->reload());
        $this->assertEquals(InvoiceOperation::INTRA_COMMUNITY_SERVICES, $services->operacion, 'services-customer-operation-not-fixed');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_68_70, $services->excepcioniva, 'services-customer-exception-changed');

        $this->assertTrue($correct->reload());
        $this->assertEquals(InvoiceOperation::INTRA_COMMUNITY, $correct->operacion, 'correct-customer-operation-changed');
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_25, $correct->excepcioniva, 'correct-customer-exception-changed');

        // tras la migración, todos se pueden volver a guardar
        foreach ([$spanish, $foreign, $services, $correct] as $customer) {
            $this->assertTrue($customer->save(), 'customer-cant-save-after-migration');
            $this->assertTrue($customer->getDefaultAddress()->delete(), 'contacto-cant-delete');
            $this->assertTrue($customer->delete(), 'cliente-cant-delete');
        }
    }

    public function testFixProveedoresIntraCommunityVatException(): void
    {
        $goods = $this->createSupplier(InvoiceOperation::INTRA_COMMUNITY, TaxExceptions::ES_TAX_EXCEPTION_68_70);
        $services = $this->createSupplier(InvoiceOperation::INTRA_COMMUNITY_SERVICES, TaxExceptions::ES_TAX_EXCEPTION_7);

        // sin operación, ES_68_70 es válida y no debe cambiar
        $noOperation = $this->createSupplier(null, TaxExceptions::ES_TAX_EXCEPTION_68_70);

        $this->runMigration('fixProveedoresIntraCommunityVatException');

        $this->assertTrue($goods->reload());
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_84, $goods->excepcioniva, 'goods-supplier-exception-not-fixed');

        $this->assertTrue($services->reload());
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_84, $services->excepcioniva, 'services-supplier-exception-not-fixed');

        $this->assertTrue($noOperation->reload());
        $this->assertEquals(TaxExceptions::ES_TAX_EXCEPTION_68_70, $noOperation->excepcioniva, 'no-operation-supplier-exception-changed');

        // tras la migración, todos se pueden volver a guardar
        foreach ([$goods, $services, $noOperation] as $supplier) {
            $this->assertTrue($supplier->save(), 'supplier-cant-save-after-migration');
            $this->assertTrue($supplier->getDefaultAddress()->delete(), 'contacto-cant-delete');
            $this->assertTrue($supplier->delete(), 'proveedor-cant-delete');
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    /**
     * Crea un cliente y le asigna por SQL una operación y excepción que la validación ya no admite,
     * como las que pueden quedar en instalaciones existentes.
     */
    private function createCustomer(?string $operation, string $exception, string $codpais): Cliente
    {
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'cliente-cant-save');

        $this->db->exec("UPDATE clientes SET operacion = " . $this->db->var2str($operation)
            . ", excepcioniva = " . $this->db->var2str($exception)
            . " WHERE codcliente = " . $this->db->var2str($customer->codcliente) . ";");
        $this->db->exec("UPDATE contactos SET codpais = " . $this->db->var2str($codpais)
            . " WHERE idcontacto = " . $this->db->var2str($customer->idcontactofact) . ";");

        return $customer;
    }

    /**
     * Crea un proveedor y le asigna por SQL una operación y excepción, saltándose la validación.
     */
    private function createSupplier(?string $operation, string $exception): Proveedor
    {
        $supplier = $this->getRandomSupplier();
        $this->assertTrue($supplier->save(), 'proveedor-cant-save');

        $this->db->exec("UPDATE proveedores SET operacion = " . $this->db->var2str($operation)
            . ", excepcioniva = " . $this->db->var2str($exception)
            . " WHERE codproveedor = " . $this->db->var2str($supplier->codproveedor) . ";");

        return $supplier;
    }

    /**
     * Ejecuta directamente una migración privada del núcleo, sin pasar por el registro de ejecutadas.
     */
    private function runMigration(string $name): void
    {
        $method = new ReflectionMethod(Migrations::class, $name);
        $method->setAccessible(true);
        $method->invoke(null);
    }
}
