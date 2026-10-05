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

use FacturaScripts\Core\Base\Controller as LegacyController;
use FacturaScripts\Core\Controller\Dashboard;
use FacturaScripts\Core\Template\Controller;
use PHPUnit\Framework\TestCase;

final class ControllerTest extends TestCase
{
    public function testExtensionCanAddDynamicPropertyToTemplateController(): void
    {
        $controller = new class ('TestController') extends Controller {
            public function run(): void
            {
            }
        };

        $this->assertNoDeprecationWhenAddingProperty($controller);
    }

    public function testExtensionCanAddDynamicPropertyToLegacyController(): void
    {
        $this->assertTrue(is_subclass_of(Dashboard::class, LegacyController::class));
        $controller = new Dashboard('Dashboard', '/Dashboard');

        $this->assertNoDeprecationWhenAddingProperty($controller);
    }

    /**
     * Simula una extensión de plugin: un closure vinculado al controlador
     * que le añade una propiedad no declarada.
     */
    private function assertNoDeprecationWhenAddingProperty(object $controller): void
    {
        $deprecations = [];
        set_error_handler(function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;
            return true;
        }, E_DEPRECATED | E_USER_DEPRECATED);

        try {
            $extension = function () {
                $this->profileStats = ['level' => 3];
            };
            $extension->bindTo($controller, get_class($controller))();
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
        $this->assertSame(['level' => 3], $controller->profileStats);
    }
}
