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
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Controller\EditProvincia;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\Provincia;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Prueba la página de bloques de una provincia: su formulario, el mapa y los listados en pestañas.
 */
final class EditProvinciaTest extends TestCase
{
    use LogErrorsTrait;

    /** @var Ciudad[] */
    private $cities = [];

    /** @var Provincia[] */
    private $provinces = [];

    public function testBlocksShowOnlyThePlacesOfTheProvince(): void
    {
        $province = $this->provinces[0];
        $controller = $this->handle(['code' => $province->idprovincia]);

        $this->assertTrue($controller->hasData);
        $this->assertSame(['map', 'ListCiudad', 'ListCodigoPostal'], array_map(
            fn($block) => $block->name(),
            $controller->sections()
        ));

        // la primera pestaña se carga con la ciudad de esta provincia; las demás quedan aplazadas
        $cursor = $controller->viewBlock('ListCiudad')->view()->cursor;
        $this->assertCount(1, $cursor);
        $this->assertSame($this->cities[0]->idciudad, $cursor[0]->idciudad);
        $this->assertTrue($controller->block('ListCodigoPostal')->isDeferred());
        $this->assertTrue($controller->block('map')->isDeferred());
    }

    public function testBreadcrumbGoesFromTheListToTheProvince(): void
    {
        $province = $this->provinces[0];
        $controller = $this->handle(['code' => $province->idprovincia]);
        $crumbs = $controller->header()->breadcrumbFor($controller->record());

        // listado, país y la propia provincia, que no lleva enlace
        $this->assertSame(
            [Tools::trans('provinces'), $province->getCountry()->nombre, $province->provincia],
            array_column($crumbs, 'title')
        );
        $this->assertSame([$province->url('list'), 'EditPais?code=ESP', ''], array_column($crumbs, 'url'));
    }

    public function testCountsAndMapOfTheProvince(): void
    {
        $province = $this->provinces[0];
        $names = 'ListCiudad,ListCodigoPostal,ListPuntoInteresCiudad,map';
        $controller = $this->handle(['code' => $province->idprovincia, 'counts' => $names]);
        // los puntos de interés ya no son un bloque de la página, así que no se cuentan
        $this->assertSame(['ListCiudad' => 1, 'ListCodigoPostal' => 0], $controller->jsonData);

        // el mapa solo pinta las ciudades con coordenadas de esta provincia
        $controller = $this->handle(['code' => $province->idprovincia, 'fragment' => 'map']);
        $this->assertSame([], $controller->block('map')->config()['markers']);

        foreach ($this->cities as $city) {
            $city->latitude = 41.6;
            $city->longitude = -0.9;
            $this->assertTrue($city->save());
        }
        $controller = $this->handle(['code' => $province->idprovincia, 'fragment' => 'map']);
        $markers = $controller->block('map')->config()['markers'];
        $this->assertSame([$this->cities[0]->ciudad], array_column($markers, 'title'));
        $this->assertSame($this->cities[0]->url(), $markers[0]['url']);
    }

    public function testNewProvinceShowsOnlyTheForm(): void
    {
        $controller = $this->handle([]);

        $this->assertFalse($controller->hasData);
        $this->assertSame([], $controller->sections());
    }

    protected function setUp(): void
    {
        // dos provincias con una ciudad cada una, para comprobar que no se mezclan
        foreach (['A', 'B'] as $suffix) {
            $province = new Provincia();
            $province->codpais = 'ESP';
            $province->provincia = 'Test province ' . mt_rand(1000, 9999) . $suffix;
            $this->assertTrue($province->save());
            $this->provinces[] = $province;

            $city = new Ciudad();
            $city->idprovincia = $province->idprovincia;
            $city->ciudad = 'Test city ' . $suffix;
            $this->assertTrue($city->save());
            $this->cities[] = $city;
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->cities as $city) {
            $city->delete();
        }
        foreach ($this->provinces as $province) {
            $province->delete();
        }

        unset($_SERVER['REQUEST_METHOD']);
        $this->logErrors();
    }

    private function handle(array $query): TestableEditProvincia
    {
        $controller = new TestableEditProvincia('EditProvincia', '/EditProvincia');

        $user = new User();
        $user->nick = 'test-edit-provincia';
        $controller->user = $user;

        $controller->permissions = new ControllerPermissions();
        $controller->permissions->set(true, 1, false, true);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $controller->testRequest = new Request(['query' => $query]);
        $controller->handle();

        return $controller;
    }
}

final class TestableEditProvincia extends EditProvincia
{
    /** @var array|null */
    public $jsonData;

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

    protected function json(array $data): bool
    {
        // la respuesta real envía cabeceras: en los tests solo anotamos los datos
        $this->jsonData = $data;
        $this->finished = true;

        return false;
    }
}
