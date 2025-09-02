<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Internal\Headers;
use FacturaScripts\Core\Internal\RequestFiles;
use FacturaScripts\Core\Internal\SubRequest;
use FacturaScripts\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    private function createRequest(array $data = []): Request
    {
        return new Request($data);
    }

    public function testBasicRequest(): void
    {
        $request = $this->createRequest();

        $this->assertInstanceOf(SubRequest::class, $request->cookies);
        $this->assertInstanceOf(RequestFiles::class, $request->files);
        $this->assertInstanceOf(Headers::class, $request->headers);
        $this->assertInstanceOf(SubRequest::class, $request->query);
        $this->assertInstanceOf(SubRequest::class, $request->request);
    }

    public function testCreateFromGlobals(): void
    {
        // Guardar valores originales
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalCookie = $_COOKIE;
        $originalFiles = $_FILES;
        $originalServer = $_SERVER;

        try {
            // Configurar superglobales de prueba
            $_GET = ['page' => 'test', 'id' => '123'];
            $_POST = ['name' => 'John', 'email' => 'john@example.com'];
            $_COOKIE = ['session' => 'abc123'];
            $_FILES = ['upload' => [
                'name' => 'test.txt',
                'type' => 'text/plain',
                'tmp_name' => '/tmp/phptest',
                'error' => 0,
                'size' => 1024
            ]];
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['HTTP_HOST'] = 'localhost';
            $_SERVER['REQUEST_URI'] = '/test?page=test&id=123';

            $request = Request::createFromGlobals();

            $this->assertEquals('test', $request->query('page'));
            $this->assertEquals('123', $request->query('id'));
            $this->assertEquals('John', $request->input('name'));
            $this->assertEquals('john@example.com', $request->input('email'));
            $this->assertEquals('abc123', $request->cookie('session'));
            $this->assertEquals('POST', $request->method());
        } finally {
            // Restaurar valores originales
            $_GET = $originalGet;
            $_POST = $originalPost;
            $_COOKIE = $originalCookie;
            $_FILES = $originalFiles;
            $_SERVER = $originalServer;
        }
    }

    public function testGetMethods(): void
    {
        $request = $this->createRequest([
            'query' => ['q_param' => 'query_value'],
            'request' => ['r_param' => 'request_value', 'priority' => 'post']
        ]);

        // Test get() con prioridad a request sobre query
        $this->assertEquals('query_value', $request->get('q_param'));
        $this->assertEquals('request_value', $request->get('r_param'));
        $this->assertEquals('post', $request->get('priority'));
        $this->assertNull($request->get('nonexistent'));
        $this->assertEquals('default', $request->get('nonexistent', 'default'));
    }

    public function testGetInt(): void
    {
        $request = $this->createRequest([
            'query' => ['int1' => '42', 'int2' => 'not_a_number', 'int3' => '3.14'],
            'request' => ['int4' => '100']
        ]);

        $this->assertEquals(42, $request->getInt('int1'));
        $this->assertEquals(0, $request->getInt('int2', false));
        $this->assertEquals(3, $request->getInt('int3'));
        $this->assertEquals(100, $request->getInt('int4'));
        $this->assertNull($request->getInt('nonexistent'));
        $this->assertEquals(0, $request->getInt('nonexistent', false));
    }

    public function testGetFloat(): void
    {
        $request = $this->createRequest([
            'query' => ['float1' => '3.14', 'float2' => '42', 'float3' => 'not_a_number'],
            'request' => ['float4' => '2.718']
        ]);

        $this->assertEquals(3.14, $request->getFloat('float1'));
        $this->assertEquals(42.0, $request->getFloat('float2'));
        $this->assertEquals(0.0, $request->getFloat('float3', false));
        $this->assertEquals(2.718, $request->getFloat('float4'));
        $this->assertNull($request->getFloat('nonexistent'));
    }

    public function testGetBool(): void
    {
        $request = $this->createRequest([
            'query' => [
                'bool1' => 'true',
                'bool2' => '1',
                'bool3' => 'false',
                'bool4' => '0',
                'bool5' => 'yes',
                'bool6' => ''
            ]
        ]);

        $this->assertTrue($request->getBool('bool1'));
        $this->assertTrue($request->getBool('bool2'));
        $this->assertTrue($request->getBool('bool3')); // 'false' string es true en PHP
        $this->assertFalse($request->getBool('bool4'));
        $this->assertTrue($request->getBool('bool5'));
        $this->assertFalse($request->getBool('bool6')); // string vacía es false
        $this->assertNull($request->getBool('nonexistent'));
        $this->assertFalse($request->getBool('nonexistent', false));
    }

    public function testGetString(): void
    {
        $request = $this->createRequest([
            'query' => ['str1' => 'Hello World', 'str2' => '  trimmed  ', 'str3' => '<script>alert(1)</script>']
        ]);

        $this->assertEquals('Hello World', $request->getString('str1'));
        $this->assertEquals('  trimmed  ', $request->getString('str2'));
        $this->assertEquals('<script>alert(1)</script>', $request->getString('str3'));
        $this->assertNull($request->getString('nonexistent'));
        $this->assertEquals('', $request->getString('nonexistent', false));
    }

    public function testGetEmail(): void
    {
        $request = $this->createRequest([
            'query' => [
                'email1' => 'valid@example.com',
                'email2' => 'UPPER@EXAMPLE.COM',
                'email3' => 'invalid.email',
                'email4' => 'another@'
            ]
        ]);

        $this->assertEquals('valid@example.com', $request->getEmail('email1'));
        $this->assertEquals('upper@example.com', $request->getEmail('email2'));
        $this->assertEquals('', $request->getEmail('email3', false));
        $this->assertEquals('', $request->getEmail('email4', false));
        $this->assertNull($request->getEmail('nonexistent'));
    }

    public function testGetDate(): void
    {
        $request = $this->createRequest([
            'query' => [
                'date1' => '2025-01-15',
                'date2' => '16-02-2025',
                'date3' => 'invalid'
            ]
        ]);

        $this->assertEquals('15-01-2025', $request->getDate('date1'));
        $this->assertEquals('16-02-2025', $request->getDate('date2', false));
        $this->assertEquals('', $request->getDate('date3', false));
        $this->assertNull($request->getDate('date3'));
        $this->assertNull($request->getDate('nonexistent'));
    }

    public function testGetDateTime(): void
    {
        $request = $this->createRequest([
            'query' => [
                'datetime1' => '2025-01-15 14:30:00',
                'datetime2' => '2025-01-15T14:30:00',
                'datetime3' => 'invalid'
            ]
        ]);

        $this->assertEquals('15-01-2025 14:30:00', $request->getDateTime('datetime1'));
        $this->assertNotEmpty($request->getDateTime('datetime2'));
        $this->assertEquals('', $request->getDateTime('datetime3', false));
        $this->assertNull($request->getDateTime('nonexistent'));
    }

    public function testGetHour(): void
    {
        $request = $this->createRequest([
            'query' => [
                'hour1' => '14:30',
                'hour2' => '14:30:45',
                'hour3' => '25:00',
                'hour4' => 'invalid'
            ]
        ]);

        $this->assertEquals('14:30:00', $request->getHour('hour1'));
        $this->assertEquals('14:30:45', $request->getHour('hour2'));
        $this->assertEquals('', $request->getHour('hour3', false));
        $this->assertEquals('', $request->getHour('hour4', false));
        $this->assertNull($request->getHour('nonexistent'));
    }

    public function testGetArray(): void
    {
        $request = $this->createRequest([
            'query' => [
                'array1' => ['a', 'b', 'c'],
                'array2' => 'not_array'
            ]
        ]);

        $this->assertEquals(['a', 'b', 'c'], $request->getArray('array1'));
        $this->assertEquals([], $request->getArray('array2', false));
        $this->assertNull($request->getArray('nonexistent'));
    }

    public function testGetAlnum(): void
    {
        $request = $this->createRequest([
            'query' => [
                'alnum1' => 'abc123',
                'alnum2' => 'with spaces',
                'alnum3' => 'special!@#$'
            ]
        ]);

        $this->assertEquals('abc123', $request->getAlnum('alnum1'));
        $this->assertEquals('withspaces', $request->getAlnum('alnum2'));
        $this->assertEquals('special', $request->getAlnum('alnum3'));
    }

    public function testGetUrl(): void
    {
        $request = $this->createRequest([
            'query' => [
                'url1' => 'https://example.com',
                'url2' => 'http://test.org/path?query=1',
                'url3' => 'not_a_url',
                'url4' => 'javascript:alert(1)'
            ]
        ]);

        $this->assertEquals('https://example.com', $request->getUrl('url1'));
        $this->assertEquals('http://test.org/path?query=1', $request->getUrl('url2'));
        $this->assertEquals('', $request->getUrl('url3', false));
        $this->assertEquals('', $request->getUrl('url4', false));
    }

    public function testGetOnly(): void
    {
        $request = $this->createRequest([
            'query' => ['status' => 'active', 'invalid' => 'pending']
        ]);

        $this->assertEquals('active', $request->getOnly('status', ['active', 'inactive', 'pending']));
        $this->assertNull($request->getOnly('invalid', ['active', 'inactive']));
        $this->assertNull($request->getOnly('nonexistent', ['value1', 'value2']));
    }

    public function testAll(): void
    {
        $request = $this->createRequest([
            'query' => ['q1' => 'value1', 'q2' => 'value2'],
            'request' => ['r1' => 'value3', 'r2' => 'value4']
        ]);

        $all = $request->all();
        $this->assertEquals('value1', $all['q1']);
        $this->assertEquals('value2', $all['q2']);
        $this->assertEquals('value3', $all['r1']);
        $this->assertEquals('value4', $all['r2']);

        $specific = $request->all('q1', 'r2');
        $this->assertEquals(['q1' => 'value1', 'r2' => 'value4'], $specific);
    }

    public function testHas(): void
    {
        $request = $this->createRequest([
            'query' => ['q1' => 'value1'],
            'request' => ['r1' => 'value2']
        ]);

        $this->assertTrue($request->has('q1'));
        $this->assertTrue($request->has('r1'));
        $this->assertFalse($request->has('nonexistent'));
        $this->assertTrue($request->has('q1', 'r1'));
        $this->assertFalse($request->has('q1', 'nonexistent'));
    }

    public function testCookie(): void
    {
        $request = $this->createRequest([
            'cookies' => ['session' => 'abc123', 'token' => 'xyz789']
        ]);

        $this->assertEquals('abc123', $request->cookie('session'));
        $this->assertEquals('xyz789', $request->cookie('token'));
        $this->assertNull($request->cookie('nonexistent'));
        $this->assertEquals('default', $request->cookie('nonexistent', 'default'));
    }

    public function testHeader(): void
    {
        $request = $this->createRequest([
            'headers' => [
                'HTTP_HOST' => 'example.com',
                'HTTP_USER_AGENT' => 'Test Browser',
                'CONTENT_TYPE' => 'application/json'
            ]
        ]);

        $this->assertEquals('example.com', $request->header('HTTP_HOST'));
        $this->assertEquals('Test Browser', $request->header('HTTP_USER_AGENT'));
        $this->assertEquals('application/json', $request->header('CONTENT_TYPE'));
        $this->assertNull($request->header('NONEXISTENT'));
    }

    public function testBrowser(): void
    {
        $browsers = [
            'Mozilla/5.0 Chrome/96.0' => 'chrome',
            'Mozilla/5.0 Edg/96.0' => 'edge',
            'Mozilla/5.0 Firefox/95.0' => 'firefox',
            'Mozilla/5.0 Safari/605.1' => 'safari',
            'Opera/9.80' => 'opera',
            'Mozilla/4.0 MSIE 8.0' => 'ie',
            'Unknown Browser' => 'unknown'
        ];

        foreach ($browsers as $userAgent => $expected) {
            $_SERVER['HTTP_USER_AGENT'] = $userAgent;
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals($expected, $request->browser());
        }
    }

    public function testOs(): void
    {
        $systems = [
            'Windows NT 10.0' => 'windows',
            'Macintosh; Intel Mac OS X' => 'mac',
            'X11; Linux x86_64' => 'linux',
            'X11; Unix' => 'unix',
            'SunOS 5.11' => 'sun',
            'FreeBSD' => 'bsd',
            'Unknown System' => 'unknown'
        ];

        foreach ($systems as $userAgent => $expected) {
            $_SERVER['HTTP_USER_AGENT'] = $userAgent;
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals($expected, $request->os());
        }
    }

    public function testIp(): void
    {
        $originalServer = $_SERVER;

        try {
            // Test IP desde Cloudflare
            $_SERVER = ['HTTP_CF_CONNECTING_IP' => '192.168.1.1'];
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('192.168.1.1', $request->ip());

            // Test IP desde proxy
            $_SERVER = ['HTTP_X_FORWARDED_FOR' => '10.0.0.1'];
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('10.0.0.1', $request->ip());

            // Test IP directa
            $_SERVER = ['REMOTE_ADDR' => '172.16.0.1'];
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('172.16.0.1', $request->ip());

            // Test sin IP
            $_SERVER = [];
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('::1', $request->ip());
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testMethod(): void
    {
        $originalServer = $_SERVER;

        try {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('GET', $request->method());
            $this->assertTrue($request->isMethod('GET'));
            $this->assertFalse($request->isMethod('POST'));

            $_SERVER['REQUEST_METHOD'] = 'POST';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('POST', $request->method());
            $this->assertTrue($request->isMethod('POST'));

            $_SERVER['REQUEST_METHOD'] = 'PUT';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('PUT', $request->method());
            $this->assertTrue($request->isMethod('PUT'));
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testUrl(): void
    {
        $originalServer = $_SERVER;

        try {
            $_SERVER['REQUEST_URI'] = '/admin/users/edit?id=1';
            $request = $this->createRequest(['headers' => $_SERVER]);

            // URL completa sin query string
            $this->assertEquals('/admin/users/edit', $request->url());

            // Posiciones específicas
            $this->assertEquals('', $request->url(0));
            $this->assertEquals('admin', $request->url(1));
            $this->assertEquals('users', $request->url(2));
            $this->assertEquals('edit', $request->url(3));
            $this->assertEquals('', $request->url(4));

            // Posiciones negativas
            $this->assertEquals('edit', $request->url(-1));
            $this->assertEquals('users', $request->url(-2));
            $this->assertEquals('admin', $request->url(-3));
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testUrlWithRoute(): void
    {
        $originalServer = $_SERVER;

        try {
            // Simular configuración de ruta
            $_SERVER['REQUEST_URI'] = '/app/admin/dashboard?page=1';
            $request = $this->createRequest(['headers' => $_SERVER]);

            // La clase Request usa Tools::config('route') internamente
            // Como no podemos modificar esa configuración aquí, 
            // solo probamos que el método funciona
            $this->assertIsString($request->url());
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testFullUrl(): void
    {
        $originalServer = $_SERVER;

        try {
            $_SERVER['HTTP_HOST'] = 'example.com';
            $_SERVER['REQUEST_URI'] = '/path/to/page?query=1';
            $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
            $_SERVER['QUERY_STRING'] = 'query=1';

            $request = $this->createRequest(['headers' => $_SERVER]);

            $this->assertStringContainsString('example.com', $request->fullUrl());
            $this->assertStringContainsString('/path/to/page', $request->fullUrl());
            $this->assertStringContainsString('query=1', $request->fullUrl());
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testUrlWithQuery(): void
    {
        $originalServer = $_SERVER;

        try {
            $_SERVER['REQUEST_URI'] = '/admin/users';
            $_SERVER['QUERY_STRING'] = 'page=1&sort=name';

            $request = $this->createRequest(['headers' => $_SERVER]);

            $this->assertEquals('/admin/users?page=1&sort=name', $request->urlWithQuery());
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testHost(): void
    {
        $originalServer = $_SERVER;

        try {
            $_SERVER['HTTP_HOST'] = 'example.com';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('example.com', $request->host());

            $_SERVER['HTTP_HOST'] = 'subdomain.example.org:8080';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('subdomain.example.org:8080', $request->host());
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testProtocol(): void
    {
        $originalServer = $_SERVER;

        try {
            $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('HTTP/1.1', $request->protocol());

            $_SERVER['SERVER_PROTOCOL'] = 'HTTP/2.0';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('HTTP/2.0', $request->protocol());
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testIsSecure(): void
    {
        $originalServer = $_SERVER;

        try {
            $_SERVER['SERVER_PROTOCOL'] = 'https';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertTrue($request->isSecure());

            $_SERVER['SERVER_PROTOCOL'] = 'http';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertFalse($request->isSecure());
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testUserAgent(): void
    {
        $originalServer = $_SERVER;

        try {
            $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Test Browser';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('Mozilla/5.0 Test Browser', $request->userAgent());

            unset($_SERVER['HTTP_USER_AGENT']);
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('', $request->userAgent());
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testGetBasePath(): void
    {
        $originalServer = $_SERVER;

        try {
            $_SERVER['REQUEST_URI'] = '/admin/users?page=1';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('/admin/users', $request->getBasePath());

            $_SERVER['REQUEST_URI'] = '/index.php';
            $request = $this->createRequest(['headers' => $_SERVER]);
            $this->assertEquals('/index.php', $request->getBasePath());
        } finally {
            $_SERVER = $originalServer;
        }
    }

    public function testInput(): void
    {
        $request = $this->createRequest([
            'request' => ['field1' => 'value1', 'field2' => 'value2']
        ]);

        $this->assertEquals('value1', $request->input('field1'));
        $this->assertEquals('value2', $request->input('field2'));
        $this->assertNull($request->input('nonexistent'));
        $this->assertEquals('default', $request->input('nonexistent', 'default'));
    }

    public function testQuery(): void
    {
        $request = $this->createRequest([
            'query' => ['param1' => 'value1', 'param2' => 'value2']
        ]);

        $this->assertEquals('value1', $request->query('param1'));
        $this->assertEquals('value2', $request->query('param2'));
        $this->assertNull($request->query('nonexistent'));
        $this->assertEquals('default', $request->query('nonexistent', 'default'));
    }

    public function testParseRequestData(): void
    {
        $originalServer = $_SERVER;
        $originalPost = $_POST;

        try {
            // Test GET request (debe devolver $_POST vacío)
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_POST = [];
            $result = Request::parseRequestData();
            $this->assertEquals([], $result);

            // Test POST request
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = ['field' => 'value'];
            $result = Request::parseRequestData();
            $this->assertEquals(['field' => 'value'], $result);

            // Test sin REQUEST_METHOD
            unset($_SERVER['REQUEST_METHOD']);
            $result = Request::parseRequestData();
            $this->assertEquals(['field' => 'value'], $result);
        } finally {
            $_SERVER = $originalServer;
            $_POST = $originalPost;
        }
    }

    public function testConstants(): void
    {
        $this->assertEquals('GET', Request::METHOD_GET);
        $this->assertEquals('POST', Request::METHOD_POST);
        $this->assertEquals('PUT', Request::METHOD_PUT);
        $this->assertEquals('PATCH', Request::METHOD_PATCH);
    }
}
