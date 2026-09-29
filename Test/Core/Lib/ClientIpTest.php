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

namespace FacturaScripts\Test\Core\Lib;

use FacturaScripts\Core\Lib\ClientIp;
use PHPUnit\Framework\TestCase;

final class ClientIpTest extends TestCase
{
    public function testDirectClientCanNotSpoofHeaders(): void
    {
        $server = [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.2',
            'REMOTE_ADDR' => '198.51.100.7',
        ];
        $this->assertSame('198.51.100.7', ClientIp::resolve($server));

        // cada petición con una cabecera distinta sigue dando la misma IP
        for ($i = 1; $i <= 5; $i++) {
            $server['HTTP_X_FORWARDED_FOR'] = '10.0.0.' . $i;
            $this->assertSame('198.51.100.7', ClientIp::resolve($server));
        }
    }

    public function testWithoutRemoteAddrReturnsDefault(): void
    {
        $this->assertSame('::1', ClientIp::resolve([]));
        $this->assertSame('::1', ClientIp::resolve(['HTTP_X_FORWARDED_FOR' => '203.0.113.2']));
        $this->assertSame('::1', ClientIp::resolve(['REMOTE_ADDR' => 'not-an-ip']));
    }

    public function testCloudflareHeaderOnlyFromCloudflare(): void
    {
        // IPv4 e IPv6 de Cloudflare
        $this->assertSame('203.0.113.1', ClientIp::resolve([
            'HTTP_CF_CONNECTING_IP' => '203.0.113.1',
            'REMOTE_ADDR' => '172.64.0.10',
        ]));
        $this->assertSame('2001:db8::1', ClientIp::resolve([
            'HTTP_CF_CONNECTING_IP' => '2001:db8::1',
            'REMOTE_ADDR' => '2606:4700::10',
        ]));

        // un proxy local no puede pasar CF-Connecting-IP, que el cliente podría haber inventado
        $this->assertSame('127.0.0.1', ClientIp::resolve([
            'HTTP_CF_CONNECTING_IP' => '203.0.113.1',
            'REMOTE_ADDR' => '127.0.0.1',
        ]));

        // una cabecera que no es una IP se ignora
        $this->assertSame('172.64.0.10', ClientIp::resolve([
            'HTTP_CF_CONNECTING_IP' => '<script>',
            'REMOTE_ADDR' => '172.64.0.10',
        ]));
    }

    public function testForwardedForSkipsOnlyTrustedHops(): void
    {
        // proxy local: la IP del cliente es la última añadida
        $this->assertSame('203.0.113.1', ClientIp::resolve([
            'HTTP_X_FORWARDED_FOR' => '203.0.113.1',
            'REMOTE_ADDR' => '127.0.0.1',
        ]));

        // lo que añade el cliente a la izquierda no se usa
        $this->assertSame('203.0.113.1', ClientIp::resolve([
            'HTTP_X_FORWARDED_FOR' => '198.51.100.99, 203.0.113.1',
            'REMOTE_ADDR' => '10.0.0.5',
        ]));

        // Cloudflare delante de un proxy local
        $this->assertSame('203.0.113.1', ClientIp::resolve([
            'HTTP_X_FORWARDED_FOR' => 'spoofed, 203.0.113.1, 162.158.1.1',
            'REMOTE_ADDR' => '192.168.1.10',
        ]));

        // si todos los saltos son de confianza, se usa el más lejano
        $this->assertSame('10.0.0.2', ClientIp::resolve([
            'HTTP_X_FORWARDED_FOR' => '10.0.0.2, 10.0.0.3',
            'REMOTE_ADDR' => '127.0.0.1',
        ]));
    }

    public function testConfiguredTrustedProxies(): void
    {
        $server = [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.2',
            'REMOTE_ADDR' => '198.51.100.7',
        ];

        // sin configurar, el proxy público no es de confianza
        $this->assertSame('198.51.100.7', ClientIp::resolve($server));

        // como IP, como rango CIDR y como lista separada por comas
        $this->assertSame('203.0.113.1', ClientIp::resolve($server, ['198.51.100.7']));
        $this->assertSame('203.0.113.1', ClientIp::resolve($server, ['198.51.100.0/24']));
        $this->assertSame('203.0.113.1', ClientIp::resolve($server, '192.0.2.1, 198.51.100.0/24'));

        unset($server['HTTP_CF_CONNECTING_IP']);
        $this->assertSame('203.0.113.2', ClientIp::resolve($server, ['198.51.100.0/24']));

        // un rango que no incluye la IP no la convierte en proxy de confianza
        $this->assertSame('198.51.100.7', ClientIp::resolve($server, ['198.51.101.0/24', 'invalid']));
    }
}
