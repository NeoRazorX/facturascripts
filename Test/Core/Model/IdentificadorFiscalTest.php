<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\IdentificadorFiscal;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class IdentificadorFiscalTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
    {
        // creamos un identificador fiscal
        $identificador = new IdentificadorFiscal();
        $identificador->tipoidfiscal = 'test';
        $this->assertTrue($identificador->save(), 'cant-save-identificador-fiscal');

        // lo borramos
        $this->assertTrue($identificador->delete(), 'cant-delete-identificador-fiscal');
    }

    public function testCantCreateWithoutTipo()
    {
        $identificador = new IdentificadorFiscal();
        $this->assertFalse($identificador->save(), 'cant-save-identificador-fiscal');
    }

    public function testHtmlOnFields()
    {
        // creamos un identificador fiscal con html
        $identificador = new IdentificadorFiscal();
        $identificador->tipoidfiscal = '<test>';
        $identificador->codeid = '<test>';
        $identificador->tipoidfiscal = 'test';
        $this->assertFalse($identificador->save(), 'can-save-with-html');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
