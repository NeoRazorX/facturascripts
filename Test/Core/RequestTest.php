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

        $this->assertEquals($data, $request->cookies());

        $this->assertTrue($request->hasCookie('test'));
        $this->assertFalse($request->hasCookie('test2'));

        $this->assertTrue($request->isCookieMissing('test2'));
        $this->assertFalse($request->isCookieMissing('test'));

        // asignamos un valor
        $request->cookies->set('test3', 'value3');
        $this->assertEquals('value3', $request->cookie('test3'));

        // eliminamos un valor
        $request->cookies->remove('test3');
        $this->assertNull($request->cookie('test3'));
    }

    public function testFoundationCookies(): void
    {
        $emptyRequest = new Request();
        $this->assertNull($emptyRequest->cookies->get('test'));
        $this->assertEquals('default', $emptyRequest->cookies->get('test', 'default'));

        $data = ['test' => 'value2'];
        $request = new Request($data);
        $this->assertEquals('value2', $request->cookies->get('test'));
        $this->assertEquals('value2', $request->cookies->get('test', 'default'));
        $this->assertNull($request->cookies->get('test2'));

        $this->assertEquals($data, $request->cookies->all());

        $this->assertTrue($request->cookies->has('test'));
        $this->assertFalse($request->cookies->has('test2'));

        // asignamos un valor
        $request->cookies->set('test3', 'value3');
        $this->assertEquals('value3', $request->cookies->get('test3'));

        // eliminamos un valor
        $request->cookies->remove('test3');
        $this->assertNull($request->cookies->get('test3'));
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

        $this->assertEquals($data, $request->inputs());

        $this->assertTrue($request->hasInput('test'));
        $this->assertFalse($request->hasInput('test2'));

        $this->assertTrue($request->isInputMissing('test2'));
        $this->assertFalse($request->isInputMissing('test'));

        // asignamos un valor
        $request->request->set('test3', 'value3');
        $this->assertEquals('value3', $request->input('test3'));

        // eliminamos un valor
        $request->request->remove('test3');
        $this->assertNull($request->input('test3'));
    }

    public function testFoundationRequest(): void
    {
        $emptyRequest = new Request();
        $this->assertNull($emptyRequest->request->get('test'));
        $this->assertEquals('default', $emptyRequest->request->get('test', 'default'));

        $data = ['test' => 'value3'];
        $request = new Request([], [], [], $data);
        $this->assertEquals('value3', $request->request->get('test'));
        $this->assertEquals('value3', $request->request->get('test', 'default'));
        $this->assertNull($request->request->get('test2'));

        $this->assertEquals($data, $request->request->all());

        $this->assertTrue($request->request->has('test'));
        $this->assertFalse($request->request->has('test2'));

        // asignamos un valor
        $request->request->set('test3', 'value3');
        $this->assertEquals('value3', $request->request->get('test3'));

        // eliminamos un valor
        $request->request->remove('test3');
        $this->assertNull($request->request->get('test3'));
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

        $this->assertEquals($data, $request->queries());

        $this->assertTrue($request->hasQuery('test'));
        $this->assertFalse($request->hasQuery('test2'));

        $this->assertTrue($request->isQueryMissing('test2'));
        $this->assertFalse($request->isQueryMissing('test'));

        // asignamos un valor
        $request->query->set('test3', 'value3');
        $this->assertEquals('value3', $request->query('test3'));

        // eliminamos un valor
        $request->query->remove('test3');
        $this->assertNull($request->query('test3'));
    }

    public function testFoundationQueries(): void
    {
        $emptyRequest = new Request();
        $this->assertNull($emptyRequest->query->get('test'));
        $this->assertEquals('default', $emptyRequest->query->get('test', 'default'));

        $data = ['test' => 'value4'];
        $request = new Request([], [], $data);
        $this->assertEquals('value4', $request->query->get('test'));
        $this->assertEquals('value4', $request->query->get('test', 'default'));
        $this->assertNull($request->query->get('test2'));

        $this->assertEquals($data, $request->query->all());

        $this->assertTrue($request->query->has('test'));
        $this->assertFalse($request->query->has('test2'));

        // asignamos un valor
        $request->query->set('test3', 'value3');
        $this->assertEquals('value3', $request->query->get('test3'));

        // eliminamos un valor
        $request->query->remove('test3');
        $this->assertNull($request->query->get('test3'));
    }
}
