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

use FacturaScripts\Core\Lib\Block\Container;
use FacturaScripts\Core\Lib\ControllerPermissions;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Controller\EditCodigoPostal;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\CodigoPostal;
use FacturaScripts\Dinamic\Model\Provincia;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Prueba la página de bloques de un código postal: solo su formulario, centrado en la pantalla.
 */
final class EditCodigoPostalTest extends TestCase
{
    use LogErrorsTrait;

    /** @var Ciudad */
    private $city;

    /** @var Provincia */
    private $province;

    /** @var CodigoPostal */
    private $zipCode;

    public function testBreadcrumbGoesFromTheListToTheZipCode(): void
    {
        $controller = $this->handle(['code' => $this->zipCode->id]);
        $crumbs = $controller->header()->breadcrumbFor($controller->record());

        // listado, país, provincia, ciudad y el propio código postal, que no lleva enlace
        $this->assertSame(
            [
                Tools::trans('zip-codes'),
                $this->province->getCountry()->nombre,
                $this->province->provincia,
                $this->city->ciudad,
                $this->zipCode->number,
            ],
            array_column($crumbs, 'title')
        );
        $this->assertSame(
            [$this->zipCode->url('list'), 'EditPais?code=ESP', $this->province->url(), $this->city->url(), ''],
            array_column($crumbs, 'url')
        );
    }

    public function testNewZipCodeTakesTheValuesOfTheUrl(): void
    {
        // así llega desde el botón nuevo del listado de una ciudad
        $controller = $this->handle([
            'codpais' => 'ESP',
            'idprovincia' => $this->province->idprovincia,
            'idciudad' => $this->city->idciudad,
        ]);

        $this->assertFalse($controller->hasData);
        $this->assertSame(Container::CENTERED_WIDTH, $controller->page()->maxWidth());
        $this->assertEquals($this->city->idciudad, $controller->record()->idciudad);
        $this->assertEquals($this->province->idprovincia, $controller->record()->idprovincia);
    }

    public function testPageIsOnlyTheCenteredForm(): void
    {
        $controller = $this->handle(['code' => $this->zipCode->id]);

        $this->assertTrue($controller->hasData);
        $this->assertSame($this->zipCode->number, $controller->record()->number);

        // no hay más bloques que el formulario, y la página se centra con un ancho máximo
        $this->assertSame(['page', 'EditCodigoPostal'], $controller->page()->names());
        $this->assertSame([], $controller->sections());
        $this->assertSame(Container::CENTERED_WIDTH, $controller->page()->maxWidth());
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

        $this->zipCode = new CodigoPostal();
        $this->zipCode->codpais = 'ESP';
        $this->zipCode->idprovincia = $this->province->idprovincia;
        $this->zipCode->idciudad = $this->city->idciudad;
        $this->zipCode->number = (string)mt_rand(10000, 99999);
        $this->assertTrue($this->zipCode->save());
    }

    protected function tearDown(): void
    {
        $this->zipCode->delete();
        $this->city->delete();
        $this->province->delete();

        unset($_SERVER['REQUEST_METHOD']);
        $this->logErrors();
    }

    private function handle(array $query): TestableEditCodigoPostal
    {
        $controller = new TestableEditCodigoPostal('EditCodigoPostal', '/EditCodigoPostal');

        $user = new User();
        $user->nick = 'test-edit-zip';
        $controller->user = $user;

        $controller->permissions = new ControllerPermissions();
        $controller->permissions->set(true, 1, false, true);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $controller->testRequest = new Request(['query' => $query]);
        $controller->handle();

        return $controller;
    }
}

final class TestableEditCodigoPostal extends EditCodigoPostal
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
