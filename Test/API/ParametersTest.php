<?php

namespace FacturaScripts\Test\API;

use FacturaScripts\Test\Traits\ApiTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class ParametersTest extends TestCase
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

    public function testFilterLike(): void
    {
        $result = $this->makeGETCurl('pais?filter[nombre_like]=Esp');

        $expected = [
            [
                "alias" => null,
                "codiso" => "ES",
                "codpais" => "ESP",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => 40.4637,
                "longitude" => -3.7492,
                "nick" => null,
                "nombre" => "España",
                "telephone_prefix" => "+34"
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testFilterData(): void
    {
        $result = $this->makeGETCurl('pais?filter[codpais]=ESP');

        $expected = [
            [
                "alias" => null,
                "codiso" => "ES",
                "codpais" => "ESP",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => 40.4637,
                "longitude" => -3.7492,
                "nick" => null,
                "nombre" => "España",
                "telephone_prefix" => "+34"
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testFilterGreaterThan(): void
    {
        $result = $this->makeGETCurl('pais?filter[latitude_gt]=71.7069');

        $expected = [
            [
                "alias" => null,
                "codiso" => "SJ",
                "codpais" => "SJM",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => 77.5536,
                "longitude" => 23.6703,
                "nick" => null,
                "nombre" => "Svalbard y Jan Mayen",
                "telephone_prefix" => ""
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testFilterGreaterThanOrEqual(): void
    {
        $result = $this->makeGETCurl('pais?filter[latitude_gte]=71.7069');

        $expected = [
            [
                "alias" => null,
                "codiso" => "GL",
                "codpais" => "GRL",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => 71.7069,
                "longitude" => -42.6043,
                "nick" => null,
                "nombre" => "Groenlandia",
                "telephone_prefix" => "+299"
            ],
            [
                "alias" => null,
                "codiso" => "SJ",
                "codpais" => "SJM",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => 77.5536,
                "longitude" => 23.6703,
                "nick" => null,
                "nombre" => "Svalbard y Jan Mayen",
                "telephone_prefix" => ""
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testFilterLessThan(): void
    {
        $result = $this->makeGETCurl('pais?filter[latitude_lt]=-54.4296');

        $expected = [
            [
                "alias" => null,
                "codiso" => "AQ",
                "codpais" => "ATA",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => -90,
                "longitude" => 0,
                "nick" => null,
                "nombre" => "Antártida",
                "telephone_prefix" => ""
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testFilterLessThanOrEqual(): void
    {
        $result = $this->makeGETCurl('pais?filter[latitude_lte]=-54.4296');

        $expected = [
            [
                "alias" => null,
                "codiso" => "AQ",
                "codpais" => "ATA",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => -90,
                "longitude" => 0,
                "nick" => null,
                "nombre" => "Antártida",
                "telephone_prefix" => ""
            ],
            [
                "alias" => null,
                "codiso" => "GS",
                "codpais" => "SGS",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => -54.4296,
                "longitude" => -36.5879,
                "nick" => null,
                "nombre" => "Islas Georgias del Sur y Sandwich del Sur",
                "telephone_prefix" => ""
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testFilterDistinct(): void
    {
        $result = $this->makeGETCurl('pais?filter[latitude_lte]=-54.4296&filter[latitude_neq]=-90');

        $expected = [
            [
                "alias" => null,
                "codiso" => "GS",
                "codpais" => "SGS",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => -54.4296,
                "longitude" => -36.5879,
                "nick" => null,
                "nombre" => "Islas Georgias del Sur y Sandwich del Sur",
                "telephone_prefix" => ""
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testsort(): void
    {
        $result = $this->makeGETCurl('pais?filter[latitude_lte]=-54.4296&sort[latitude]=DESC');

        $expected = [
            [
                "alias" => null,
                "codiso" => "GS",
                "codpais" => "SGS",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => -54.4296,
                "longitude" => -36.5879,
                "nick" => null,
                "nombre" => "Islas Georgias del Sur y Sandwich del Sur",
                "telephone_prefix" => ""
            ],
            [
                "alias" => null,
                "codiso" => "AQ",
                "codpais" => "ATA",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => -90,
                "longitude" => 0,
                "nick" => null,
                "nombre" => "Antártida",
                "telephone_prefix" => ""
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testPagination(): void
    {
        $result = $this->makeGETCurl('pais?offset=0&limit=3');
        $expected = [
            [
                "alias" => null,
                "codiso" => "AW",
                "codpais" => "ABW",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => 12.5211,
                "longitude" => -69.9683,
                "nick" => null,
                "nombre" => "Aruba",
                "telephone_prefix" => "+297"
            ],
            [
                "alias" => null,
                "codiso" => "AF",
                "codpais" => "AFG",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => 33.9391,
                "longitude" => 67.71,
                "nick" => null,
                "nombre" => "Afganistán",
                "telephone_prefix" => "+93"
            ],
            [
                "alias" => null,
                "codiso" => "AO",
                "codpais" => "AGO",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => 11.2027,
                "longitude" => 17.8739,
                "nick" => null,
                "nombre" => "Angola",
                "telephone_prefix" => "+244"
            ]
        ];

        if ($result['status'] === 200) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }

        $result = $this->makeGETCurl('pais?offset=3&limit=3');

        $expected = [
            [
                "alias" => null,
                "codiso" => "AI",
                "codpais" => "AIA",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => 18.2206,
                "longitude" => -63.0686,
                "nick" => null,
                "nombre" => "Anguila",
                "telephone_prefix" => "+1 264"
            ],
            [
                "alias" => null,
                "codiso" => "AX",
                "codpais" => "ALA",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => 60.1785,
                "longitude" => 19.9156,
                "nick" => null,
                "nombre" => "Islas Gland",
                "telephone_prefix" => ""
            ],
            [
                "alias" => null,
                "codiso" => "AL",
                "codpais" => "ALB",
                "creation_date" => null,
                "last_nick" => null,
                "last_update" => null,
                "latitude" => 41.1533,
                "longitude" => 20.1683,
                "nick" => null,
                "nombre" => "Albania",
                "telephone_prefix" => "+355"
            ]
        ];
    }

    public function testResourceNotFound():void
    {
        $result = $this->makeGETCurl("divisas/999");

        $expected = [
            "error" => "Registro no encontrado"
        ];

        if ($result['status'] === 404) {
            $this->assertEquals($expected, $result['data'], 'response-not-equal');
        } else {
            $this->fail('API request failed');
        }
    }

    public function testFilterDataNotExists():void
    {
        $result = $this->makeGETCurl("divisas?filter[coddivisa]=999");

        $expected = [];

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
