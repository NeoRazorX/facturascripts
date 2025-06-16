<?php
namespace FacturaScripts\Test\API;

use FacturaScripts\Test\Traits\ApiTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

class ParametersTest extends TestCase
{

    use ApiTrait;
    use LogErrorsTrait;

    public function testListResources()
    {

        $result = $this->makeGETCurl();

        $expected = [ 'resources' => $this->getResourcesList() ];

        $this->assertEquals($expected, $result, 'response-not-equal');

    }

    public function testFilterLike()
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

        $this->assertEquals($expected, $result, 'response-not-equal');
    }

    public function testFilterData(){
        $result = $this->makeGETCurl('pais?filter[nombre]=Esp');

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

        $this->assertEquals($expected, $result, 'response-not-equal');
    }

    public function testFilterGreaterThan()
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

        $this->assertEquals($expected, $result, 'response-not-equal');
    }

    public function testFilterGreaterThanOrEqual(){
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

        $this->assertEquals($expected, $result, 'response-not-equal');
    }

    public function testFilterLessThan()
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

        $this->assertEquals($expected, $result, 'response-not-equal');
    }

    public function testFilterLessThanOrEqual()
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

        $this->assertEquals($expected, $result, 'response-not-equal');
    }

    public function testFilterDistinct(){
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

        $this->assertEquals($expected, $result, 'response-not-equal');
    }

    public function testsort(){
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

        $this->assertEquals($expected, $result, 'response-not-equal');
    }


    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
