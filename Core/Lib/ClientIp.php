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

namespace FacturaScripts\Core\Lib;

/**
 * Obtiene la IP del cliente sin fiarse de las cabeceras que puede enviar cualquiera.
 *
 * Por defecto se usa REMOTE_ADDR. Las cabeceras CF-Connecting-IP y X-Forwarded-For solo se tienen
 * en cuenta cuando la petición llega desde un proxy de confianza: una red privada o local, Cloudflare
 * o una IP o rango indicado en la constante FS_TRUSTED_PROXIES de config.php.
 */
final class ClientIp
{
    /** Rangos publicados en https://www.cloudflare.com/ips/ */
    const CLOUDFLARE_RANGES = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    const DEFAULT_IP = '::1';

    const PRIVATE_RANGES = [
        '10.0.0.0/8',
        '127.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '::1/128',
        'fc00::/7',
    ];

    /**
     * @param array $server Variables del servidor, normalmente $_SERVER.
     * @param array|string $trustedProxies IPs o rangos CIDR de proxies de confianza adicionales.
     */
    public static function resolve(array $server, $trustedProxies = []): string
    {
        $remoteAddr = self::normalize($server['REMOTE_ADDR'] ?? '');
        if (null === $remoteAddr) {
            return self::DEFAULT_IP;
        }

        $configured = self::parseRanges($trustedProxies);
        if (false === self::isTrusted($remoteAddr, $configured)) {
            return $remoteAddr;
        }

        // Cloudflare añade CF-Connecting-IP, pero solo la aceptamos si nos llega de Cloudflare
        // o de un proxy configurado expresamente
        $cfIp = self::normalize($server['HTTP_CF_CONNECTING_IP'] ?? '');
        if ($cfIp !== null && self::inRanges($remoteAddr, array_merge(self::CLOUDFLARE_RANGES, $configured))) {
            return $cfIp;
        }

        // recorremos X-Forwarded-For de derecha a izquierda: cada proxy añade al final la IP de la que
        // le llega la petición, así que la primera que no sea de confianza es la del cliente
        $client = $remoteAddr;
        $hops = array_reverse(explode(',', (string)($server['HTTP_X_FORWARDED_FOR'] ?? '')));
        foreach ($hops as $hop) {
            $ip = self::normalize($hop);
            if (null === $ip) {
                continue;
            }

            $client = $ip;
            if (false === self::isTrusted($ip, $configured)) {
                break;
            }
        }

        return $client;
    }

    private static function inRange(string $ip, string $range): bool
    {
        $parts = explode('/', $range, 2);
        $subnet = @inet_pton($parts[0]);
        $address = @inet_pton($ip);
        if (false === $subnet || false === $address || strlen($subnet) !== strlen($address)) {
            return false;
        }

        $maxBits = strlen($address) * 8;
        $bits = isset($parts[1]) && ctype_digit($parts[1]) ? (int)$parts[1] : $maxBits;
        if ($bits > $maxBits) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        if (substr($address, 0, $bytes) !== substr($subnet, 0, $bytes)) {
            return false;
        }

        $remaining = $bits % 8;
        if ($remaining === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remaining)) & 0xFF;
        return (ord($address[$bytes]) & $mask) === (ord($subnet[$bytes]) & $mask);
    }

    private static function inRanges(string $ip, array $ranges): bool
    {
        foreach ($ranges as $range) {
            if (self::inRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    private static function isTrusted(string $ip, array $configured): bool
    {
        return self::inRanges($ip, array_merge(self::PRIVATE_RANGES, self::CLOUDFLARE_RANGES, $configured));
    }

    private static function normalize($value): ?string
    {
        $ip = trim((string)$value);
        return false === filter_var($ip, FILTER_VALIDATE_IP) ? null : $ip;
    }

    /**
     * @param array|string $value
     */
    private static function parseRanges($value): array
    {
        $items = is_array($value) ? $value : explode(',', (string)$value);

        $ranges = [];
        foreach ($items as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $ranges[] = $item;
            }
        }

        return $ranges;
    }
}
