<?php declare(strict_types=1);

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\ApiCreateDocument;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

class ApiCreateDocumentTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;
    use DefaultSettingsTrait;

    protected function setUp(): void
    {
        $this->setDefaultSettings();
    }

    /**
     * @dataProvider documentNameProvider
     * @throws ReflectionException
     */
    public function testPuedeCrearDocumento($documentName)
    {
        $apiCreateDocument = new ApiCreateDocument('');
        $reflection = new ReflectionClass($apiCreateDocument);

        $property = new ReflectionProperty(ApiCreateDocument::class, 'request');
        $property->setAccessible(true);

        /** @var Request $request */
        $request = $property->getValue($apiCreateDocument);
        $_SERVER['REQUEST_METHOD'] = 'POST';

        if (str_contains($documentName, 'Cliente')) {
            $subject = $this->getRandomCustomer();
        } else {
            $subject = $this->getRandomSupplier();
        }
        $this->assertTrue($subject->save());

        $request->request->set('documentname', $documentName);
        $request->request->set('codsubject', $subject->primaryColumnValue());
        $request->request->set('lineas', json_encode([
            [
                'cantidad' => 2,
                'descripcion' => 'test-descripcion-linea',
                'pvpunitario' => 2
            ]
        ]));

        $method = $reflection->getMethod('runResource');
        $method->setAccessible(true);
        $method->invokeArgs($apiCreateDocument, []);

        $property = new ReflectionProperty(ApiCreateDocument::class, 'response');
        $property->setAccessible(true);

        /** @var Response $response */
        $response = $property->getValue($apiCreateDocument);

        $content = json_decode($response->getContent(), true);

        // creamos un documento para obtener su primaryColumnValue()
        $documentNamespace = '\\FacturaScripts\\Dinamic\\Model\\' . $documentName;
        $doc = new $documentNamespace($content['doc']);

        $idDocument = $doc->primaryColumnValue();
        $docBBDD = new $documentNamespace();
        $docBBDD->loadFromCode($idDocument);

        $this->assertEquals($subject->razonsocial, $docBBDD->getSubject()->razonsocial);

        $this->assertEquals(4, $docBBDD->neto);
        $this->assertEquals(0.84, $docBBDD->totaliva);
        $this->assertEquals(4.84, $docBBDD->total);

        // eliminamos
        $this->assertTrue($docBBDD->delete());

        $this->assertTrue($subject->getDefaultAddress()->delete());
        $this->assertTrue($subject->delete());
    }

    public function documentNameProvider(): array
    {
        return [
            ['PresupuestoCliente'],
            ['PedidoCliente'],
            ['AlbaranCliente'],
            ['FacturaCliente'],
            ['PresupuestoProveedor'],
            ['PedidoProveedor'],
            ['AlbaranProveedor'],
            ['FacturaProveedor'],
        ];
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}