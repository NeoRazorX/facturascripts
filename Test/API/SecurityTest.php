<?php
namespace FacturaScripts\Test\API;

use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\ApiTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{

    use ApiTrait;
    use LogErrorsTrait;

    protected function setUp(): void
    {
        if(Tools::config('FS_API_KEY') === null) {
            $this->markTestSkipped('API desactivada. Puede activarla desde el panel de control');
        }
        $this->startAPIServer();
    }

    // Test para comprobar el flujo de seguridad de la API facturascripts
    public function testSecurityFlow()
    {
        $form = [
            'coddivisa' => '123',
            'descripcion' => 'Divisa 123',
        ];


        // paso 1: API Enabled?
        $configPath = __DIR__ . '/../../config.php';
        if (!file_exists($configPath)) {
            $this->fail("Error: El fichero config.php no existe.");
        }
        $originalConfigContent = file_get_contents($configPath);
        $modifiedContent = '';
        foreach (explode(PHP_EOL, $originalConfigContent) as $line) {
            if (strpos($line, "FS_API_KEY") === false) {
                $modifiedContent .= $line . PHP_EOL;
            }
        }
        file_put_contents($configPath, $modifiedContent);

        $result = [];

        $expected = [
            "status" => "error",
            "message" => "API desactivada. Puede activarla desde el panel de control"
        ];

        try {
            $result = $this->makePOSTCurl("divisas", $form);
        } catch (\Exception $e) {
            echo $e->getMessage();
        } finally {
            file_put_contents($configPath, $originalConfigContent);
        }

        $this->assertEquals($expected, $result, 'response-not-equal');
    
        
        // paso 2: clave de API incorrecta
        $expected = [
            "status" => "error",
            "message" => "Clave de API no válida"
        ];

        $this->setApiToken("invalid-token");

        for ($attempt = 0; $attempt < ApiController::MAX_INCIDENT_COUNT; $attempt++) {
            $result = $this->makePOSTCurl("divisas", $form);
            $this->assertEquals($expected, $result, 'response-not-equal-' . $attempt);
        }

        // paso 3: IP baneada
        $this->setApiToken('prueba');
        $expected = [
            "status" => "error",
            "message" => "Por motivos de seguridad se ha bloqueado temporalmente el acceso desde su IP."
        ];

        $result = $this->makePOSTCurl("divisas", $form);
        echo var_dump($result);
        $this->assertEquals($expected, $result, 'response-not-equal-' . $attempt);

        $this->stopAPIServer();
        $this->startAPIServer();


        // paso 4: Allowed resource
        $this->token = 'invalid-token';
        $result = $this->makePOSTCurl("divisas", $form);
        $this->setApiToken('prueba');

        $expected = [
            "status" => "error",
            "message" => "Clave de API no válida"
        ];

        $this->assertEquals($expected, $result, 'response-not-equal');
    }

    protected function tearDown(): void
    {
        $this->stopAPIServer();
        $this->logErrors();
    }
}
