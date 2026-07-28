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
