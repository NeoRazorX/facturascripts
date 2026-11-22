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

use FacturaScripts\Core\Lib\ControllerPermissions;
use FacturaScripts\Core\Request;
use FacturaScripts\Dinamic\Controller\EditPuntoInteresCiudad;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\Provincia;
use FacturaScripts\Dinamic\Model\PuntoInteresCiudad;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Prueba la página de bloques de un punto de interés: su formulario y el mapa con el punto.
 */
final class EditPuntoInteresCiudadTest extends TestCase
{
    use LogErrorsTrait;

    /** @var Ciudad */
    private $city;

    /** @var PuntoInteresCiudad */
    private $point;

    /** @var Provincia */
    private $province;

    public function testMapShowsOnlyThePoint(): void
    {
        $controller = $this->handle(['code' => $this->point->id]);

        $this->assertTrue($controller->hasData);
        $this->assertSame(['map'], array_map(fn($block) => $block->name(), $controller->sections()));
        $this->assertTrue($controller->block('map')->isDeferred());

        // sin coordenadas no hay nada que pintar
        $controller = $this->handle(['code' => $this->point->id, 'fragment' => 'map']);
        $config = $controller->block('map')->config();
        $this->assertSame([], $config['markers']);
        $this->assertNull($config['center']);

        // con coordenadas, el punto es el único marcador y el centro del mapa
        $this->point->latitude = 41.6;
        $this->point->longitude = -0.9;
        $this->assertTrue($this->point->save());

        $controller = $this->handle(['code' => $this->point->id, 'fragment' => 'map']);
        $config = $controller->block('map')->config();
        $this->assertSame([$this->point->name], array_column($config['markers'], 'title'));
        $this->assertEquals(41.6, $config['markers'][0]['lat']);
        $this->assertEquals([41.6, -0.9, 16], $config['center']);
    }

    public function testNewPointShowsOnlyTheForm(): void
    {
        $controller = $this->handle(['idciudad' => $this->city->idciudad]);

        $this->assertFalse($controller->hasData);
        $this->assertSame([], $controller->sections());
        $this->assertEquals($this->city->idciudad, $controller->record()->idciudad);
    }

    protected function setUp(): void
    {
        $this->province = new Provincia();
        $this->province->codpais = 'ESP';
        $this->province->provincia = 'Test province ' . mt_rand(1000, 9999);
        $this->assertTrue($this->province->save());

        $this->city = new Ciudad();
        $this->city->idprovincia = $this->province->idprovincia;
        $this->city->ciudad = 'Test city';
        $this->assertTrue($this->city->save());

        $this->point = new PuntoInteresCiudad();
        $this->point->idciudad = $this->city->idciudad;
        $this->point->name = 'Test point';
        $this->assertTrue($this->point->save());
    }

    protected function tearDown(): void
    {
        $this->point->delete();
        $this->city->delete();
        $this->province->delete();

        unset($_SERVER['REQUEST_METHOD']);
        $this->logErrors();
    }

    private function handle(array $query): TestableEditPuntoInteresCiudad
    {
        $controller = new TestableEditPuntoInteresCiudad('EditPuntoInteresCiudad', '/EditPuntoInteresCiudad');

        $user = new User();
        $user->nick = 'test-edit-poi';
        $controller->user = $user;

        $controller->permissions = new ControllerPermissions();
        $controller->permissions->set(true, 1, false, true);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $controller->testRequest = new Request(['query' => $query]);
        $controller->handle();

        return $controller;
    }
}

final class TestableEditPuntoInteresCiudad extends EditPuntoInteresCiudad
{
    /** @var Request */
    public $testRequest;

    public function handle(): void
    {
        $this->createBlocks();
        $this->process();
    }

    public function request(): Request
    {
        return $this->testRequest;
    }
}
