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

use FacturaScripts\Core\Controller\ApiCreateDocument;
use FacturaScripts\Core\DataSrc\Retenciones;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Response;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ApiCreateDocumentTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        // los controladores crean el documento dentro de una transacción, y dentro
        // no se pueden crear tablas. Instanciamos todos los modelos antes para que
        // el esquema exista al empezar, incluso en una base de datos recién creada.
        self::loadCoreModels();

        self::setDefaultSettings();
        self::installAccountingPlan();
        self::removeTaxRegularization();
    }

    public function testLineIrpfIsApplied(): void
    {
        // creamos un cliente sin retención, para que la cabecera no aporte IRPF
        $subject = $this->getRandomCustomer();
        $subject->codretencion = null;
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos un albarán con IRPF por línea
        $payload = [
            'codcliente' => $subject->codcliente,
            'lineas' => json_encode([
                ['descripcion' => 'línea con IRPF', 'cantidad' => 1, 'pvpunitario' => 100, 'irpf' => 15],
            ]),
        ];
        $result = $this->callCreate('crearAlbaranCliente', $payload);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'create-irpf-bad-code');

        // cargamos el documento creado
        $doc = new AlbaranCliente();
        $this->assertTrue($doc->load($result['body']['doc']['idalbaran'] ?? 0), 'can-not-load-albaran');

        // la línea conserva el IRPF enviado
        $lines = $doc->getLines();
        $this->assertCount(1, $lines, 'bad-line-count');
        $this->assertEquals(15, $lines[0]->irpf, 'line-irpf-not-applied');

        // y los totales lo reflejan
        $this->assertEquals(100, $doc->neto, 'bad-neto');
        $this->assertEquals(15, $doc->irpf, 'bad-doc-irpf');
        $this->assertEquals(15, $doc->totalirpf, 'bad-totalirpf');

        // limpiamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testLineIrpfOverridesSubjectRetention(): void
    {
        // necesitamos dos retenciones distintas para distinguir cabecera y línea
        $percentages = [];
        foreach (Retenciones::all() as $retention) {
            if (false === in_array($retention->porcentaje, $percentages, true)) {
                $percentages[] = $retention->porcentaje;
            }
            if (count($percentages) > 1) {
                break;
            }
        }
        if (count($percentages) < 2) {
            $this->markTestSkipped('not-enough-retentions');
        }

        // creamos un cliente con la primera retención
        $subject = $this->getRandomCustomer();
        foreach (Retenciones::all() as $retention) {
            if ($retention->porcentaje === $percentages[0]) {
                $subject->codretencion = $retention->codretencion;
                break;
            }
        }
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos el albarán enviando la segunda retención en la línea
        $payload = [
            'codcliente' => $subject->codcliente,
            'lineas' => json_encode([
                [
                    'descripcion' => 'línea con IRPF propio',
                    'cantidad' => 1,
                    'pvpunitario' => 100,
                    'irpf' => $percentages[1],
                ],
            ]),
        ];
        $result = $this->callCreate('crearAlbaranCliente', $payload);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'create-irpf-override-bad-code');

        $doc = new AlbaranCliente();
        $this->assertTrue($doc->load($result['body']['doc']['idalbaran'] ?? 0), 'can-not-load-albaran');

        // gana el IRPF de la línea, no el del cliente
        $lines = $doc->getLines();
        $this->assertCount(1, $lines, 'bad-line-count');
        $this->assertEquals($percentages[1], $lines[0]->irpf, 'subject-retention-not-overridden');

        // limpiamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testLineWithoutIrpfInheritsFromSubject(): void
    {
        // buscamos una retención cualquiera
        $retention = null;
        foreach (Retenciones::all() as $item) {
            if ($item->porcentaje > 0) {
                $retention = $item;
                break;
            }
        }
        if (null === $retention) {
            $this->markTestSkipped('no-retentions-available');
        }

        // creamos un cliente con retención
        $subject = $this->getRandomCustomer();
        $subject->codretencion = $retention->codretencion;
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos el albarán sin enviar irpf en la línea
        $payload = [
            'codcliente' => $subject->codcliente,
            'lineas' => json_encode([
                ['descripcion' => 'línea sin IRPF', 'cantidad' => 1, 'pvpunitario' => 100],
            ]),
        ];
        $result = $this->callCreate('crearAlbaranCliente', $payload);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'create-no-irpf-bad-code');

        $doc = new AlbaranCliente();
        $this->assertTrue($doc->load($result['body']['doc']['idalbaran'] ?? 0), 'can-not-load-albaran');

        // se mantiene el comportamiento anterior: hereda la retención del cliente
        $lines = $doc->getLines();
        $this->assertCount(1, $lines, 'bad-line-count');
        $this->assertEquals($retention->porcentaje, $lines[0]->irpf, 'subject-retention-not-inherited');

        // limpiamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testInvoiceLineIrpfIsApplied(): void
    {
        // el asiento de retención necesita una Retencion con ese porcentaje,
        // así que usamos una de las que trae el país configurado
        $retention = null;
        foreach (Retenciones::all() as $item) {
            if ($item->porcentaje > 0) {
                $retention = $item;
                break;
            }
        }
        if (null === $retention) {
            $this->markTestSkipped('no-retentions-available');
        }

        // creamos un cliente sin retención asignada
        $subject = $this->getRandomCustomer();
        $subject->codretencion = null;
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos la factura con IRPF por línea
        $payload = [
            'codcliente' => $subject->codcliente,
            'lineas' => json_encode([
                [
                    'descripcion' => 'línea con IRPF',
                    'cantidad' => 1,
                    'pvpunitario' => 100,
                    'irpf' => $retention->porcentaje,
                ],
            ]),
        ];
        $result = $this->callCreate('crearFacturaCliente', $payload);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'create-invoice-irpf-bad-code');

        $invoice = new FacturaCliente();
        $this->assertTrue($invoice->load($result['body']['doc']['idfactura'] ?? 0), 'can-not-load-invoice');

        // la línea conserva el IRPF enviado
        $lines = $invoice->getLines();
        $this->assertCount(1, $lines, 'bad-line-count');
        $this->assertEquals($retention->porcentaje, $lines[0]->irpf, 'invoice-line-irpf-not-applied');

        // y la factura descuenta la retención del total
        $this->assertEquals(100, $invoice->neto, 'bad-neto');
        $this->assertEquals($retention->porcentaje, $invoice->irpf, 'bad-invoice-irpf');
        $this->assertEquals($retention->porcentaje, $invoice->totalirpf, 'bad-totalirpf');
        $this->assertEquals(
            round(100 + $invoice->totaliva - $invoice->totalirpf, 2),
            round($invoice->total, 2),
            'bad-total'
        );

        // limpiamos
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testLineOrderAndCostAreApplied(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos el albarán indicando orden y coste
        $payload = [
            'codcliente' => $subject->codcliente,
            'lineas' => json_encode([
                ['descripcion' => 'primera', 'cantidad' => 1, 'pvpunitario' => 100, 'orden' => 10, 'coste' => 40],
            ]),
        ];
        $result = $this->callCreate('crearAlbaranCliente', $payload);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'create-order-cost-bad-code');

        $doc = new AlbaranCliente();
        $this->assertTrue($doc->load($result['body']['doc']['idalbaran'] ?? 0), 'can-not-load-albaran');

        $lines = $doc->getLines();
        $this->assertCount(1, $lines, 'bad-line-count');
        $this->assertEquals(10, $lines[0]->orden, 'line-orden-not-applied');
        $this->assertEquals(40, $lines[0]->coste, 'line-coste-not-applied');

        // limpiamos
        $this->assertTrue($doc->delete(), 'can-not-delete-albaran');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    /**
     * Ejecuta el controlador ApiCreateDocument simulando una petición, evitando
     * la validación de token (que pertenece a ApiController) y capturando la
     * respuesta sin enviarla.
     *
     * @param string $resource
     * @param array $body
     * @param string $method
     *
     * @return array{code: int, body: array}
     */
    private function callCreate(string $resource, array $body, string $method = 'POST'): array
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        unset($_SERVER['CONTENT_TYPE']);
        $_POST = $body;
        $_GET = [];

        $url = '/api/3/' . $resource;

        $api = new class ('ApiCreateDocument', $url) extends ApiCreateDocument {
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
