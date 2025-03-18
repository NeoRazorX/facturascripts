<?php declare(strict_types=1);

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\ApiCreateFacturaCliente;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\PedidoCliente;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Model\FacturaCliente;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class ApiCreateFacturaClienteTest extends TestCase
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
    public function testPuedeCrearFactura()
    {
        $apiCreateFacturaCliente = new ApiCreateFacturaCliente('');
        $reflection = new ReflectionClass($apiCreateFacturaCliente);

        $property = new ReflectionProperty(ApiCreateFacturaCliente::class, 'request');
        $property->setAccessible(true);

        /** @var Request $request */
        $request = $property->getValue($apiCreateFacturaCliente);
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
        $method->invokeArgs($apiCreateFacturaCliente, []);

        $property = new ReflectionProperty(ApiCreateFacturaCliente::class, 'response');
        $property->setAccessible(true);

        /** @var Response $response */
        $response = $property->getValue($apiCreateFacturaCliente);

        $content = json_decode($response->getContent(), true);
        $factura = $content['doc'];

        $this->assertEquals($cliente->razonsocial, $factura['nombrecliente']);

        $this->assertEquals(4, $factura['neto']);
        $this->assertEquals(0.84, $factura['totaliva']);
        $this->assertEquals(4.84, $factura['total']);

        // eliminamos
        $facturaBBDD = new FacturaCliente();
        $facturaBBDD->loadFromCode($factura['idfactura']);
        $this->assertTrue($facturaBBDD->delete());

        $this->assertTrue($cliente->getDefaultAddress()->delete());
        $this->assertTrue($cliente->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}