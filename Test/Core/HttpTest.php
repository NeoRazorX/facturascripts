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

use FacturaScripts\Core\Http;
use PHPUnit\Framework\TestCase;

final class HttpTest extends TestCase
{
    public function testGet(): void
    {
        $url = 'https://facturascripts.com/PluginInfoList';
        $request = Http::get($url)->setTimeout(10);

        // saltamos si el estado es 0
        if ($request->status() === 0) {
            $this->markTestSkipped('No se puede conectar con el servidor.');
        }

        $this->assertTrue($request->ok());
        $this->assertFalse($request->failed());
        $this->assertEquals(200, $request->status());
        $this->assertEmpty($request->errorMessage());
        $this->assertNotEmpty($request->body());
        $this->assertEquals('application/json', $request->header('Content-Type'));
        $this->assertJson($request->body());
        $this->assertIsArray($request->json());
    }

    public function testGetFailed(): void
    {
        $url = 'https://facturascripts.com/PluginInfoList404';
        $request = Http::get($url)->setTimeout(10);

        // saltamos si el estado es 0
        if ($request->status() === 0) {
            $this->markTestSkipped('No se puede conectar con el servidor.');
        }

        $this->assertFalse($request->ok());
        $this->assertTrue($request->failed());
        $this->assertEquals(404, $request->status());
    }

    public function testSaveAs(): void
    {
        $url = 'https://facturascripts.com/PluginInfoList';
        $request = Http::get($url)->setTimeout(10);

        // saltamos si el estado es 0
        if ($request->status() === 0) {
            $this->markTestSkipped('No se puede conectar con el servidor.');
        }

        $filePath = FS_FOLDER . '/MyFiles/test-http.json';
        $this->assertTrue($request->saveAs($filePath));
        $this->assertFileExists($filePath);
        $this->assertJson(file_get_contents($filePath));

        // eliminamos
        unlink($filePath);
    }
}
