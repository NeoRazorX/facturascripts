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

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
