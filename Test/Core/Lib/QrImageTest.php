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

use FacturaScripts\Core\Lib\QrImage;
use FacturaScripts\Core\Lib\TwoFactorManager;
use PHPUnit\Framework\TestCase;

final class QrImageTest extends TestCase
{
    const URL = 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR?nif=89890001K&numserie=12345678-G33&fecha=01-09-2024&importe=241.4';

    public function testPng(): void
    {
        $image = QrImage::png(self::URL);
        $this->assertStringStartsWith('data:image/png;base64,', $image, 'not-png-data-uri');

        // el ancho es (módulos + 2 márgenes de 4 módulos) por 5 píxeles por módulo
        $size = getimagesizefromstring(base64_decode(substr($image, strlen('data:image/png;base64,'))));
        $this->assertNotFalse($size, 'not-an-image');
        $this->assertSame((QrImage::size(self::URL) + 8) * 5, $size[0], 'bad-image-width');
        $this->assertSame($size[0], $size[1], 'image-not-square');
    }

    public function testPngScaleAndQuietZone(): void
    {
        $modules = QrImage::size(self::URL, QrImage::ECC_H);

        // sin margen, el ancho son solo los módulos
        $image = QrImage::png(self::URL, QrImage::ECC_H, 3, 0);
        $size = getimagesizefromstring(base64_decode(substr($image, strlen('data:image/png;base64,'))));
        $this->assertSame($modules * 3, $size[0], 'bad-image-width-without-quiet-zone');

        $image = QrImage::png(self::URL, QrImage::ECC_H, 2, 2);
        $size = getimagesizefromstring(base64_decode(substr($image, strlen('data:image/png;base64,'))));
        $this->assertSame(($modules + 4) * 2, $size[0], 'bad-image-width-with-quiet-zone');
    }

    public function testSize(): void
    {
        // 21 módulos en la versión 1 y 4 más por versión; más corrección de errores, más módulos
        $low = QrImage::size(self::URL, QrImage::ECC_L);
        $medium = QrImage::size(self::URL, QrImage::ECC_M);
        $high = QrImage::size(self::URL, QrImage::ECC_H);
        $this->assertSame(0, ($medium - 21) % 4, 'bad-size');
        $this->assertLessThanOrEqual($medium, $low, 'low-bigger-than-medium');
        $this->assertLessThanOrEqual($high, $medium, 'medium-bigger-than-high');
        $this->assertLessThan($high, $low, 'low-not-smaller-than-high');

        // un nivel desconocido se toma como M
        $this->assertSame($medium, QrImage::size(self::URL, 'X'), 'unknown-level-not-medium');
        $this->assertSame($medium, QrImage::size(self::URL), 'default-level-not-medium');
    }

    public function testTwoFactorImage(): void
    {
        // el QR de la autenticación en dos pasos no cambia: nivel L, escala 10 y margen de 4 módulos
        $url = 'otpauth://totp/FacturaScripts:test@example.com?secret=JBSWY3DPEHPK3PXP&issuer=FacturaScripts';
        $this->assertSame(QrImage::png($url, QrImage::ECC_L, 10), TwoFactorManager::getQRCodeImage($url), 'two-factor-image-changed');
    }
}
