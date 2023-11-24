<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace Core;

use FacturaScripts\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testCookies(): void
    {
        $emptyRequest = new Request();
        $this->assertNull($emptyRequest->cookie('test'));
        $this->assertEquals('default', $emptyRequest->cookie('test', 'default'));

        $data = ['test' => 'value2'];
        $request = new Request($data);
        $this->assertEquals('value2', $request->cookie('test'));
        $this->assertEquals('value2', $request->cookie('test', 'default'));
        $this->assertNull($request->cookie('test2'));

        $this->assertEquals($data, $request->cookies->all());

        $this->assertTrue($request->cookies->has('test'));
        $this->assertFalse($request->cookies->has('test2'));

        $this->assertTrue($request->cookies->isMissing('test2'));
        $this->assertFalse($request->cookies->isMissing('test'));

        // asignamos un valor
        $request->cookies->set('test3', 'value3');
        $this->assertEquals('value3', $request->cookie('test3'));

        // eliminamos un valor
        $request->cookies->remove('test3');
        $this->assertNull($request->cookie('test3'));
    }

    public function testInputs(): void
    {
        $emptyRequest = new Request();
        $this->assertNull($emptyRequest->input('test'));
        $this->assertEquals('default', $emptyRequest->input('test', 'default'));

        $data = ['test' => 'value3'];
        $request = new Request([], [], [], $data);
        $this->assertEquals('value3', $request->input('test'));
        $this->assertEquals('value3', $request->input('test', 'default'));
        $this->assertNull($request->input('test2'));

        $this->assertEquals($data, $request->request->all());

        $this->assertTrue($request->request->has('test'));
        $this->assertFalse($request->request->has('test2'));

        $this->assertTrue($request->request->isMissing('test2'));
        $this->assertFalse($request->request->isMissing('test'));

        // asignamos un valor
        $request->request->set('test3', 'value3');
        $this->assertEquals('value3', $request->input('test3'));

        // eliminamos un valor
        $request->request->remove('test3');
        $this->assertNull($request->input('test3'));
    }

    public function testQueries(): void
    {
        $emptyRequest = new Request();
        $this->assertNull($emptyRequest->query('test'));
        $this->assertEquals('default', $emptyRequest->query('test', 'default'));

        $data = ['test' => 'value4'];
        $request = new Request([], [], $data);
        $this->assertEquals('value4', $request->query('test'));
        $this->assertEquals('value4', $request->query('test', 'default'));
        $this->assertNull($request->query('test2'));

        $this->assertEquals($data, $request->query->all());

        $this->assertTrue($request->query->has('test'));
        $this->assertFalse($request->query->has('test2'));

        $this->assertTrue($request->query->isMissing('test2'));
        $this->assertFalse($request->query->isMissing('test'));

        // asignamos un valor
        $request->query->set('test3', 'value3');
        $this->assertEquals('value3', $request->query('test3'));

        // eliminamos un valor
        $request->query->remove('test3');
        $this->assertNull($request->query('test3'));
    }

    public function testCastsCookies(): void
    {
        $request = new Request();

        $request->cookies->set('test', '1');
        $this->assertEquals('1', $request->cookie('test'));
        $this->assertEquals(1, $request->cookies->asInt()->get('test'));
        $this->assertEquals(1.0, $request->cookies->asFloat()->get('test'));
        $this->assertTrue($request->cookies->asBool()->get('test'));
        $this->assertEquals('1', $request->cookies->asString()->get('test'));

        $request->cookies->set('test-only', 'value-1');
        $this->assertEquals('value-1', $request->cookie('test-only'));
        $this->assertEquals(0, $request->cookies->asInt()->get('test-only'));
        $this->assertEquals(0.0, $request->cookies->asFloat()->get('test-only'));
        $this->assertTrue($request->cookies->asBool()->get('test-only'));
        $this->assertEquals('value-1', $request->cookies->asString()->get('test-only'));
        $this->assertEquals('value-1', $request->cookies->asOnly(['value-1', 'value-2'])->get('test-only'));
        $this->assertNull($request->cookies->asOnly(['value-3', 'value-4'])->get('test-only'));
    }

    public function testCastsInputs(): void
    {
        $request = new Request();

        $request->request->set('test', '1');
        $this->assertEquals('1', $request->input('test'));
        $this->assertEquals(1, $request->request->asInt()->get('test'));
        $this->assertEquals(1.0, $request->request->asFloat()->get('test'));
        $this->assertTrue($request->request->asBool()->get('test'));
        $this->assertEquals('1', $request->request->asString()->get('test'));

        $request->request->set('test-only', 'value-1');
        $this->assertEquals('value-1', $request->input('test-only'));
        $this->assertEquals(0, $request->request->asInt()->get('test-only'));
        $this->assertEquals(0.0, $request->request->asFloat()->get('test-only'));
        $this->assertTrue($request->request->asBool()->get('test-only'));
        $this->assertEquals('value-1', $request->request->asString()->get('test-only'));
        $this->assertEquals('value-1', $request->request->asOnly(['value-1', 'value-2'])->get('test-only'));
        $this->assertNull($request->request->asOnly(['value-3', 'value-4'])->get('test-only'));
    }

    public function testCastsQueries(): void
    {
        $request = new Request();

        $request->query->set('test', '1');
        $this->assertEquals('1', $request->query('test'));
        $this->assertEquals(1, $request->query->asInt()->get('test'));
        $this->assertEquals(1.0, $request->query->asFloat()->get('test'));
        $this->assertTrue($request->query->asBool()->get('test'));
        $this->assertEquals('1', $request->query->asString()->get('test'));

        $request->query->set('test-only', 'value-1');
        $this->assertEquals('value-1', $request->query('test-only'));
        $this->assertEquals(0, $request->query->asInt()->get('test-only'));
        $this->assertEquals(0.0, $request->query->asFloat()->get('test-only'));
        $this->assertTrue($request->query->asBool()->get('test-only'));
        $this->assertEquals('value-1', $request->query->asString()->get('test-only'));
        $this->assertEquals('value-1', $request->query->asOnly(['value-1', 'value-2'])->get('test-only'));
        $this->assertNull($request->query->asOnly(['value-3', 'value-4'])->get('test-only'));
    }
}
