<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\Model\PageFilter;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class PageFilterTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate(): void
    {
        // creamos un usuario
        $user = $this->getRandomUser();
        $this->assertTrue($user->save());

        // creamos un filtro de página
        $pageFilter = new PageFilter();
        $pageFilter->name = 'TestController';
        $pageFilter->nick = $user->nick;
        $pageFilter->description = 'Test Filter';
        $pageFilter->filters = ['field1' => 'value1', 'field2' => 'value2'];
        $this->assertTrue($pageFilter->save(), 'Error saving PageFilter');

        // comprobamos que existe en la base de datos
        $this->assertTrue($pageFilter->exists());

        // comprobamos que se ha asignado un id
        $this->assertNotNull($pageFilter->id);

        // eliminamos
        $this->assertTrue($pageFilter->delete(), 'Error deleting PageFilter');
        $this->assertTrue($user->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
