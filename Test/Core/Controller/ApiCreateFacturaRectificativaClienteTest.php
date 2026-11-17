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

use FacturaScripts\Core\Controller\ApiCreateFacturaRectificativaCliente;
use FacturaScripts\Core\Lib\Calculator;
use FacturaScripts\Core\Lib\RefundReason;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class ApiCreateFacturaRectificativaClienteTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testCannotRefundARefund(): void
    {
        // creamos un cliente
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        // creamos la factura original
        $original = new FacturaCliente();
        $this->assertTrue($original->setSubject($subject), 'can-not-set-subject');
        $this->assertTrue($original->save(), 'can-not-create-invoice');

        // creamos una factura marcada como rectificativa de la original
        $refund = new FacturaCliente();
        $this->assertTrue($refund->setSubject($subject), 'can-not-set-subject-refund');
        $refund->idfacturarect = $original->idfactura;
        $refund->codigorect = $original->codigo;
        $this->assertTrue($refund->save(), 'can-not-create-refund');

        // intentar rectificar la rectificativa por API debe rechazarse con 400
        $result = $this->callApi(['idfactura' => $refund->idfactura, 'fecha' => Tools::date()]);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], 'refund-of-refund-should-fail');

        // no se ha creado ninguna rectificativa de la rectificativa
        $created = FacturaCliente::all([Where::eq('idfacturarect', $refund->idfactura)]);
        $this->assertCount(0, $created, 'refund-of-refund-created');

        // limpiamos (primero la rectificativa, luego la original)
        $this->assertTrue($refund->delete(), 'can-not-delete-refund');
        $this->assertTrue($original->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testRefundOneLine(): void
    {
        // creamos una factura con dos líneas
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-invoice');
        $line2 = $invoice->getNewLine();
        $line2->cantidad = 3;
        $line2->pvpunitario = 10;
        $this->assertTrue($line2->save(), 'can-not-save-line');
        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'can-not-calculate-invoice');

        // rectificamos solo 1 unidad de la segunda línea
        $result = $this->callApi([
            'idfactura' => $invoice->idfactura,
            'fecha' => Tools::date(),
            'refund_' . $line2->idlinea => 1,
        ]);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'refund-one-line-failed');

        // la rectificativa solo debe tener esa línea, con cantidad -1
        $refunds = FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]);
        $this->assertCount(1, $refunds, 'refund-not-created');
        $refundLines = $refunds[0]->getLines();
        $this->assertCount(1, $refundLines, 'bad-refund-lines-count');
        $this->assertEquals($line2->idlinea, $refundLines[0]->idlinearect, 'bad-refund-line');
        $this->assertEquals(-1, $refundLines[0]->cantidad, 'bad-refund-quantity');

        // sin idestado, la rectificativa conserva el estado de la factura original y su efecto en el stock
        $this->assertTrue($invoice->reload(), 'can-not-reload-invoice');
        $this->assertEquals($invoice->idestado, $refunds[0]->idestado, 'refund-should-keep-invoice-status');
        $this->assertEquals($line2->actualizastock, $refundLines[0]->actualizastock, 'bad-refund-line-stock');

        // limpiamos (primero la rectificativa, luego la original)
        $this->deleteRefund($refunds[0]);
        $invoice->reload();
        $subject = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testRefundWithReason(): void
    {
        // creamos una factura con una línea
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-invoice');

        // un motivo que no existe se rechaza sin crear nada
        $result = $this->callApi([
            'idfactura' => $invoice->idfactura,
            'fecha' => Tools::date(),
            'motivorect' => 'NO_EXISTE',
        ]);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], 'invalid-reason-should-fail');
        $this->assertCount(0, FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]), 'refund-created-with-invalid-reason');

        // con un motivo válido, la rectificativa lo guarda
        $result = $this->callApi([
            'idfactura' => $invoice->idfactura,
            'fecha' => Tools::date(),
            'motivorect' => RefundReason::ES_REFUND_REASON_80_4,
        ]);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'refund-with-reason-failed');
        $refunds = FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]);
        $this->assertCount(1, $refunds, 'refund-not-created');
        $this->assertEquals(RefundReason::ES_REFUND_REASON_80_4, $refunds[0]->motivorect, 'bad-refund-reason');

        // limpiamos (primero la rectificativa, luego la original)
        $this->deleteRefund($refunds[0]);
        $invoice->reload();
        $subject = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testRefundWithStatus(): void
    {
        // creamos una factura con una línea
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-invoice');

        // buscamos un estado de factura distinto del que tendrá la rectificativa por defecto
        $status = null;
        foreach ($invoice->getAvailableStatus() as $item) {
            if ($item->activo && false === $item->editable && empty($item->generadoc)) {
                $status = $item;
            }
        }
        $this->assertNotNull($status, 'no-status-found');

        // si se indica idestado, la rectificativa lo usa
        $result = $this->callApi([
            'idfactura' => $invoice->idfactura,
            'fecha' => Tools::date(),
            'idestado' => $status->idestado,
        ]);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'refund-with-status-failed');

        $refunds = FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]);
        $this->assertCount(1, $refunds, 'refund-not-created');
        $this->assertEquals($status->idestado, $refunds[0]->idestado, 'refund-should-use-status');

        // limpiamos (primero la rectificativa, luego la original)
        $this->deleteRefund($refunds[0]);
        $invoice->reload();
        $subject = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testRefundUnknownLineFails(): void
    {
        // creamos una factura con una línea
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-invoice');
        $lines = $invoice->getLines();
        $this->assertCount(1, $lines, 'bad-invoice-lines-count');

        // un refund_X que no es el idlinea de ninguna línea debe rechazarse, no rectificar toda la factura
        $badKey = 'refund_' . ($lines[0]->idlinea + 1000);
        $result = $this->callApi([
            'idfactura' => $invoice->idfactura,
            'fecha' => Tools::date(),
            $badKey => 1,
        ]);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], 'refund-unknown-line-should-fail');
        $this->assertStringContainsString($badKey, $result['body']['message'] ?? '', 'error-should-name-the-key');

        // no se ha creado ninguna rectificativa y la factura sigue editable
        $refunds = FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]);
        $this->assertCount(0, $refunds, 'refund-created');
        $this->assertTrue($invoice->reload(), 'can-not-reload-invoice');
        $this->assertTrue($invoice->editable, 'invoice-should-stay-editable');

        // limpiamos
        $subject = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testRefundWithoutHour(): void
    {
        // creamos una factura con una línea
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-invoice');

        // la hora es opcional: sin ella debe crearse la rectificativa con la hora actual
        $result = $this->callApi(['idfactura' => $invoice->idfactura, 'fecha' => Tools::date()]);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'refund-without-hour-failed');

        $refunds = FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]);
        $this->assertCount(1, $refunds, 'refund-not-created');
        $this->assertNotEmpty($refunds[0]->hora, 'refund-without-hour');

        // limpiamos (primero la rectificativa, luego la original)
        $this->deleteRefund($refunds[0]);
        $invoice->reload();
        $subject = $invoice->getSubject();
        $this->assertTrue($invoice->delete(), 'can-not-delete-invoice');
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    public function testRefundExceedingQuantityFails(): void
    {
        // creamos una factura con una línea de 2 unidades
        $invoice = $this->getInvoiceWithQuantities([2]);
        $line = $invoice->getLines()[0];

        // rectificar 3 unidades supera lo facturado y se rechaza indicando la línea
        $result = $this->callApi([
            'idfactura' => $invoice->idfactura,
            'fecha' => Tools::date(),
            'refund_' . $line->idlinea => 3,
        ]);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], 'refund-exceeded-should-fail');
        $this->assertStringContainsString((string)$line->idlinea, $result['body']['message'] ?? '', 'error-should-name-the-line');

        // el signo de la cantidad enviada no permite saltarse el límite
        $result = $this->callApi([
            'idfactura' => $invoice->idfactura,
            'fecha' => Tools::date(),
            'refund_' . $line->idlinea => -3,
        ]);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], 'negative-refund-exceeded-should-fail');

        // no se ha creado ninguna rectificativa y la factura sigue editable
        $this->assertCount(0, FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]), 'refund-created');
        $this->assertTrue($invoice->reload(), 'can-not-reload-invoice');
        $this->assertTrue($invoice->editable, 'invoice-should-stay-editable');

        // limpiamos
        $this->deleteInvoiceAndRefunds($invoice);
    }

    public function testRefundNegativeLine(): void
    {
        // creamos una factura con una línea de 2 unidades y otra de -1
        $invoice = $this->getInvoiceWithQuantities([2, -1]);
        $positive = $negative = null;
        foreach ($invoice->getLines() as $line) {
            $line->cantidad > 0 ? $positive = $line : $negative = $line;
        }

        // en la línea negativa se compara en valor absoluto: rectificar 2 se rechaza
        $result = $this->callApi([
            'idfactura' => $invoice->idfactura,
            'fecha' => Tools::date(),
            'refund_' . $negative->idlinea => 2,
        ]);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], 'negative-line-exceeded-should-fail');

        // rectificar 1 sí se permite y la línea rectificativa lleva el signo contrario
        $result = $this->callApi([
            'idfactura' => $invoice->idfactura,
            'fecha' => Tools::date(),
            'refund_' . $negative->idlinea => 1,
        ]);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'negative-line-refund-failed');
        $refunds = FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]);
        $this->assertCount(1, $refunds, 'refund-not-created');
        $refundLines = $refunds[0]->getLines();
        $this->assertCount(1, $refundLines, 'bad-refund-lines-count');
        $this->assertEquals(1, $refundLines[0]->cantidad, 'bad-negative-refund-quantity');

        // sin refund_ solo se rectifica lo pendiente: las 2 unidades de la línea positiva
        $result = $this->callApi(['idfactura' => $invoice->idfactura, 'fecha' => Tools::date()]);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'pending-refund-failed');
        $this->assertEquals(-2, $this->getRefundedQuantity($invoice, $positive->idlinea), 'bad-positive-refunded');
        $this->assertEquals(1, $this->getRefundedQuantity($invoice, $negative->idlinea), 'bad-negative-refunded');

        // limpiamos
        $this->deleteInvoiceAndRefunds($invoice);
    }

    public function testRepeatFullRefundFails(): void
    {
        // creamos una factura con una línea de 2 unidades
        $invoice = $this->getInvoiceWithQuantities([2]);
        $line = $invoice->getLines()[0];

        // sin refund_ se rectifica la factura completa
        $result = $this->callApi(['idfactura' => $invoice->idfactura, 'fecha' => Tools::date()]);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'full-refund-failed');

        // repetir la llamada ya no crea otra rectificativa
        $result = $this->callApi(['idfactura' => $invoice->idfactura, 'fecha' => Tools::date()]);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], 'repeated-full-refund-should-fail');
        $this->assertCount(1, FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]), 'bad-refunds-count');
        $this->assertEquals(-2, $this->getRefundedQuantity($invoice, $line->idlinea), 'bad-refunded-quantity');

        // limpiamos
        $this->deleteInvoiceAndRefunds($invoice);
    }

    public function testSuccessivePartialRefunds(): void
    {
        // creamos una factura con una línea de 4 unidades
        $invoice = $this->getInvoiceWithQuantities([4]);
        $line = $invoice->getLines()[0];
        $refundKey = 'refund_' . $line->idlinea;

        // rectificamos 1 y luego 2 unidades
        foreach ([1, 2] as $quantity) {
            $result = $this->callApi(['idfactura' => $invoice->idfactura, 'fecha' => Tools::date(), $refundKey => $quantity]);
            $this->assertEquals(Response::HTTP_OK, $result['code'], 'partial-refund-failed');
        }

        // queda 1 unidad pendiente: rectificar 2 se rechaza
        $result = $this->callApi(['idfactura' => $invoice->idfactura, 'fecha' => Tools::date(), $refundKey => 2]);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], 'refund-over-pending-should-fail');

        // sin refund_ se rectifica solo la unidad pendiente
        $result = $this->callApi(['idfactura' => $invoice->idfactura, 'fecha' => Tools::date()]);
        $this->assertEquals(Response::HTTP_OK, $result['code'], 'pending-refund-failed');
        $this->assertEquals(-1, $result['body']['lines'][0]['cantidad'] ?? null, 'bad-pending-refund-quantity');

        // ya no queda nada pendiente: ni con refund_ ni sin él se crea otra rectificativa
        $result = $this->callApi(['idfactura' => $invoice->idfactura, 'fecha' => Tools::date(), $refundKey => 1]);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], 'refund-without-pending-should-fail');
        $result = $this->callApi(['idfactura' => $invoice->idfactura, 'fecha' => Tools::date()]);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $result['code'], 'nothing-to-refund-should-fail');

        $this->assertCount(3, FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]), 'bad-refunds-count');
        $this->assertEquals(-4, $this->getRefundedQuantity($invoice, $line->idlinea), 'bad-refunded-quantity');

        // limpiamos
        $this->deleteInvoiceAndRefunds($invoice);
    }

    /**
     * Elimina las rectificativas de la factura, la factura y su cliente.
     */
    private function deleteInvoiceAndRefunds(FacturaCliente $invoice): void
    {
        foreach (FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]) as $refund) {
            $this->deleteRefund($refund);
        }

        // al borrar cada rectificativa la original vuelve a su estado anterior, que puede no ser editable
        $invoice->reload();
        $subject = $invoice->getSubject();
        $this->deleteRefund($invoice);
        $this->assertTrue($subject->getDefaultAddress()->delete(), 'can-not-delete-contact');
        $this->assertTrue($subject->delete(), 'can-not-delete-customer');
    }

    /**
     * Crea una factura de cliente con una línea por cada cantidad indicada.
     *
     * @param float[] $quantities
     */
    private function getInvoiceWithQuantities(array $quantities): FacturaCliente
    {
        $invoice = $this->getRandomCustomerInvoice();
        $this->assertTrue($invoice->exists(), 'can-not-create-invoice');

        // la primera línea ya la crea getRandomCustomerInvoice()
        foreach ($quantities as $num => $quantity) {
            $line = $num === 0 ? $invoice->getLines()[0] : $invoice->getNewLine();
            $line->cantidad = $quantity;
            $line->pvpunitario = 10;
            $this->assertTrue($line->save(), 'can-not-save-line');
        }

        $lines = $invoice->getLines();
        $this->assertTrue(Calculator::calculate($invoice, $lines, true), 'can-not-calculate-invoice');
        return $invoice;
    }

    /**
     * Suma la cantidad de todas las líneas rectificativas de una línea de la factura.
     */
    private function getRefundedQuantity(FacturaCliente $invoice, int $idlinea): float
    {
        $quantity = 0.0;
        foreach (FacturaCliente::all([Where::eq('idfacturarect', $invoice->idfactura)]) as $refund) {
            foreach ($refund->getLines() as $line) {
                if ((int)$line->idlinearect === $idlinea) {
                    $quantity += $line->cantidad;
                }
            }
        }

        return $quantity;
    }

    /**
     * Elimina una factura (rectificativa u original), devolviéndola antes a un estado editable si hace falta.
     */
    private function deleteRefund(FacturaCliente $refund): void
    {
        if (false === $refund->editable) {
            foreach ($refund->getAvailableStatus() as $status) {
                if ($status->editable && $status->activo) {
                    $refund->idestado = $status->idestado;
                    $this->assertTrue($refund->save(), 'can-not-unlock-refund');
                    break;
                }
            }
        }

        $this->assertTrue($refund->delete(), 'can-not-delete-refund');
    }

    /**
     * Ejecuta el controlador ApiCreateFacturaRectificativaCliente simulando una
     * petición POST, evitando la validación de token (que pertenece a
     * ApiController) y capturando la respuesta sin enviarla.
     *
     * @param array $body
     * @param string $method
     *
     * @return array{code: int, body: array}
     */
    private function callApi(array $body, string $method = 'POST'): array
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        unset($_SERVER['CONTENT_TYPE']);
        $_POST = $body;
        $_GET = [];

        $url = '/api/3/crearFacturaRectificativaCliente';

        $api = new class ('ApiCreateFacturaRectificativaCliente', $url) extends ApiCreateFacturaRectificativaCliente {
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
