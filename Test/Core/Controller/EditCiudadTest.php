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

use FacturaScripts\Core\DataSrc\Paises;
use FacturaScripts\Core\Lib\ControllerPermissions;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Controller\EditCiudad;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\CodigoPostal;
use FacturaScripts\Dinamic\Model\Provincia;
use FacturaScripts\Dinamic\Model\PuntoInteresCiudad;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Prueba la página de bloques de una ciudad: su formulario, el mapa y los listados en pestañas.
 */
final class EditCiudadTest extends TestCase
{
    use LogErrorsTrait;

    /** @var Ciudad[] */
    private $cities = [];

    /** @var PuntoInteresCiudad[] */
    private $points = [];

    /** @var Provincia */
    private $province;

    /** @var CodigoPostal[] */
    private $zipCodes = [];

    public function testBlocksShowOnlyThePlacesOfTheCity(): void
    {
        $city = $this->cities[0];
        $controller = $this->handle(['code' => $city->idciudad]);

        $this->assertTrue($controller->hasData);
        $this->assertSame(['map', 'ListCodigoPostal', 'ListPuntoInteresCiudad'], array_map(
            fn($block) => $block->name(),
            $controller->sections()
        ));

        // la primera pestaña se carga con el código postal de esta ciudad; las demás quedan aplazadas
        $cursor = $controller->viewBlock('ListCodigoPostal')->view()->cursor;
        $this->assertCount(1, $cursor);
        $this->assertSame($this->zipCodes[0]->id, $cursor[0]->id);

        // el botón nuevo lleva ya rellenos la ciudad, la provincia y el país
        $newUrl = $controller->viewBlock('ListCodigoPostal')->view()->btnNewUrl();
        $this->assertStringContainsString('idciudad=' . $city->idciudad, $newUrl);
        $this->assertStringContainsString('idprovincia=' . $this->province->idprovincia, $newUrl);
        $this->assertStringContainsString('codpais=ESP', $newUrl);
        $this->assertTrue($controller->block('ListPuntoInteresCiudad')->isDeferred());
        $this->assertTrue($controller->block('map')->isDeferred());
    }

    public function testBreadcrumbGoesFromTheListToTheCity(): void
    {
        $city = $this->cities[0];
        $controller = $this->handle(['code' => $city->idciudad]);
        $crumbs = $controller->header()->breadcrumbFor($controller->record());

        // listado, país, provincia y la propia ciudad, que no lleva enlace
        $this->assertSame(
            [Tools::trans('cities'), Paises::get('ESP')->nombre, $this->province->provincia, $city->ciudad],
            array_column($crumbs, 'title')
        );
        $this->assertSame(
            [$city->url('list'), 'EditPais?code=ESP', $this->province->url(), ''],
            array_column($crumbs, 'url')
        );

        // una ciudad nueva todavía no cuelga de nada
        $controller = $this->handle([]);
        $crumbs = $controller->header()->breadcrumbFor($controller->record());
        $this->assertSame([Tools::trans('cities'), Tools::trans('new')], array_column($crumbs, 'title'));
    }

    public function testCountsAndMapOfTheCity(): void
    {
        $city = $this->cities[0];
        $names = 'ListCodigoPostal,ListPuntoInteresCiudad,ListCiudad,map';
        $controller = $this->handle(['code' => $city->idciudad, 'counts' => $names]);
        $this->assertSame(['ListCodigoPostal' => 1, 'ListPuntoInteresCiudad' => 1], $controller->jsonData);

        // el mapa solo pinta los puntos de interés con coordenadas de esta ciudad
        $controller = $this->handle(['code' => $city->idciudad, 'fragment' => 'map']);
        $this->assertSame([], $controller->block('map')->config()['markers']);

        foreach ($this->points as $point) {
            $point->latitude = 41.6;
            $point->longitude = -0.9;
            $this->assertTrue($point->save());
        }
        $controller = $this->handle(['code' => $city->idciudad, 'fragment' => 'map']);
        $markers = $controller->block('map')->config()['markers'];
        $this->assertSame([$this->points[0]->name], array_column($markers, 'title'));
        $this->assertSame($this->points[0]->url(), $markers[0]['url']);
    }

    public function testNewCityShowsOnlyTheForm(): void
    {
        $controller = $this->handle([]);

        $this->assertFalse($controller->hasData);
        $this->assertSame([], $controller->sections());
    }

    protected function setUp(): void
    {
        $this->province = new Provincia();
        $this->province->codpais = 'ESP';
        $this->province->provincia = 'Test province ' . mt_rand(1000, 9999);
        $this->assertTrue($this->province->save());

        // dos ciudades con un código postal y un punto de interés cada una, para comprobar que no se mezclan
        foreach (['A', 'B'] as $suffix) {
            $city = new Ciudad();
            $city->idprovincia = $this->province->idprovincia;
            $city->ciudad = 'Test city ' . $suffix;
            $this->assertTrue($city->save());
            $this->cities[] = $city;

            $zipCode = new CodigoPostal();
            $zipCode->codpais = 'ESP';
            $zipCode->idprovincia = $this->province->idprovincia;
            $zipCode->idciudad = $city->idciudad;
            $zipCode->number = (string)mt_rand(10000, 99999);
            $this->assertTrue($zipCode->save());
            $this->zipCodes[] = $zipCode;

            $point = new PuntoInteresCiudad();
            $point->idciudad = $city->idciudad;
            $point->name = 'Test point ' . $suffix;
            $this->assertTrue($point->save());
            $this->points[] = $point;
        }
    }

    protected function tearDown(): void
    {
        foreach (array_merge($this->points, $this->zipCodes, $this->cities) as $model) {
            $model->delete();
        }
        $this->province->delete();

        unset($_SERVER['REQUEST_METHOD']);
        $this->logErrors();
    }

    private function handle(array $query): TestableEditCiudad
    {
        $controller = new TestableEditCiudad('EditCiudad', '/EditCiudad');

        $user = new User();
        $user->nick = 'test-edit-ciudad';
        $controller->user = $user;

        $controller->permissions = new ControllerPermissions();
        $controller->permissions->set(true, 1, false, true);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $controller->testRequest = new Request(['query' => $query]);
        $controller->handle();

        return $controller;
    }
}

final class TestableEditCiudad extends EditCiudad
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
