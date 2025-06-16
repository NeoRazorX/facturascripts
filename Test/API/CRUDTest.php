<?php
namespace FacturaScripts\Test\API;

use FacturaScripts\Test\Traits\ApiTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class CRUDTest extends TestCase
{

    use ApiTrait;
    use LogErrorsTrait;

    protected function startAPIServer(): void
    {
        $this->document_root = __DIR__ . '/../../';
        $this->router = __DIR__ . '/../../index.php';

        $this->url = "http://{$this->host}:{$this->port}/api/3/";
        $this->command = "php -S {$this->host}:{$this->port} -t {$this->document_root} {$this->router} > /dev/null 2>&1 & echo $!";
        $this->pid = shell_exec($this->command);
        sleep(1);
    }

    protected function stopAPIServer(): void
    {
        shell_exec("kill $this->pid");
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
        $this->logErrors();
    }
}
