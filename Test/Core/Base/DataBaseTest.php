<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2026 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Test\Core\Base;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * @covers \FacturaScripts\Core\Base\DataBase
 */
final class DataBaseTest extends TestCase
{
    use LogErrorsTrait;

    /** @var DataBase */
    private $db;

    protected function setUp(): void
    {
        $this->db = new DataBase();
    }

    public function testType(): void
    {
        $this->assertContains($this->db->type(), ['mysql', 'postgresql', '']);
    }

    public function testGetEngine(): void
    {
        $engine = $this->db->getEngine();
        $this->assertNotNull($engine);
    }

    public function testEscapeColumnSimple(): void
    {
        $escaped = $this->db->escapeColumn('nombre');

        // Esperamos `nombre` en MySQL o "nombre" en PostgreSQL
        $this->assertContains($escaped, ['`nombre`', '"nombre"']);
    }

    public function testEscapeColumnWithDot(): void
    {
        $escaped = $this->db->escapeColumn('tabla.columna');

        $this->assertContains($escaped, ['`tabla`.`columna`', '"tabla"."columna"']);
    }

    public function testEscapeColumnWithMultipleDots(): void
    {
        $escaped = $this->db->escapeColumn('a.b.c');

        $this->assertContains($escaped, ['`a`.`b`.`c`', '"a"."b"."c"']);
    }

    public function testEscapeColumnEscapesQuoteChar(): void
    {
        // Si el nombre contiene el carácter de cita, debe escaparse duplicándolo
        // para evitar inyección SQL si el nombre proviene de input no confiable.
        if ($this->db->type() === 'postgresql') {
            $escaped = $this->db->escapeColumn('a"b');
            $this->assertSame('"a""b"', $escaped);
        } else {
            $escaped = $this->db->escapeColumn('a`b');
            $this->assertSame('`a``b`', $escaped);
        }
    }

    public function testEscapeStringQuotes(): void
    {
        $escaped = $this->db->escapeString("O'Brien");

        // No debe contener la comilla simple sin escapar
        $this->assertNotEquals("O'Brien", $escaped);
        $this->assertStringNotContainsString("'O'Brien'", "'" . $escaped . "'");
    }

    public function testVar2strNull(): void
    {
        $this->assertSame('NULL', $this->db->var2str(null));
    }

    public function testVar2strBool(): void
    {
        $this->assertSame('TRUE', $this->db->var2str(true));
        $this->assertSame('FALSE', $this->db->var2str(false));
    }

    public function testVar2strString(): void
    {
        $result = $this->db->var2str('hello');
        $this->assertSame("'hello'", $result);
    }

    public function testVar2strStringWithQuote(): void
    {
        $result = $this->db->var2str("O'Brien");

        // El resultado debe estar entrecomillado y la comilla interna escapada
        $this->assertStringStartsWith("'", $result);
        $this->assertStringEndsWith("'", $result);
        $this->assertNotSame("'O'Brien'", $result);
    }

    public function testVar2strInt(): void
    {
        $this->assertSame("'42'", $this->db->var2str(42));
    }

    public function testVar2strDate(): void
    {
        $result = $this->db->var2str('15-03-2024');
        $this->assertStringStartsWith("'", $result);
        $this->assertStringEndsWith("'", $result);
    }

    public function testVar2strArrayThrows(): void
    {
        $this->expectException(\Throwable::class);
        $this->db->var2str(['a', 'b']);
    }

    public function testVar2strObjectThrows(): void
    {
        $this->expectException(\Throwable::class);
        $this->db->var2str(new \stdClass());
    }

    public function testGetOperator(): void
    {
        // El operador debe devolver una cadena no vacía
        $this->assertNotEmpty($this->db->getOperator('LIKE'));
    }

    public function testRandom(): void
    {
        $this->assertNotEmpty($this->db->random());
    }

    public function testTableExistsWithEmptyList(): void
    {
        $this->assertFalse($this->db->tableExists('tabla_inexistente_xyz', ['otra']));
    }

    public function testTableExistsFromGivenList(): void
    {
        $this->assertTrue($this->db->tableExists('foo', ['foo', 'bar']));
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
