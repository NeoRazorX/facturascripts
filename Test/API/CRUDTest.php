<?php
namespace FacturaScripts\Test\API;

use FacturaScripts\Core\Tools;
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

    public function testListResources()
    {

        $result = $this->makeGETCurl();

        $expected = [ 'resources' => $this->getResourcesList() ];

        $this->assertEquals($expected, $result, 'response-not-equal');

    }

    public function testCreateData(){
        $form = [
           'coddivisa' => '123',
           'descripcion' => 'Divisa 123',
        ];


        $result = $this->makePOSTCurl("divisas", $form);


        $expected = [ 
            'ok' => 'Registro actualizado correctamente',
            'data' => [
                'coddivisa' => '123',
                'codiso' => null,
                'descripcion' => 'Divisa 123',
                'simbolo' => '?',
                'tasaconv' => 1,
                'tasaconvcompra' => 1
            ]
        ];

        print_r($result);


        $this->assertEquals($expected, $result, 'response-not-equal');
    }


    public function testUpdateData(){
        $result = $this->makePUTCurl("divisas/123", [
            'descripcion' => 'Divisa 123 Actualizada'
        ]);
        $expected = [
            'ok' => 'Registro actualizado correctamente',
            'data' => [
                'coddivisa' => '123',
                'codiso' => null,
                'descripcion' => 'Divisa 123 Actualizada',
                'simbolo' => '?',
                'tasaconv' => 1,
                'tasaconvcompra' => 1
            ]
        ];
        $this->assertEquals($expected, $result, 'response-not-equal');
    }

    public function testDeleteData()
    {
        $result = $this->makeDELETECurl("divisas/123");

        $expected = [
            'ok' => 'Registro eliminado correctamente',
            'data' => [
                'coddivisa' => '123',
                'codiso' => null,
                'descripcion' => 'Divisa 123 Actualizada',
                'simbolo' => '?',
                'tasaconv' => 1,
                'tasaconvcompra' => 1
            ]
        ];

        $this->assertEquals($expected, $result, 'response-not-equal');
    }

    protected function tearDown(): void
    {
        $this->stopAPIServer();
        $this->logErrors();
    }
}
