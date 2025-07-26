<?php declare(strict_types=1);

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\ApiCreateFacturaCliente;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Model\ApiKey;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

class ApiCreateFacturaClienteTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    /** @var ApiKey */
    private $apiKey;

    public function testMetodoNoPermitido(): void
    {
        $apiCreateFacturaCliente = new ApiCreateFacturaCliente('');

        $apiCreateFacturaCliente->request->headers->set('Token', $this->apiKey->apikey);
        $apiCreateFacturaCliente->url = '/api/3/crearFacturaCliente';

        try {
            $apiCreateFacturaCliente->run();
        } catch (KernelException $e) {
            //
        }

        static::assertEquals('error', json_decode($apiCreateFacturaCliente->response->getContent())->status);
        static::assertEquals('Method not allowed', json_decode($apiCreateFacturaCliente->response->getContent())->message);
        static::assertEquals(200, $apiCreateFacturaCliente->response->getStatusCode());
    }

    public function testCodclienteRequerido(): void
    {
        $apiCreateFacturaCliente = new ApiCreateFacturaCliente('');

        $apiCreateFacturaCliente->request->headers->set('Token', $this->apiKey->apikey);
        $apiCreateFacturaCliente->url = '/api/3/crearFacturaCliente';
        $apiCreateFacturaCliente->request->setMethod('POST');

        try {
            $apiCreateFacturaCliente->run();
        } catch (KernelException $e) {
            //
        }

        static::assertEquals('error', json_decode($apiCreateFacturaCliente->response->getContent())->status);
        static::assertEquals('codcliente is required', json_decode($apiCreateFacturaCliente->response->getContent())->message);
        static::assertEquals(200, $apiCreateFacturaCliente->response->getStatusCode());
    }

    public function testClienteNoEncontrado(): void
    {
        $apiCreateFacturaCliente = new ApiCreateFacturaCliente('');

        $apiCreateFacturaCliente->request->headers->set('Token', $this->apiKey->apikey);
        $apiCreateFacturaCliente->url = '/api/3/crearFacturaCliente';
        $apiCreateFacturaCliente->request->setMethod('POST');
        $apiCreateFacturaCliente->request->request->set('codcliente', 1);

        try {
            $apiCreateFacturaCliente->run();
        } catch (KernelException $e) {
            //
        }

        static::assertEquals('error', json_decode($apiCreateFacturaCliente->response->getContent())->status);
        static::assertEquals('Customer not found', json_decode($apiCreateFacturaCliente->response->getContent())->message);
        static::assertEquals(200, $apiCreateFacturaCliente->response->getStatusCode());
    }

    public function testLineasRequeridas(): void
    {
        $apiCreateFacturaCliente = new ApiCreateFacturaCliente('');

        $apiCreateFacturaCliente->request->headers->set('Token', $this->apiKey->apikey);
        $apiCreateFacturaCliente->url = '/api/3/crearFacturaCliente';
        $apiCreateFacturaCliente->request->setMethod('POST');

        $cliente = $this->getRandomCustomer();
        $this->assertTrue($cliente->save());
        $apiCreateFacturaCliente->request->request->set('codcliente', $cliente->primaryColumnValue());

        try {
            $apiCreateFacturaCliente->run();
        } catch (KernelException $e) {
            //
        }

        static::assertEquals('error', json_decode($apiCreateFacturaCliente->response->getContent())->status);
        static::assertEquals('Lines are required', json_decode($apiCreateFacturaCliente->response->getContent())->message);
        static::assertEquals(200, $apiCreateFacturaCliente->response->getStatusCode());

        static::assertTrue($cliente->delete());
    }

    protected function setUp(): void
    {
        static::setDefaultSettings();

        Tools::settingsSet('default', 'enable_api', true);

        $this->apiKey = new ApiKey();
        $this->apiKey->nick = 'admin';
        $this->apiKey->description = 'tests';
        $this->apiKey->enabled = true;
        $this->apiKey->fullaccess = true;
        static::assertTrue($this->apiKey->save());
    }

    protected function tearDown(): void
    {
        static::assertTrue($this->apiKey->delete());
        $this->logErrors();
    }
}
