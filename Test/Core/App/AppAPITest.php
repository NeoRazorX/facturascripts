<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\App;

use FacturaScripts\Core\App\AppAPI;
use FacturaScripts\Core\App\AppSettings;
use PHPUnit\Framework\TestCase;

/**
 * Description of AppAPITest
 *
 * @author Carlos Carlos Garcia Gomez <carlos@facturascripts.com>
 * @covers \FacturaScripts\Core\App\AppAPI
 */
final class AppAPITest extends TestCase
{

    public function testApiRun()
    {
        $app = new AppAPI();
        $this->assertTrue($app->connect(), 'db-connection-error');

        $appSettings = new AppSettings();
        $appSettings->set('default', 'enable_api', false);
        $this->assertFalse($app->run(), 'api-should-not-be-executed');

        $appSettings->set('default', 'enable_api', true);
        $this->assertTrue($app->run(), 'api-should-be-executed');
    }
}
