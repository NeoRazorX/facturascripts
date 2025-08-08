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

use FacturaScripts\Core\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
    public function testBasicResponse(): void
    {
        $response = new Response();
        $this->assertEquals(200, $response->getHttpCode());
        $this->assertEquals('', $response->getContent());
    }

    public function testSetContent(): void
    {
        $response = new Response();
        $response->setContent('Hello World');
        $this->assertEquals('Hello World', $response->getContent());
    }

    public function testSetHttpCode(): void
    {
        $response = new Response();
        $response->setHttpCode(404);
        $this->assertEquals(404, $response->getHttpCode());
    }

    public function testCookieWithSecurityFlags(): void
    {
        $response = new Response();
        
        // Test que los valores por defecto de seguridad se aplican
        $reflection = new \ReflectionClass($response);
        $cookiesProperty = $reflection->getProperty('cookies');
        $cookiesProperty->setAccessible(true);
        
        $response->cookie('test', 'value');
        $cookies = $cookiesProperty->getValue($response);
        
        $this->assertTrue($cookies['test']['httponly']);
        $this->assertEquals('Lax', $cookies['test']['samesite']);
    }

    public function testRedirectValidation(): void
    {
        $response = new Response();
        
        // Test redirección relativa (segura)
        $reflection = new \ReflectionClass($response);
        $method = $reflection->getMethod('validateRedirectUrl');
        $method->setAccessible(true);
        
        // URL relativa normal
        $this->assertEquals('/dashboard', $method->invoke($response, '/dashboard'));
        
        // URL relativa sin /
        $this->assertEquals('/admin', $method->invoke($response, 'admin'));
        
        // URL con caracteres de control (debe limpiarlos)
        $this->assertEquals('/clean', $method->invoke($response, "/clean\x00\x01"));
        
        // URL externa no permitida (debe redirigir a /)
        $this->assertEquals('/', $method->invoke($response, 'http://evil.com'));
        
        // Esquema no permitido (debe redirigir a /)
        $this->assertEquals('/', $method->invoke($response, 'javascript:alert(1)'));
        
        // URL vacía
        $this->assertEquals('/', $method->invoke($response, ''));
    }

    public function testPdfFileNameSanitization(): void
    {
        $response = new Response();
        $response->disableSend(); // Deshabilitar envío para tests
        
        $reflection = new \ReflectionClass($response);
        $headersProperty = $reflection->getProperty('headers');
        $headersProperty->setAccessible(true);
        
        $response->pdf('PDF content', 'file<script>.pdf');
        
        $headers = $headersProperty->getValue($response);
        $headerData = $headers->all();
        
        // Verificar que el nombre del archivo fue sanitizado
        $this->assertStringContainsString('filename="filescript.pdf"', $headerData['Content-Disposition']);
    }

    public function testFilePathValidation(): void
    {
        $response = new Response();
        $response->disableSend(); // Deshabilitar envío para tests
        
        // Test archivo que no existe
        $tempFile = sys_get_temp_dir() . '/test_file_' . uniqid() . '.txt';
        
        $reflection = new \ReflectionClass($response);
        $httpCodeProperty = $reflection->getProperty('http_code');
        $httpCodeProperty->setAccessible(true);
        
        $response->file($tempFile);
        
        // Debe establecer código 404 para archivo no encontrado
        $this->assertEquals(Response::HTTP_NOT_FOUND, $httpCodeProperty->getValue($response));
    }

    public function testFileNameSanitization(): void
    {
        $response = new Response();
        $response->disableSend(); // Deshabilitar envío para tests
        
        // Crear archivo temporal para el test
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, 'test content');
        
        $reflection = new \ReflectionClass($response);
        $headersProperty = $reflection->getProperty('headers');
        $headersProperty->setAccessible(true);
        
        $response->file($tempFile, 'file<>"|*.txt');
        
        $headers = $headersProperty->getValue($response);
        $headerData = $headers->all();
        
        // Verificar que el nombre fue sanitizado
        $this->assertStringContainsString('filename="file.txt"', $headerData['Content-Disposition']);
        
        // Limpiar
        unlink($tempFile);
    }

    public function testHttpConstants(): void
    {
        $this->assertEquals(200, Response::HTTP_OK);
        $this->assertEquals(400, Response::HTTP_BAD_REQUEST);
        $this->assertEquals(401, Response::HTTP_UNAUTHORIZED);
        $this->assertEquals(403, Response::HTTP_FORBIDDEN);
        $this->assertEquals(404, Response::HTTP_NOT_FOUND);
        $this->assertEquals(405, Response::HTTP_METHOD_NOT_ALLOWED);
        $this->assertEquals(500, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
