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

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Controller\EditPedidoCliente;
use FacturaScripts\Core\Controller\EditPedidoProveedor;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Dinamic\Model\PedidoProveedor;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * Simula la parte del navegador cuando se corta la conexión mientras se guarda un documento nuevo:
 * el servidor completa el primer save-doc, pero el navegador no recibe la respuesta, reactiva el botón
 * y el usuario vuelve a guardar enviando el mismo formulario con el mismo multireqtoken.
 * El reintento no debe crear un segundo documento.
 *
 * Todos los documentos de venta y de compra comparten SalesController y PurchasesController,
 * por eso basta con probar un documento de cada tipo.
 */
final class SaveDocDuplicateTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var User */
    private $user;

    public function controllerProvider(): array
    {
        return [
            'EditPedidoCliente' => [EditPedidoCliente::class, PedidoCliente::class, 'codcliente'],
            'EditPedidoProveedor' => [EditPedidoProveedor::class, PedidoProveedor::class, 'codproveedor'],
        ];
    }

    public static function setUpBeforeClass(): void
    {
        // el controlador guarda dentro de una transacción, donde no se pueden crear tablas
        self::loadCoreModels();
        self::setDefaultSettings();
    }

    /**
     * @dataProvider controllerProvider
     */
    public function testRetryWithSameTokenDoesNotDuplicate(string $controllerClass, string $modelClass, string $field): void
    {
        $subject = $field === 'codcliente' ? $this->getRandomCustomer() : $this->getRandomSupplier();
        $this->assertTrue($subject->save());

        try {
            // el navegador genera el token al cargar el formulario y lo reutiliza en el reintento
            $token = $this->newToken($controllerClass);
            $data = json_encode(['action' => 'save-doc', $field => $subject->{$field}]);

            // primer guardado: el servidor lo completa, pero la respuesta no llega al navegador
            $this->runController($controllerClass, $token, $data);
            $this->assertCount(1, $modelClass::all([Where::eq($field, $subject->{$field})]), 'document-not-saved');

            // el usuario vuelve a pulsar guardar con el mismo formulario
            $this->runController($controllerClass, $token, $data);
            $this->assertCount(1, $modelClass::all([Where::eq($field, $subject->{$field})]), 'document-duplicated');
        } finally {
            foreach ($modelClass::all([Where::eq($field, $subject->{$field})]) as $doc) {
                $doc->delete();
            }
            $subject->getDefaultAddress()->delete();
            $subject->delete();
        }
    }

    private function newToken(string $controllerClass): string
    {
        $name = substr($controllerClass, strrpos($controllerClass, '\\') + 1);
        $controller = new $controllerClass($name, '/' . $name);
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($this->user->nick);
        $token = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();
        return $token;
    }

    private function runController(string $controllerClass, string $token, string $data): void
    {
        $name = substr($controllerClass, strrpos($controllerClass, '\\') + 1);
        $controller = new $controllerClass($name, '/' . $name);
        $controller->request = new Request(['request' => [
            'action' => 'save-doc',
            'code' => '',
            'data' => $data,
            'multireqtoken' => $token,
            'selectedLine' => '0',
        ]]);

        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, true);

        $response = new Response();
        $response->disableSend(true);
        $controller->privateCore($response, $this->user, $permissions);
        $controller->multiRequestProtection->clearSeed();
    }

    protected function setUp(): void
    {
        MiniLog::clear();

        $this->user = $this->getRandomUser();
        $this->user->admin = true;
        $this->assertTrue($this->user->save());
    }

    protected function tearDown(): void
    {
        $this->user->delete();
        $this->logErrors();
    }
}
