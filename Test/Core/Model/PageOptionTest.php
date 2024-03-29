<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\PageOption;
use PHPUnit\Framework\TestCase;

final class PageOptionTest extends TestCase
{
    public function testCreate(): void
    {
        // creamos
        $pageOption = new PageOption();
        $pageOption->name = 'test';
        $this->assertTrue($pageOption->save(), 'Error saving PageOption');

        // eliminamos
        $this->assertTrue($pageOption->delete(), 'Error deleting PageOption');
    }
}
