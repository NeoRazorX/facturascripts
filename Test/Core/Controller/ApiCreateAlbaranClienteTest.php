<?php declare(strict_types=1);

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\ApiCreateAlbaranCliente;
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

class ApiCreateAlbaranClienteTest extends TestCase
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
    public function testPuedeCrearAlbaran()
    {
        $apiCreateAlbaranCliente = new ApiCreateAlbaranCliente('');
        $reflection = new ReflectionClass($apiCreateAlbaranCliente);

        $property = new ReflectionProperty(ApiCreateAlbaranCliente::class, 'request');
        $property->setAccessible(true);

        /** @var Request $request */
        $request = $property->getValue($apiCreateAlbaranCliente);
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
        $method->invokeArgs($apiCreateAlbaranCliente, []);

        $property = new ReflectionProperty(ApiCreateAlbaranCliente::class, 'response');
        $property->setAccessible(true);

        /** @var Response $response */
        $response = $property->getValue($apiCreateAlbaranCliente);

        $content = json_decode($response->getContent(), true);
        $albaran = $content['doc'];

        $this->assertEquals($cliente->razonsocial, $albaran['nombrecliente']);

        $this->assertEquals(4, $albaran['neto']);
        $this->assertEquals(0.84, $albaran['totaliva']);
        $this->assertEquals(4.84, $albaran['total']);

        // eliminamos
        $albaranBBDD = new AlbaranCliente();
        $albaranBBDD->loadFromCode($albaran['idalbaran']);
        $this->assertTrue($albaranBBDD->delete());

        $this->assertTrue($cliente->getDefaultAddress()->delete());
        $this->assertTrue($cliente->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}