<?php declare(strict_types=1);

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\ApiCreatePresupuestoCliente;
use FacturaScripts\Core\Model\AlbaranCliente;
use FacturaScripts\Core\Model\PedidoCliente;
use FacturaScripts\Core\Model\PresupuestoCliente;
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

class ApiCreatePresupuestoClienteTest extends TestCase
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
    public function testPuedeCrearPresupuesto()
    {
        $apiCreatePresupuestoCliente = new ApiCreatePresupuestoCliente('');
        $reflection = new ReflectionClass($apiCreatePresupuestoCliente);

        $property = new ReflectionProperty(ApiCreatePresupuestoCliente::class, 'request');
        $property->setAccessible(true);

        /** @var Request $request */
        $request = $property->getValue($apiCreatePresupuestoCliente);
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
        $method->invokeArgs($apiCreatePresupuestoCliente, []);

        $property = new ReflectionProperty(ApiCreatePresupuestoCliente::class, 'response');
        $property->setAccessible(true);

        /** @var Response $response */
        $response = $property->getValue($apiCreatePresupuestoCliente);

        $content = json_decode($response->getContent(), true);
        $presupuesto = $content['doc'];

        $this->assertEquals($cliente->razonsocial, $presupuesto['nombrecliente']);

        $this->assertEquals(4, $presupuesto['neto']);
        $this->assertEquals(0.84, $presupuesto['totaliva']);
        $this->assertEquals(4.84, $presupuesto['total']);

        // eliminamos
        $presupuestoBBDD = new PresupuestoCliente();
        $presupuestoBBDD->loadFromCode($presupuesto['idpresupuesto']);
        $this->assertTrue($presupuestoBBDD->delete());

        $this->assertTrue($cliente->getDefaultAddress()->delete());
        $this->assertTrue($cliente->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}