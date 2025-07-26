<?php

namespace FacturaScripts\Test\API;

use FacturaScripts\Test\Traits\ApiTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class CRUDTest extends TestCase
{

    use ApiTrait;
    use LogErrorsTrait;

    protected function setUp(): void
    {
        $this->startAPIServer();
    }

    public function testListResources(): void
    {
        $result = $this->makeGETCurl();

        $expected = ['resources' => $this->getResourcesList()];
        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testCreateData(): void
    {
        $form = [
            'coddivisa' => '123',
            'descripcion' => 'Divisa 123',
        ];

        $result = $this->makePOSTCurl("divisas", $form);

        $expected = [
            'ok' => 'Registro actualizado correctamente.',
            'data' => [
                'coddivisa' => '123',
                'codiso' => null,
                'descripcion' => 'Divisa 123',
                'simbolo' => '?',
                'tasaconv' => 1,
                'tasaconvcompra' => 1
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testUpdateData(): void
    {
        $result = $this->makePUTCurl("divisas/123", [
            'descripcion' => 'Divisa 123 Actualizada'
        ]);
        $expected = [
            'ok' => 'Registro actualizado correctamente.',
            'data' => [
                'coddivisa' => '123',
                'codiso' => null,
                'descripcion' => 'Divisa 123 Actualizada',
                'simbolo' => '?',
                'tasaconv' => 1,
                'tasaconvcompra' => 1
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testDeleteData(): void
    {
        $result = $this->makeDELETECurl("divisas/123");

        $expected = [
            'ok' => 'Registro eliminado correctamente!',
            'data' => [
                'coddivisa' => '123',
                'codiso' => null,
                'descripcion' => 'Divisa 123 Actualizada',
                'simbolo' => '?',
                'tasaconv' => 1,
                'tasaconvcompra' => 1
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    protected function tearDown(): void
    {
        $this->stopAPIServer();
        $this->logErrors();
    }
}
