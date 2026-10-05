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

namespace FacturaScripts\Test\Core;

use FacturaScripts\Core\AppKey;
use FacturaScripts\Core\CrashReport;
use FacturaScripts\Core\Tools;
use PHPUnit\Framework\TestCase;

final class AppKeyTest extends TestCase
{
    public function testCrashReportToken(): void
    {
        $token = CrashReport::newToken();
        $this->assertTrue(CrashReport::validateToken($token), 'crash-report-token-not-valid');
        $this->assertFalse(CrashReport::validateToken(''), 'empty-crash-report-token-valid');
        $this->assertFalse(CrashReport::validateToken(strrev($token)), 'bad-crash-report-token-valid');

        // el token antiguo solo dependía del nombre y el usuario de la base de datos
        $oldToken = md5(Tools::config('db_name') . Tools::config('db_user') . date('Y-m-d H'));
        $this->assertFalse(CrashReport::validateToken($oldToken), 'old-crash-report-token-valid');

        // el token de otro tipo no sirve como token de CrashReport
        $otherToken = AppKey::sign('other', date('Y-m-d H'));
        $this->assertFalse(CrashReport::validateToken($otherToken), 'other-purpose-token-valid');
    }

    public function testGenerate(): void
    {
        $key1 = AppKey::generate();
        $key2 = AppKey::generate();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $key1, 'bad-key-format');
        $this->assertNotEquals($key1, $key2, 'repeated-key');
    }

    public function testGet(): void
    {
        $key = AppKey::get();
        $this->assertGreaterThanOrEqual(AppKey::KEY_BYTES, strlen($key), 'key-too-short');
        $this->assertEquals($key, AppKey::get(), 'key-not-stable');

        if (AppKey::isDerived()) {
            $this->assertNotEquals(Tools::config('db_pass'), $key, 'derived-key-is-db-pass');
        } else {
            $this->assertEquals(Tools::config('app_key'), $key, 'key-not-from-config');
        }
    }

    public function testSignAndVerify(): void
    {
        $signature = AppKey::sign('test', 'data');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $signature, 'bad-signature-format');
        $this->assertEquals($signature, AppKey::sign('test', 'data'), 'signature-not-stable');
        $this->assertTrue(AppKey::verify('test', 'data', $signature), 'signature-not-valid');

        // cambiar los datos, el propósito o la firma invalida la comprobación
        $this->assertFalse(AppKey::verify('test', 'other-data', $signature), 'signature-valid-with-other-data');
        $this->assertFalse(AppKey::verify('other', 'data', $signature), 'signature-valid-with-other-purpose');
        $this->assertFalse(AppKey::verify('test', 'data', ''), 'empty-signature-valid');
        $this->assertFalse(AppKey::verify('test', 'data', substr($signature, 0, -1)), 'short-signature-valid');
    }
}
