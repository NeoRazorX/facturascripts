<?php

namespace FacturaScripts\Test\Core\Lib;

use SoapClient;

/**
 * Esta clase la usamos para no tener que realizar
 * peticiones a un endpoint durante los tests
 *
 * Simulamos que el servicio devuelve 'valid' true o false
 */
class SoapClientMock extends SoapClient
{
    public function __construct()
    {
        //
    }

    /**
     * @param array $requestData
     * @return array
     */
    public function checkVat(array $requestData)
    {
        $responseData = [
            ['results' => -1, 'number' => '', 'iso' => ''],
            ['results' => -1, 'number' => '123', 'iso' => ''],
            ['results' => 0, 'number' => '123456789', 'iso' => 'ES'],
            ['results' => 0, 'number' => 'ES74003828J', 'iso' => 'ES'],
            ['results' => 1, 'number' => 'ES74003828V', 'iso' => 'ES'],
            ['results' => 1, 'number' => '74003828V', 'iso' => 'ES'],
            ['results' => 1, 'number' => '43834596223', 'iso' => 'FR'],
            ['results' => 0, 'number' => '81328757100011', 'iso' => 'FR'],
            ['results' => 1, 'number' => '514356480', 'iso' => 'PT'],
            ['results' => 1, 'number' => '513969144', 'iso' => 'PT'],
            ['results' => 0, 'number' => '513967144', 'iso' => 'PT'],
            ['results' => 0, 'number' => '12345678A', 'iso' => 'IT'],
            ['results' => 1, 'number' => '02839750995', 'iso' => 'IT'],
            ['results' => 0, 'number' => '12345678A', 'iso' => 'PT'],
            ['results' => 1, 'number' => '503297887', 'iso' => 'PT'],
            ['results' => 1, 'number' => 'B13658620', 'iso' => 'ES'],
            ['results' => 0, 'number' => '12345678A', 'iso' => 'ES'],
            ['results' => 1, 'number' => 'B87533303', 'iso' => 'ES'],
            ['results' => 1, 'number' => 'B01563311', 'iso' => 'ES'],
        ];

        foreach ($responseData as $data) {
            if ($requestData['vatNumber'] == $data['number'] && $requestData['countryCode'] == $data['iso']){
                return [
                    'valid' => $data['results'] === 1,
                ];
            }
        }

        return [
            'valid' => false,
        ];
    }
}