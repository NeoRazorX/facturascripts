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

namespace FacturaScripts\Test\Core\Template;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Template\ExtensionsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests para el aviso de conflicto de nombres al extender clases (tarea #3763).
 */
final class ExtensionsTraitTest extends TestCase
{
    protected function setUp(): void
    {
        ExtensionsTraitTestSubject::clearExtensions();
        MiniLog::clear();
    }

    public function testSingleExtensionRunsWithoutWarning(): void
    {
        ExtensionsTraitTestSubject::addExtension(new class {
            public function greet()
            {
                return function () {
                    return 'hello';
                };
            }
        });

        $subject = new ExtensionsTraitTestSubject();
        $this->assertEquals('hello', $subject->greet());
        $this->assertCount(0, MiniLog::read('', ['warning']), 'no debería avisar con una sola extensión');
    }

    public function testDuplicatedMethodNameLogsWarning(): void
    {
        // dos plugins registran el mismo método; el de mayor prioridad va primero
        ExtensionsTraitTestSubject::addExtension(new class {
            public function greet()
            {
                return function () {
                    return 'first';
                };
            }
        }, 200);
        ExtensionsTraitTestSubject::addExtension(new class {
            public function greet()
            {
                return function () {
                    return 'second';
                };
            }
        }, 100);

        $subject = new ExtensionsTraitTestSubject();

        // __call solo ejecuta la primera (mayor prioridad)
        $this->assertEquals('first', $subject->greet());

        // y se avisa al desarrollador del conflicto de nombres
        $this->assertNotEmpty(MiniLog::read('', ['warning']), 'debería avisar del conflicto de nombres');
    }

    public function testWarningIsLoggedOnlyOnce(): void
    {
        ExtensionsTraitTestSubject::addExtension(new class {
            public function greet()
            {
                return function () {
                    return 'first';
                };
            }
        }, 200);
        ExtensionsTraitTestSubject::addExtension(new class {
            public function greet()
            {
                return function () {
                    return 'second';
                };
            }
        }, 100);

        $subject = new ExtensionsTraitTestSubject();
        $subject->greet();
        $subject->greet();
        $subject->greet();

        $this->assertCount(1, MiniLog::read('', ['warning']), 'el aviso no debe repetirse en cada llamada');
    }
}

/**
 * Clase de apoyo que usa el trait bajo prueba.
 */
class ExtensionsTraitTestSubject
{
    use ExtensionsTrait;
}
