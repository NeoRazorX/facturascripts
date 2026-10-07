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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Internal\PhpExtensions;
use PHPUnit\Framework\TestCase;

final class PhpExtensionsTest extends TestCase
{
    public function testMissing(): void
    {
        // el entorno de pruebas tiene todas las extensiones requeridas
        $this->assertSame([], PhpExtensions::missing(), 'required-extensions-missing');
        $this->assertSame(['fs_not_an_extension'], PhpExtensions::missing(['zip', 'fs_not_an_extension']));

        // simulamos que falta la extensión zip
        PhpExtensions::$simulateMissing = ['zip'];
        $this->assertSame(['zip'], PhpExtensions::missing());
        $this->assertSame([], PhpExtensions::missing(['fileinfo']));
    }

    public function testWarnMissing(): void
    {
        $this->assertTrue(PhpExtensions::warnMissing());
        $this->assertSame([], MiniLog::read('', ['warning']));

        PhpExtensions::$simulateMissing = ['fileinfo', 'zip'];
        $this->assertFalse(PhpExtensions::warnMissing());

        $warnings = MiniLog::read('', ['warning']);
        $this->assertCount(2, $warnings);
        foreach ($warnings as $num => $warning) {
            $this->assertSame('php-extension-not-found', $warning['original']);
            $this->assertSame(['fileinfo', 'zip'][$num], $warning['context']['%extension%']);
        }
    }

    protected function setUp(): void
    {
        MiniLog::clear();
    }

    protected function tearDown(): void
    {
        PhpExtensions::$simulateMissing = [];
        MiniLog::clear();
    }
}
