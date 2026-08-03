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

namespace FacturaScripts\Test\Core\Controller;

use FacturaScripts\Core\Controller\Dashboard;
use FacturaScripts\Dinamic\Model\User;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class DashboardTest extends TestCase
{
    public function testNonAdminDoesNotSeeBackupWarning(): void
    {
        $controller = new Dashboard('Dashboard', '/Dashboard');
        $controller->user = new User();
        $controller->user->admin = false;

        $this->assertFalse($controller->showBackupWarning());
    }

    public function testStatsComparison(): void
    {
        $controller = new Dashboard('Dashboard', '/Dashboard');
        $method = new ReflectionMethod(Dashboard::class, 'setStats');

        $method->invoke($controller, 'sales', 150.0, 100.0);
        $this->assertSame(['this-month' => 150.0, 'last-month' => 100.0], $controller->stats['sales']);
        $this->assertSame(50.0, $controller->statChanges['sales']);

        $method->invoke($controller, 'purchases', 25.0, 0.0);
        $this->assertNull($controller->statChanges['purchases']);
    }
}
