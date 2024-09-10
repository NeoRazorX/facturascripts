<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Pais;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class PaisTest extends TestCase
{
    use LogErrorsTrait;

    public function testDataInstalled()
    {
        $pais = new Pais();
        $this->assertNotEmpty($pais->all(), 'pais-data-not-installed-from-csv');
    }

    public function testCreate()
    {
        $pais = new Pais();
        $pais->codpais = 'YOL';
        $pais->nombre = 'Yolandia';
        $this->assertTrue($pais->save(), 'pais-can-not-create');
        $this->assertTrue($pais->exists(), 'pais-do-not-persists');
        $this->assertTrue($pais->delete(), 'pais-can-not-delete');
    }

    public function testCreateNoCode()
    {
        $pais = new Pais();
        $pais->nombre = 'Wolandia';
        $this->assertFalse($pais->save(), 'pais-can-not-create');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
