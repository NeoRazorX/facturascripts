<?php
namespace FacturaScripts\Test\API;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
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

    protected $securityFlowApiKeyObj;

    protected function setUp(): void
    {
        $this->startAPIServer();

        $agencia = new AgenciaTransporte();
        $agencia = $agencia->get('TestTest');
        if ($agencia !== false) {
            $this->assertTrue($agencia->delete(), 'agenciaTransporte-cant-delete');
        }

        Cache::deleteMulti(ApiController::IP_LIST);
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

        if ($result['status'] === 409) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }

        Tools::settingsSet('default', 'enable_api', true);
        Tools::settingsSave();


        // paso 2: clave de API incorrecta e IP baneada
        for ($i = 0; $i < 3; $i++) { // 3 intentos
            $expected = [
                "status" => "error",
                "message" => "Clave de API no válida"
            ];

            $this->setApiToken("invalid-token");

            for ($attempt = 0; $attempt < ApiController::MAX_INCIDENT_COUNT; $attempt++) {
                $result = $this->makePOSTCurl("agenciatransportes", $form);
                if ($result['status'] === 401) {
                    $this->assertEquals($expected, $result['data'], 'response-not-equal-' . $attempt);
                } else {
                    $this->fail('API request failed');
                }
            }

            $this->setApiToken('prueba');
            $expected = [
                "status" => "error",
                "message" => "Por motivos de seguridad se ha bloqueado temporalmente el acceso desde su IP."
            ];

            $result = $this->makePOSTCurl("agenciatransportes", $form);
            if ($result['status'] === 401) {
                $this->assertEquals($expected, $result['data'], 'response-not-equal-' . $attempt);
            } else {
                $this->fail('API request failed');
            }

            Cache::deleteMulti(ApiController::IP_LIST); // limpiar cache de ips bloqueadas
        }


        // paso 3: Allowed resource
        $this->token = 'invalid-token';
        $result = $this->makePOSTCurl("agenciatransportes", $form);

        $expected = [
            "status" => "error",
            "message" => "Clave de API no válida"
        ];

        if ($result['status'] === 401) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }


        //paso 4: Allowed resource
        // clave api desactivada
        $ApiKeyObj = new ApiKey();
        $this->securityFlowApiKeyObj = $ApiKeyObj;
        $ApiKeyObj->clear();
        $ApiKeyObj->description = 'Clave de pruebas';
        $ApiKeyObj->nick = $this->getApiUser()->nick;
        $ApiKeyObj->enabled = false;
        $this->assertTrue($ApiKeyObj->save(), 'can-not-save-key');

        $this->setApiToken($ApiKeyObj->apikey);

        $expected = [
            "status" => "error",
            "message" => "Clave de API no válida"
        ];

        $result = $this->makePOSTCurl("agenciatransportes", $form);
        if ($result['status'] === 401) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }

        // clave api sin permisos (pero activada)
        $ApiKeyObj->enabled = true;
        $this->assertTrue($ApiKeyObj->save(), 'can-not-save-key');

        $expected = [
            "status" => "error",
            "message" => "forbidden"
        ];

        $result = $this->makePOSTCurl("agenciatransportes", $form);
        if ($result['status'] === 403) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }

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
        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }

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

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }

        // clave api accediendo a recurso sin permisos
        $result = $this->makeGETCurl("divisas");

        $expected = [
            "status" => "error",
            "message" => "forbidden"
        ];

        $result = $this->makeGETCurl("divisas");
        if ($result['status'] === 403) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }

        $agenciasAccess = $ApiKeyObj->getResourceAccess('agenciatransportes');
        $this->assertTrue($agenciasAccess !== false, 'can-not-get-access');
        $this->assertTrue($agenciasAccess->setAllowed(false, false, false, false), 'can-not-update-access');

        $result = $this->makeGETCurl("agenciatransportes");
        print_r(var_dump($result));
        if ($result['status'] === 403) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }

        $ApiKeyObj->delete();
        Cache::deleteMulti(ApiController::IP_LIST);
    }

    protected function tearDown(): void
    {
        // limpieza
        $apiAccess = new ApiAccess();
        $allAccesses = $apiAccess->all([new DataBaseWhere('idapikey', $this->securityFlowApiKeyObj->id)], [], 0);
        foreach ($allAccesses as $access) {
            $this->assertTrue($access->delete(), 'can-not-delete-access');
        }
        $this->assertTrue($this->securityFlowApiKeyObj->delete(), 'can-not-delete-key');
        Cache::deleteMulti(ApiController::IP_LIST);

        $agencia = new AgenciaTransporte();
        $agencia->get('TestTest');
        if ($agencia !== false) {
            $this->assertTrue($agencia->delete(), 'agenciaTransporte-cant-delete');
        }

        $this->stopAPIServer();
        $this->logErrors();
    }
}
