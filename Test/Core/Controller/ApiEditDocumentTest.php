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

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\ApiEditDocument;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\EstadoDocumento;
use FacturaScripts\Core\Model\Stock;
use FacturaScripts\Core\Response;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ApiEditDocumentTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testCannotChangeCompany(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos una empresa alternativa
        $company = $this->getRandomCompany();
        $this->assertTrue($company->save(), 'can-not-save-company');

        // creamos un albarán
        $doc = new AlbaranCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject');
        $this->assertTrue($doc->save(), 'can-not-create-albaran');

        // guardamos la empresa original del documento
        $idempresa = $doc->idempresa;

        // intentamos cambiar la empresa
        $payload = [
            'idempresa' => $company->idempresa,
            'lineas' => json_encode([]),
        ];
        $result = $this->callEdit('editarAlbaranCliente', $doc->idalbaran, $payload);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $result['code'], 'edit-change-company-code');

        // la empresa no ha cambiado
        $doc->reload();
        $this->assertEquals($idempresa, $doc->idempresa, 'company-changed');

        // limpiamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran');
        $this->assertTrue($company->delete(), 'can-not-delete-company');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testCannotChangeSubject(): void
    {
        // creamos dos clientes
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer-1');
        $other = $this->getRandomCustomer();
        $this->assertTrue($other->save(), 'can-not-save-customer-2');

        // creamos un albarán para el primer cliente
        $doc = new AlbaranCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject');
        $this->assertTrue($doc->save(), 'can-not-create-albaran');

        // intentamos cambiar el cliente
        $payload = [
            'codcliente' => $other->codcliente,
            'lineas' => json_encode([]),
        ];
        $result = $this->callEdit('editarAlbaranCliente', $doc->idalbaran, $payload);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $result['code'], 'edit-change-subject-code');

        // el cliente no ha cambiado
        $doc->reload();
        $this->assertEquals($subject->codcliente, $doc->codcliente, 'subject-changed');

        // limpiamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact-1');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer-1');
        $this->assertTrue($other->getDefaultAddress()->delete(), 'can-not-delete-contact-2');
        $this->assertTrue($other->delete(), 'can-not-delete-customer-2');
    }

    public function testCannotChangeWarehouse(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos un almacén alternativo
        $warehouse = $this->getRandomWarehouse();
        $this->assertTrue($warehouse->save(), 'can-not-save-warehouse');

        // creamos un albarán
        $doc = new AlbaranCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject');
        $this->assertTrue($doc->save(), 'can-not-create-albaran');

        // intentamos cambiar el almacén
        $payload = [
            'codalmacen' => $warehouse->codalmacen,
            'lineas' => json_encode([]),
        ];
        $result = $this->callEdit('editarAlbaranCliente', $doc->idalbaran, $payload);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $result['code'], 'edit-change-warehouse-code');

        // el almacén no ha cambiado
        $doc->reload();
        $this->assertNotEquals($warehouse->codalmacen, $doc->codalmacen, 'warehouse-changed');

        // limpiamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran');
        $this->assertTrue($warehouse->delete(), 'can-not-delete-warehouse');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testDocumentNotFound(): void
    {
        $result = $this->callEdit('editarFacturaCliente', 99999999, ['lineas' => '[]']);
        $this->assertEquals(Response::HTTP_NOT_FOUND, $result['code'], 'edit-not-found-code');
    }

    public function testFullSyncAddModifyDeleteLines(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos un producto sin venta sin stock
        $product = $this->getRandomProduct();
        $product->ventasinstock = false;
        $this->assertTrue($product->save(), 'can-not-save-product');
        foreach ($product->getVariants() as $variant) {
            $variant->precio = 10;
            $variant->coste = 5;
            $this->assertTrue($variant->save(), 'can-not-save-variant');
        }

        // creamos el albarán
        $doc = new AlbaranCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject');
        $this->assertTrue($doc->save(), 'can-not-create-albaran');

        // creamos stock para el almacén del documento
        $stock = new Stock();
        $stock->cantidad = 100;
        $stock->codalmacen = $doc->codalmacen;
        $stock->idproducto = $product->idproducto;
        $stock->referencia = $product->referencia;
        $this->assertTrue($stock->save(), 'can-not-save-stock');

        // añadimos una línea con cantidad 2
        $line = $doc->getNewProductLine($product->referencia);
        $line->cantidad = 2;
        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-calculate');

        // el stock baja a 98
        $stock->reload();
        $this->assertEquals(98, $stock->cantidad, 'bad-stock-after-create');

        // EDICIÓN 1: modificamos la línea a cantidad 3 y añadimos otra línea con cantidad 1
        $idlinea = $lines[0]->idlinea;
        $payload = [
            'lineas' => json_encode([
                ['idlinea' => $idlinea, 'referencia' => $product->referencia, 'cantidad' => 3],
                ['referencia' => $product->referencia, 'cantidad' => 1],
            ]),
        ];
        $result = $this->callEdit('editarAlbaranCliente', $doc->idalbaran, $payload);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'edit-1-bad-code');
        $this->assertCount(2, $result['body']['lines'] ?? [], 'edit-1-bad-line-count');

        // el stock baja a 96 (3 + 1 unidades)
        $stock->reload();
        $this->assertEquals(96, $stock->cantidad, 'bad-stock-after-edit-1');

        // comprobamos los totales del documento
        $doc->reload();
        $this->assertEquals(40, $doc->neto, 'edit-1-bad-neto');

        // EDICIÓN 2: enviamos solo la línea modificada, la otra debe borrarse (full sync)
        $payload = [
            'lineas' => json_encode([
                ['idlinea' => $idlinea, 'referencia' => $product->referencia, 'cantidad' => 3],
            ]),
        ];
        $result = $this->callEdit('editarAlbaranCliente', $doc->idalbaran, $payload);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'edit-2-bad-code');
        $this->assertCount(1, $result['body']['lines'] ?? [], 'edit-2-bad-line-count');

        // el stock vuelve a 97 (se revierte la unidad de la línea borrada)
        $stock->reload();
        $this->assertEquals(97, $stock->cantidad, 'bad-stock-after-edit-2');

        // limpiamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
        $this->assertTrue($product->delete(), 'can-not-delete-product');
    }

    public function testLineIrpfIsApplied(): void
    {
        // creamos un cliente sin retención, para que la cabecera no aporte IRPF
        $subject = $this->getRandomCustomer();
        $subject->codretencion = null;
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos un albarán con una línea libre sin IRPF
        $doc = new AlbaranCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject');
        $this->assertTrue($doc->save(), 'can-not-create-albaran');

        $line = $doc->getNewLine();
        $line->descripcion = 'línea libre';
        $line->cantidad = 1;
        $line->pvpunitario = 100;
        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-calculate');

        $doc->reload();
        $this->assertEquals(0, $doc->totalirpf, 'bad-initial-totalirpf');

        // EDICIÓN 1: añadimos IRPF a la línea existente y creamos otra con IRPF distinto
        $idlinea = $lines[0]->idlinea;
        $payload = [
            'lineas' => json_encode([
                ['idlinea' => $idlinea, 'descripcion' => 'línea libre', 'cantidad' => 1, 'pvpunitario' => 100, 'irpf' => 15],
                ['descripcion' => 'línea nueva', 'cantidad' => 1, 'pvpunitario' => 100, 'irpf' => 7],
            ]),
        ];
        $result = $this->callEdit('editarAlbaranCliente', $doc->idalbaran, $payload);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'edit-irpf-bad-code');

        // ambas líneas guardan su propio porcentaje
        $updated = [];
        foreach ($doc->getLines() as $docLine) {
            $updated[$docLine->descripcion] = $docLine->irpf;
        }
        $this->assertEquals(15, $updated['línea libre'] ?? null, 'existing-line-irpf-not-applied');
        $this->assertEquals(7, $updated['línea nueva'] ?? null, 'new-line-irpf-not-applied');

        // el documento acumula el IRPF de ambas: 100 * 15% + 100 * 7%
        $doc->reload();
        $this->assertEquals(15, $doc->irpf, 'bad-doc-irpf');
        $this->assertEquals(22, $doc->totalirpf, 'bad-totalirpf');

        // EDICIÓN 2: si no enviamos irpf, la línea existente conserva el suyo
        $payload = [
            'lineas' => json_encode([
                ['idlinea' => $idlinea, 'descripcion' => 'línea libre', 'cantidad' => 2, 'pvpunitario' => 100],
            ]),
        ];
        $result = $this->callEdit('editarAlbaranCliente', $doc->idalbaran, $payload);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'edit-keep-irpf-bad-code');

        $remaining = $doc->getLines();
        $this->assertCount(1, $remaining, 'bad-line-count');
        $this->assertEquals(15, $remaining[0]->irpf, 'line-irpf-not-preserved');

        // 200 * 15%
        $doc->reload();
        $this->assertEquals(30, $doc->totalirpf, 'bad-totalirpf-after-keep');

        // limpiamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testMethodNotAllowed(): void
    {
        $result = $this->callEdit('editarFacturaCliente', 1, ['lineas' => '[]'], 'POST');
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $result['code'], 'edit-bad-method-code');
    }

    public function testInvalidLineStructuresAreRejected(): void
    {
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        $doc = new AlbaranCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject');
        $this->assertTrue($doc->save(), 'can-not-create-albaran');

        $result = $this->callEdit('editarAlbaranCliente', $doc->idalbaran, [
            'lineas' => json_encode(['descripcion' => 'Servicios de consultoría']),
        ]);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], 'invalid-lines-bad-code');
        $this->assertEquals('Invalid lines', $result['body']['message'] ?? '', 'invalid-lines-bad-message');

        $this->assertTrue($doc->delete(), 'can-not-delete-albaran');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testNonEditableDocument(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos un estado no editable (sin generar documento ni mover stock)
        $estado = new EstadoDocumento();
        $estado->tipodoc = 'AlbaranCliente';
        $estado->nombre = 'Test no editable ' . mt_rand(1, 99999);
        $estado->editable = false;
        $estado->actualizastock = 0;
        $this->assertTrue($estado->save(), 'can-not-save-estado');

        // creamos un albarán con una línea libre
        $doc = new AlbaranCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject');
        $this->assertTrue($doc->save(), 'can-not-create-albaran');

        $line = $doc->getNewLine();
        $line->descripcion = 'línea libre';
        $line->pvpunitario = 10;
        $line->cantidad = 1;
        $lines = [$line];
        $this->assertTrue(Calculator::calculate($doc, $lines, true), 'can-not-calculate');

        // marcamos el documento como no editable
        $doc->idestado = $estado->idestado;
        $this->assertTrue($doc->save(), 'can-not-change-estado');
        $doc->reload();
        $this->assertFalse($doc->editable, 'document-should-not-be-editable');

        // intentar editar las líneas debe rechazarse con 422
        $payload = [
            'lineas' => json_encode([
                ['cantidad' => 5, 'descripcion' => 'otra'],
            ]),
        ];
        $result = $this->callEdit('editarAlbaranCliente', $doc->idalbaran, $payload);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $result['code'], 'non-editable-lines-code');

        // editar solo un campo desbloqueado (numdocs) debe funcionar
        $payload = ['numdocs' => 3];
        $result = $this->callEdit('editarAlbaranCliente', $doc->idalbaran, $payload);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'non-editable-unlocked-code');

        $doc->reload();
        $this->assertEquals(3, $doc->numdocs, 'numdocs-not-updated');

        // limpiamos: devolvemos el documento a un estado editable para poder borrarlo
        foreach ($doc->getAvailableStatus() as $status) {
            if ($status->editable && $status->predeterminado) {
                $doc->idestado = $status->idestado;
                $doc->save();
                break;
            }
        }
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran');
        $this->assertTrue($estado->delete(), 'can-not-delete-estado');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    /**
     * Ejecuta el controlador ApiEditDocument simulando una petición, evitando
     * la validación de token (que pertenece a ApiController) y capturando la
     * respuesta sin enviarla.
     *
     * @param string $resource
     * @param int|string $id
     * @param array $body
     * @param string $method
     *
     * @return array{code: int, body: array}
     */
    private function callEdit(string $resource, $id, array $body, string $method = 'PUT'): array
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        unset($_SERVER['CONTENT_TYPE']);
        $_POST = $body;
        $_GET = [];

        $url = '/api/3/' . $resource . '/' . $id;

        $api = new class ('ApiEditDocument', $url) extends ApiEditDocument {
            public function exec(): array
            {
                $this->response->disableSend(true);
                $this->runResource();
                $decoded = json_decode($this->response->getContent(), true);

                return [
                    'code' => $this->response->getHttpCode(),
                    'body' => is_array($decoded) ? $decoded : [],
                ];
            }
        };

        $result = $api->exec();

        // limpiamos los globales
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        return $result;
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
