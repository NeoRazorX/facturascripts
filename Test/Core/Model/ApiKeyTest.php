<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\ApiAccess;
use FacturaScripts\Core\Model\ApiKey;
use FacturaScripts\Core\Tools;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class ApiKeyTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate(): void
    {
        // creamos una api key
        $key = new ApiKey();
        $key->description = 'test';
        $this->assertTrue($key->save());

        // le damos permiso para un recurso
        $access = new ApiAccess();
        $access->idapikey = $key->id;
        $access->resource = 'divisas';
        $this->assertTrue($access->save());

        // comprobamos que solamente tiene acceso a un recurso
        $accesses = $key->getAccesses();
        $this->assertCount(1, $accesses);
        $this->assertEquals('divisas', $accesses[0]->resource);

        // eliminamos el api key
        $this->assertTrue($key->delete());

        // comprobamos que el acceso al recurso ya no existe
        $this->assertFalse($access->exists());
    }

    public function testAddResource(): void
    {
        // creamos una api key
        $key = new ApiKey();
        $key->description = 'test';
        $this->assertTrue($key->save());

        // añadimos un recursos en una sola llamada
        $this->assertTrue($key->addAccess('divisas', true));

        // obtenemos el recurso
        $access = $key->getAccess('divisas');
        $this->assertEquals('divisas', $access->resource);
        $this->assertTrue($access->exists());

        // eliminamos
        $this->assertTrue($key->delete());
    }

    public function testEscapeHtml(): void
    {
        $html = '<test>';
        $escaped = Tools::noHtml($html);

        // creamos una api key
        $key = new ApiKey();
        $key->apikey = $html;
        $key->description = $html;
        $this->assertTrue($key->save());

        // comprobamos que se ha escapado el html
        $this->assertEquals($escaped, $key->apikey);
        $this->assertEquals($escaped, $key->description);

        // eliminamos
        $this->assertTrue($key->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
