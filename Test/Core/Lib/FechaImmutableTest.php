<?php declare(strict_types=1);

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\FechaImmutable;
use PHPUnit\Framework\TestCase;

class FechaImmutableTest extends TestCase
{
    public function testStartOfMonth(): void
    {
        $fechaImmutable = new FechaImmutable("2023", "12", "17");
        $result = $fechaImmutable->startOfMonth();

        $this->assertEquals('2023-12-01 00:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function testEndOfMonth(): void
    {
        $fechaImmutable = new FechaImmutable("2023", "12", "17");
        $result = $fechaImmutable->endOfMonth();

        $this->assertEquals('2023-12-31 23:59:59', $result->format('Y-m-d H:i:s'));
    }

    public function testStartOfWeek(): void
    {
        $fechaImmutable = new FechaImmutable("2023", "12", "17");
        $result = $fechaImmutable->startOfWeek();

        $this->assertEquals('2023-11-27 00:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function testEndOfWeek(): void
    {
        $fechaImmutable = new FechaImmutable("2023", "11", "17");
        $result = $fechaImmutable->endOfWeek();

        $this->assertEquals('2023-12-03 23:59:59', $result->format('Y-m-d H:i:s'));
    }

    public function testFormat(): void
    {
        $fechaImmutable = new FechaImmutable("2023", "12", "17");
        $result = $fechaImmutable->format("Y-m");

        $this->assertEquals('2023-12', $result);
    }

    public function testToPeriod(): void
    {
        $fechaImmutable = new FechaImmutable("2023", "12", "17");
        $result = $fechaImmutable->toPeriod();

        $this->assertEquals('2023-11-27 00:00:00', $result[0]->format('Y-m-d H:i:s'));
        $this->assertEquals('2023-12-31 00:00:00', $result[34]->format('Y-m-d H:i:s'));
    }

    public function testBetween(): void
    {
        $fechaImmutable = new FechaImmutable("2023", "12", "17");

        $fechaTest = new FechaImmutable("2023", "12", "10");
        $result = $fechaImmutable->between($fechaTest->date);
        $this->assertTrue($result);

        $fechaTest = new FechaImmutable("2023", "11", "10");
        $result = $fechaImmutable->between($fechaTest->date);
        $this->assertFalse($result);
    }

    public function testIs(): void
    {
        $fecha = new FechaImmutable("2023", "12", "17");
        $fechaTest = new FechaImmutable("2023", "12", "10");

        $result = $fecha->is($fechaTest);
        $this->assertFalse($result);

        $fechaTest = new FechaImmutable("2023", "12", "17");
        $result = $fecha->is($fechaTest);
        $this->assertTrue($result);
    }
}
