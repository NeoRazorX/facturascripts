<?php declare(strict_types=1);

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\ApiCreatePedidoCliente;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\PedidoCliente;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class ApiCreatePedidoClienteTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    protected function setUp(): void
    {
        new User();
        new AlbaranCliente();
        new PedidoCliente();
        $this->setDefaultSettings();
    }

    /**
     * @throws ReflectionException
     */
    public function testPuedeCrearPedido()
    {
        $apiCreatePedidoCliente = new ApiCreatePedidoCliente('');
        $reflection = new ReflectionClass($apiCreatePedidoCliente);

        $property = new ReflectionProperty(ApiCreatePedidoCliente::class, 'request');
        $property->setAccessible(true);

        /** @var Request $request */
        $request = $property->getValue($apiCreatePedidoCliente);
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $cliente = $this->getRandomCustomer();
        $this->assertTrue($cliente->save());

        $request->request->set('codcliente', $cliente->codcliente);
        $request->request->set('lineas', json_encode([
            [
                'cantidad' => 2,
                'descripcion' => 'test-descripcion-linea',
                'pvpunitario' => 2
            ]
        ]));

        $method = $reflection->getMethod('runResource');
        $method->setAccessible(true);
        $method->invokeArgs($apiCreatePedidoCliente, []);

        $property = new ReflectionProperty(ApiCreatePedidoCliente::class, 'response');
        $property->setAccessible(true);

        /** @var Response $response */
        $response = $property->getValue($apiCreatePedidoCliente);

        $content = json_decode($response->getContent(), true);
        $pedido = $content['doc'];

        $this->assertEquals($cliente->razonsocial, $pedido['nombrecliente']);

        $this->assertEquals(4, $pedido['neto']);
        $this->assertEquals(0.84, $pedido['totaliva']);
        $this->assertEquals(4.84, $pedido['total']);

        // eliminamos
        $pedidoBBDD = new PedidoCliente();
        $pedidoBBDD->loadFromCode($pedido['idpedido']);
        $this->assertTrue($pedidoBBDD->delete());

        $this->assertTrue($cliente->getDefaultAddress()->delete());
        $this->assertTrue($cliente->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}