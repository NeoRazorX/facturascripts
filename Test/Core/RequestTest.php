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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\Internal\UploadedFile;
use FacturaScripts\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testUploadFile(): void
    {
        $file = new UploadedFile([
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => '/tmp/php/php1h4j1o',
            'error' => 0,
            'size' => 123,
        ]);

        $this->assertEquals('test.txt', $file->name);
        $this->assertEquals('text/plain', $file->type);
        $this->assertEquals('/tmp/php/php1h4j1o', $file->tmp_name);
        $this->assertEquals(0, $file->error);
        $this->assertEquals(123, $file->size);

        $this->assertEquals('txt', $file->extension());
    }

    public function testCookies(): void
    {
        $emptyRequest = new Request();
        $this->assertNull($emptyRequest->cookie('test'));
        $this->assertEquals('default', $emptyRequest->cookie('test', 'default'));

        $data = ['cookies' => ['test' => 'value2']];
        $request = new Request($data);
        $this->assertEquals('value2', $request->cookie('test'));
        $this->assertEquals('value2', $request->cookie('test', 'default'));
        $this->assertNull($request->cookie('test2'));

        $this->assertEquals($data['cookies'], $request->cookies->all());

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

    public function testFiles(): void
    {
        $emptyRequest = new Request();
        $this->assertNull($emptyRequest->file('test'));

        $data = ['files' => ['test' => ['name' => 'test.txt', 'size' => 100]]];
        $request = new Request($data);
        $this->assertEquals('test.txt', $request->file('test')->name);
        $this->assertNull($request->file('test2'));

        $this->assertCount(1, $request->files->all());

        $this->assertTrue($request->files->has('test'));
        $this->assertFalse($request->files->has('test2'));

        $this->assertTrue($request->files->isMissing('test2'));
        $this->assertFalse($request->files->isMissing('test'));
    }

    public function testHeaders(): void
    {
        $emptyRequest = new Request();
        $this->assertNull($emptyRequest->header('test'));
        $this->assertEquals('default', $emptyRequest->header('test', 'default'));

        $data = ['headers' => ['test' => 'value2']];
        $request = new Request($data);
        $this->assertEquals('value2', $request->header('test'));
        $this->assertEquals('value2', $request->header('test', 'default'));
        $this->assertNull($request->header('test2'));

        $this->assertEquals($data['headers'], $request->headers->all());

        $this->assertTrue($request->headers->has('test'));
        $this->assertFalse($request->headers->has('test2'));

        $this->assertTrue($request->headers->isMissing('test2'));
        $this->assertFalse($request->headers->isMissing('test'));

        // asignamos un valor
        $request->headers->set('test3', 'value3');
        $this->assertEquals('value3', $request->header('test3'));

        // eliminamos un valor
        $request->headers->remove('test3');
        $this->assertNull($request->header('test3'));
    }

    public function testInputs(): void
    {
        $emptyRequest = new Request();
        $this->assertNull($emptyRequest->input('test'));
        $this->assertEquals('default', $emptyRequest->input('test', 'default'));

        $data = ['request' => ['test' => 'value3']];
        $request = new Request($data);
        $this->assertEquals('value3', $request->input('test'));
        $this->assertEquals('value3', $request->input('test', 'default'));
        $this->assertNull($request->input('test2'));

        $this->assertEquals($data['request'], $request->request->all());

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

        $data = ['query' => ['test' => 'value4']];
        $request = new Request($data);
        $this->assertEquals('value4', $request->query('test'));
        $this->assertEquals('value4', $request->query('test', 'default'));
        $this->assertNull($request->query('test2'));

        $this->assertEquals($data['query'], $request->query->all());

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

        $request->cookies->set('test', '123.45');
        $this->assertEquals('123.45', $request->cookie('test'));
        $this->assertEquals(123, $request->cookies->getInt('test'));
        $this->assertEquals(123.45, $request->cookies->getFloat('test'));
        $this->assertTrue($request->cookies->getBool('test'));
        $this->assertEquals('123.45', $request->cookies->getString('test'));

        $request->cookies->set('test-only', 'value-1');
        $this->assertEquals('value-1', $request->cookie('test-only'));
        $this->assertEquals('value-1', $request->cookies->getOnly('test-only', ['value-1', 'value-2']));
        $this->assertNull($request->cookies->getOnly('test-only', ['value-3', 'value-4']));

        $request->cookies->set('test-date', '2020/01/01');
        $this->assertEquals('2020/01/01', $request->cookie('test-date'));
        $this->assertEquals('01-01-2020', $request->cookies->getDate('test-date'));

        $request->cookies->set('test-date-time', '2020/01/01 12:13:14');
        $this->assertEquals('2020/01/01 12:13:14', $request->cookie('test-date-time'));
        $this->assertEquals('01-01-2020 12:13:14', $request->cookies->getDateTime('test-date-time'));

        $request->cookies->set('test-time', '12:13:14');
        $this->assertEquals('12:13:14', $request->cookie('test-time'));
        $this->assertEquals('12:13:14', $request->cookies->getHour('test-time'));

        $request->cookies->set('test-email', 'carlos@test.com');
        $this->assertEquals('carlos@test.com', $request->cookie('test-email'));
        $this->assertEquals('carlos@test.com', $request->cookies->getEmail('test-email'));

        $request->cookies->set('test-bad-email', 'carlos-test');
        $this->assertEquals('carlos-test', $request->cookie('test-bad-email'));
        $this->assertNull($request->cookies->getEmail('test-bad-email'));
        $this->assertEquals('', $request->cookies->getEmail('test-bad-email', false));

        $request->cookies->set('test-url', 'http://www.test.com');
        $this->assertEquals('http://www.test.com', $request->cookie('test-url'));
        $this->assertEquals('http://www.test.com', $request->cookies->getUrl('test-url'));

        $request->cookies->set('test-bad-url', 'wwwcosa');
        $this->assertEquals('wwwcosa', $request->cookie('test-bad-url'));
        $this->assertNull($request->cookies->getUrl('test-bad-url'));
        $this->assertEquals('', $request->cookies->getUrl('test-bad-url', false));
    }

    public function testCastsInputs(): void
    {
        $request = new Request();

        $request->request->set('test', '456,78');
        $this->assertEquals('456,78', $request->input('test'));
        $this->assertEquals(456, $request->request->getInt('test'));
        $this->assertEquals(456.78, $request->request->getFloat('test'));
        $this->assertTrue($request->request->getBool('test'));
        $this->assertEquals('456,78', $request->request->getString('test'));

        $request->request->set('test-only', 'code1');
        $this->assertEquals('code1', $request->input('test-only'));
        $this->assertEquals('code1', $request->getOnly('test-only', ['code1', 'code2']));
        $this->assertNull($request->request->getOnly('test-only', ['code3', 'code4']));

        $request->request->set('test-date', '01/02/2023');
        $this->assertEquals('01/02/2023', $request->input('test-date'));
        $this->assertEquals('02-01-2023', $request->request->getDate('test-date'));

        $request->request->set('test-date-time', '02-03-2024 00:01:02');
        $this->assertEquals('02-03-2024 00:01:02', $request->input('test-date-time'));
        $this->assertEquals('02-03-2024 00:01:02', $request->getDateTime('test-date-time'));

        $request->request->set('test-time', '03:04:05');
        $this->assertEquals('03:04:05', $request->input('test-time'));
        $this->assertEquals('03:04:05', $request->request->getHour('test-time'));

        $request->request->set('test-email', 'test@yolo.com');
        $this->assertEquals('test@yolo.com', $request->input('test-email'));
        $this->assertEquals('test@yolo.com', $request->request->getEmail('test-email'));

        $request->request->set('test-bad-email', 'test:yolo');
        $this->assertEquals('test:yolo', $request->input('test-bad-email'));
        $this->assertNull($request->request->getEmail('test-bad-email'));
        $this->assertEquals('', $request->request->getEmail('test-bad-email', false));

        $request->request->set('test-url', 'http://google.com/test/1234?test=1');
        $this->assertEquals('http://google.com/test/1234?test=1', $request->input('test-url'));
        $this->assertEquals('http://google.com/test/1234?test=1', $request->request->getUrl('test-url'));

        $request->request->set('test-bad-url', 'javascript:alert("test")');
        $this->assertEquals('javascript:alert("test")', $request->input('test-bad-url'));
        $this->assertNull($request->request->getUrl('test-bad-url'));
        $this->assertEquals('', $request->getUrl('test-bad-url', false));
    }

    public function testCastsQueries(): void
    {
        $request = new Request();

        $request->query->set('test', '0123.99');
        $this->assertEquals('0123.99', $request->query('test'));
        $this->assertEquals(123, $request->query->getInt('test'));
        $this->assertEquals(123.99, $request->query->getFloat('test'));
        $this->assertTrue($request->query->getBool('test'));
        $this->assertEquals('0123.99', $request->getString('test'));

        $request->query->set('test-only', 'status1');
        $this->assertEquals('status1', $request->query('test-only'));
        $this->assertEquals('status1', $request->getOnly('test-only', ['status1', 'status2']));
        $this->assertNull($request->query->getOnly('test-only', ['status3', 'status4']));

        $request->query->set('test-date', '2019-09-08');
        $this->assertEquals('2019-09-08', $request->query('test-date'));
        $this->assertEquals('08-09-2019', $request->query->getDate('test-date'));

        $request->query->set('test-date-time', '2019-09-08');
        $this->assertEquals('2019-09-08', $request->query('test-date-time'));
        $this->assertEquals('08-09-2019 00:00:00', $request->getDateTime('test-date-time'));

        $request->query->set('test-time', '12:13');
        $this->assertEquals('12:13', $request->query('test-time'));
        $this->assertEquals('12:13:00', $request->query->getHour('test-time'));

        $request->query->set('test-email', 'mail@mail.com');
        $this->assertEquals('mail@mail.com', $request->query('test-email'));
        $this->assertEquals('mail@mail.com', $request->query->getEmail('test-email'));

        $request->query->set('test-bad-email', 'mailcom');
        $this->assertEquals('mailcom', $request->query('test-bad-email'));
        $this->assertNull($request->query->getEmail('test-bad-email'));
        $this->assertEquals('', $request->query->getEmail('test-bad-email', false));

        $request->query->set('test-url', '.com.test');
        $this->assertEquals('.com.test', $request->query('test-url'));
        $this->assertNull($request->query->getUrl('test-url'));
        $this->assertEquals('', $request->query->getUrl('test-url', false));
    }
}
