<?php
namespace FacturaScripts\Test\API;

use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Template\ApiController;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\AgenciaTransporte;
use FacturaScripts\Dinamic\Model\ApiAccess;
use FacturaScripts\Dinamic\Model\ApiKey;
use FacturaScripts\Test\Traits\ApiTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{

    use ApiTrait;
    use LogErrorsTrait;

    protected function setUp(): void
    {
        $this->startAPIServer();

        $agencia = new AgenciaTransporte();
        $agencia = $agencia->get('TestTest');
        if($agencia !== false) {
            $this->assertTrue($agencia->delete(), 'agenciaTransporte-cant-delete');
        }
    }

    // Test para comprobar el flujo de seguridad de la API facturascripts
    public function testSecurityFlow()
    {
        
        $form = [
            'codtrans' => 'TestTest',
            'nombre' => 'La agencia inexistente',
        ];


        // paso 1: API Enabled?
        Tools::settingsSet('default', 'enable_api', false);
        Tools::settingsSave();

        $result = [];

        $expected = [
            "status" => "error",
            "message" => "API desactivada. Puede activarla desde el panel de control"
        ];

        $result = $this->makePOSTCurl("agenciatransportes", $form);

        $this->assertEquals($expected, $result, 'response-not-equal');

        Tools::settingsSet('default', 'enable_api', true);
        Tools::settingsSave();
    
        
        // paso 2: clave de API incorrecta
        $expected = [
            "status" => "error",
            "message" => "Clave de API no válida"
        ];

        $this->setApiToken("invalid-token");

        for ($attempt = 0; $attempt < ApiController::MAX_INCIDENT_COUNT; $attempt++) {
            $result = $this->makePOSTCurl("agenciatransportes", $form);
            $this->assertEquals($expected, $result, 'response-not-equal-' . $attempt);
        }


        // paso 3: IP baneada
        $this->setApiToken('prueba');
        $expected = [
            "status" => "error",
            "message" => "Por motivos de seguridad se ha bloqueado temporalmente el acceso desde su IP."
        ];

        $result = $this->makePOSTCurl("agenciatransportes", $form);
        $this->assertEquals($expected, $result, 'response-not-equal-' . $attempt);

        Cache::deleteMulti(ApiController::IP_LIST); // limpiar cache de ips bloqueadas
        $this->stopAPIServer();
        $this->startAPIServer();


        // paso 4: Allowed resource
        $this->token = 'invalid-token';
        $result = $this->makePOSTCurl("agenciatransportes", $form);

        $expected = [
            "status" => "error",
            "message" => "Clave de API no válida"
        ];

        $this->assertEquals($expected, $result, 'response-not-equal');


        //paso 5: Allowed resource
        // clave api desactivada
        $ApiKeyObj = new ApiKey();
        $ApiKeyObj->clear();
        $ApiKeyObj->description = 'Clave de pruebas';
        $ApiKeyObj->nick = 'tester';
        $ApiKeyObj->enabled = false;
        $this->assertTrue($ApiKeyObj->save(), 'can-not-save-key');
        
        $this->setApiToken($ApiKeyObj->apikey);
        
        $expected = [
            "status" => "error",
            "message" => "Clave de API no válida"
        ];
        
        $result = $this->makePOSTCurl("agenciatransportes", $form);
        $this->assertEquals($expected, $result, 'response-not-equal');

        // clave api sin permisos (pero activada)
        $ApiKeyObj->enabled = true;
        $this->assertTrue($ApiKeyObj->save(), 'can-not-save-key');

        $expected = [
            "status" => "error",
            "message" => "forbidden"
        ];

        $result = $this->makePOSTCurl("agenciatransportes", $form);
        $this->assertEquals($expected, $result, 'response-not-equal');

        // clave api con todos los permisos
        $ApiKeyObj->fullaccess = true;
        $this->assertTrue($ApiKeyObj->save(), 'can-not-save-key');

        $expected = [
            "ok" => "Registro actualizado correctamente.",
            "data" => [
                "activo" => true,
                "codtrans" => "TestTest",
                "nombre" => "La agencia inexistente",
                "telefono" => null,
                "web" => null
            ]
        ];

        $result = $this->makePOSTCurl("agenciatransportes", $form);
        $this->assertEquals($expected, $result, 'response-not-equal');
        
        // clave api con permisos limitados
        $ApiKeyObj->fullaccess = false;
        $this->assertTrue($ApiKeyObj->save(), 'can-not-save-key');
        $this->assertTrue(ApiAccess::addResourcesToApiKey($ApiKeyObj->id, ['agenciatransportes'], true), 'can-not-add-resource');
        
        $form = [
            'nombre' => 'La agencia intangible',
            'activo' => false
        ];
        
        $result = $this->makePUTCurl("agenciatransportes/TestTest", $form);
        
        $expected = [
            "ok" => "Registro actualizado correctamente.",
            "data" => [
                "activo" => false,
                "codtrans" => "TestTest",
                "nombre" => "La agencia intangible",
                "telefono" => null,
                "web" => null
            ]
        ];
        
        $this->assertEquals($expected, $result, 'response-not-equal');

        $expected = [
            "status" => "error",
            "message" => "forbidden"
        ];

        $result = $this->makeGETCurl("divisas");
        $this->assertEquals($expected, $result, 'response-not-equal');

        $this->assertTrue(ApiAccess::addResourcesToApiKey($ApiKeyObj->id, ['agenciatransportes'], false), 'can-not-add-resource');
        $result = $this->makeGETCurl("agenciatransportes");
        $this->assertEquals($expected, $result, 'response-not-equal');

        $ApiKeyObj->delete();
    }

    protected function tearDown(): void
    {
        $agencia = new AgenciaTransporte();
        $agencia->get('TestTest');
        if($agencia !== false) {
            $this->assertTrue($agencia->delete(), 'agenciaTransporte-cant-delete');
        }

        $this->stopAPIServer();
        $this->logErrors();
    }
}
