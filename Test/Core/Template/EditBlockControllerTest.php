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

namespace FacturaScripts\Test\Core\Template;

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Lib\ControllerPermissions;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Controller\EditPais;
use FacturaScripts\Dinamic\Lib\MultiRequestProtection;
use FacturaScripts\Dinamic\Model\Ciudad;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Provincia;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Prueba el ciclo de petición de los controladores de bloques con EditPais, que tiene
 * un formulario y dos listados en pestañas perezosas.
 */
final class EditBlockControllerTest extends TestCase
{
    use LogErrorsTrait;

    /** @var Ciudad */
    private $city;

    /** @var Pais */
    private $country;

    /** @var Pais */
    private $otherCountry;

    /** @var Provincia */
    private $province;

    public function testActionGoesToTheTargetBlock(): void
    {
        // una acción propia del bloque solo se ejecuta si la petición va dirigida a él
        $calls = [];
        $register = function (TestableEditPais $controller) use (&$calls): void {
            $controller->viewBlock('ListProvincia')->action('ping', function ($block) use (&$calls): void {
                $calls[] = $block->name();
            });
        };

        $query = ['code' => $this->country->codpais, 'action' => 'ping'];
        $this->handle($query + ['block' => 'ListCiudad'], [], true, $register);
        $this->assertSame([], $calls);

        $this->handle($query + ['block' => 'ListProvincia'], [], true, $register);
        $this->assertSame(['ListProvincia'], $calls);
    }

    public function testBlocksLoadTheDataOfTheRecord(): void
    {
        $controller = $this->handle(['code' => $this->country->codpais]);

        $this->assertTrue($controller->hasData);
        $this->assertSame($this->country->codpais, $controller->record()->codpais);

        // cada bloque carga solo los datos del registro principal
        $provinces = $controller->viewBlock('ListProvincia')->view();
        $this->assertSame(1, $provinces->count);
        $this->assertSame($this->province->idprovincia, $provinces->cursor[0]->idprovincia);

        // las ciudades están en otra pestaña: se cargan cuando la petición va dirigida a ella
        $controller = $this->handle(['code' => $this->country->codpais, 'activetab' => 'ListCiudad']);
        $cities = $controller->viewBlock('ListCiudad')->view();
        $this->assertSame(1, $cities->count);
        $this->assertSame($this->city->idciudad, $cities->cursor[0]->idciudad);
    }

    public function testBlocksNeedTheRecord(): void
    {
        $controller = $this->handle([]);

        $this->assertFalse($controller->hasData);
        $this->assertTrue($controller->block('EditPais')->isVisible($controller->hasRecord()));
        $this->assertFalse($controller->block('places')->isVisible($controller->hasRecord()));
        $this->assertSame([], $controller->viewBlock('ListProvincia')->view()->cursor);
    }

    public function testEditNeedsPermissionAndToken(): void
    {
        $query = ['code' => $this->country->codpais];
        $form = [
            'action' => 'edit',
            'activetab' => 'EditPais',
            'code' => $this->country->codpais,
            'codpais' => $this->country->codpais,
            'nombre' => 'Changed ' . $this->country->codpais,
        ];

        // sin permiso de modificación no se guarda
        $token = (new MultiRequestProtection())->newToken();
        $this->handle($query, $form + ['multireqtoken' => $token], false);
        $this->assertSame($this->country->nombre, $this->reload()->nombre);

        // con permiso, pero sin token válido, tampoco
        $this->handle($query, $form + ['multireqtoken' => 'invalid-token']);
        $this->assertSame($this->country->nombre, $this->reload()->nombre);

        // con permiso y token se guarda, y la página muestra el registro ya guardado
        MiniLog::clear();
        $token = (new MultiRequestProtection())->newToken();
        $controller = $this->handle($query, $form + ['multireqtoken' => $token]);
        $this->assertSame($form['nombre'], $this->reload()->nombre);
        $this->assertSame($form['nombre'], $controller->record()->nombre);
    }

    public function testFragmentLoadsOnlyThatBlock(): void
    {
        $controller = $this->handle(['code' => $this->country->codpais, 'fragment' => 'ListCiudad']);

        $this->assertTrue($controller->hasData);
        $this->assertFalse($controller->block('ListCiudad')->isDeferred());
        $this->assertNotEmpty($controller->viewBlock('ListCiudad')->view()->where);

        // el resto de bloques no se cargan
        $this->assertSame([], $controller->viewBlock('ListProvincia')->view()->cursor);
    }

    public function testMapShowsOnlyThePlacesOfTheRecord(): void
    {
        // con la página el mapa queda aplazado y no consulta nada
        $controller = $this->handle(['code' => $this->country->codpais]);
        $this->assertTrue($controller->block('map')->isDeferred());
        $this->assertSame(0, $controller->block('map')->count());

        // sin coordenadas no hay marcadores
        $controller = $this->handle(['code' => $this->country->codpais, 'fragment' => 'map']);
        $this->assertFalse($controller->block('map')->isDeferred());
        $this->assertSame([], $controller->block('map')->config()['markers']);

        // con coordenadas sale la ciudad de este país, pero no la del otro; las provincias no se pintan
        foreach (Provincia::all([Where::in('codpais', [$this->country->codpais, $this->otherCountry->codpais])]) as $province) {
            $province->latitude = 40.4;
            $province->longitude = -3.7;
            $this->assertTrue($province->save());
        }
        foreach (Ciudad::all() as $city) {
            if (0 === strpos($city->ciudad, 'City ')) {
                $city->latitude = 41.6;
                $city->longitude = -0.9;
                $this->assertTrue($city->save());
            }
        }

        $controller = $this->handle(['code' => $this->country->codpais, 'fragment' => 'map']);
        $markers = $controller->block('map')->config()['markers'];
        $this->assertSame(['City ' . $this->country->codpais], array_column($markers, 'title'));
        $this->assertSame(41.6, $markers[0]['lat']);
        $this->assertSame($this->city->url(), $markers[0]['url']);
        $this->assertSame(1, $controller->block('map')->count());
    }

    public function testFragmentUrlKeepsTheRecord(): void
    {
        $controller = $this->handle(['code' => $this->country->codpais, 'action' => 'save-ok']);
        MiniLog::clear();

        $this->assertSame(
            'EditPais?code=' . $this->country->codpais . '&fragment=ListCiudad',
            $controller->fragmentUrl($controller->block('ListCiudad'))
        );
    }

    public function testHeaderActionNeedsTokenAndAnAvailableButton(): void
    {
        $calls = 0;
        $available = true;
        $register = function (TestableEditPais $controller) use (&$calls, &$available): void {
            $controller->header()->button('ping', [
                'enabled' => function () use (&$available): bool {
                    return $available;
                },
                'run' => function () use (&$calls): void {
                    $calls++;
                },
            ]);
        };
        $query = ['code' => $this->country->codpais];

        // sin token válido no se ejecuta
        $this->handle($query, ['action' => 'ping', 'multireqtoken' => 'invalid-token'], true, $register);
        $this->assertSame(0, $calls);

        // tampoco si el botón no está disponible para el registro, o si el registro no existe
        $available = false;
        $this->handle($query, ['action' => 'ping', 'multireqtoken' => $this->token()], true, $register);
        $this->assertSame(0, $calls);

        $available = true;
        $this->handle(['code' => 'missing'], ['action' => 'ping', 'multireqtoken' => $this->token()], true, $register);
        $this->assertSame(0, $calls);

        // con token y el botón disponible, se ejecuta
        MiniLog::clear();
        $this->handle($query, ['action' => 'ping', 'multireqtoken' => $this->token()], true, $register);
        $this->assertSame(1, $calls);
    }

    public function testHeaderShowsTheRecord(): void
    {
        // las estadísticas y los botones los declara cada página: aquí, una estadística de un bloque
        // cargado, otra de uno aplazado y un desplegable con un enlace que depende del registro
        $register = function (TestableEditPais $controller): void {
            $controller->header()
                ->stat('provinces', ['block' => 'ListProvincia'])
                ->stat('cities', ['block' => 'ListCiudad'])
                ->dropdown('location', ['label' => 'location'])
                ->button('open-map', [
                    'dropdown' => 'location',
                    'type' => 'link',
                    'url' => fn($country) => 'map?mlat=' . $country->latitude . '&mlon=' . $country->longitude,
                    'visible' => fn($country) => false === empty($country->latitude),
                ]);
        };
        $controller = $this->handle(['code' => $this->country->codpais], [], true, $register);
        $header = $controller->header();
        $record = $controller->record();

        // la cifra sale del bloque ya cargado: la pestaña aplazada todavía no la tiene
        $stats = $header->stats($record, $controller->page());
        $this->assertSame(['provinces'], array_column($stats, 'name'));
        $this->assertSame(1, $stats[0]['value']);
        $this->assertSame('ListProvincia', $stats[0]['block']);

        // un país no cuelga de nada: su miga de pan es el listado y el propio país
        $crumbs = $header->breadcrumbFor($record);
        $this->assertSame([Tools::trans('countries'), $record->nombre], array_column($crumbs, 'title'));
        $this->assertSame([$record->url('list'), ''], array_column($crumbs, 'url'));

        // sin coordenadas no se muestra el desplegable, que se queda sin botones
        $this->assertSame([], $header->buttons($record));
        $record->latitude = 40.4;
        $record->longitude = -3.7;
        $this->assertSame('location', $header->buttons($record)[0]->name());
        $this->assertStringContainsString('mlat=40.4&mlon=-3.7', $header->find('open-map')->href($record));

        // el índice lista los bloques con título, también los de dentro de las pestañas
        $sections = array_map(fn($block) => $block->name(), $controller->sections());
        $this->assertSame(['map', 'ListProvincia', 'ListCiudad'], $sections);
        $this->assertSame('EditPais?code=' . $this->country->codpais, $controller->pageUrl());

        // cada listado recibe la url de sus opciones, para pintar el botón en su barra
        $this->assertSame(
            'EditPageOption?code=ListProvincia&url=' . rawurlencode($controller->pageUrl()),
            $controller->viewBlock('ListProvincia')->view()->settings['optionsUrl']
        );
        $this->assertTrue($controller->block('ListProvincia')->hasOptionsButton());
        $this->assertFalse($controller->block('EditPais')->hasOptionsButton());
    }

    public function testLazyTabsLoadOnlyTheActiveTab(): void
    {
        $controller = $this->handle(['code' => $this->country->codpais]);

        // la primera pestaña se carga y las demás quedan aplazadas, sin consultar sus datos
        $this->assertFalse($controller->block('ListProvincia')->isDeferred());
        $this->assertTrue($controller->block('ListCiudad')->isDeferred());
        $this->assertSame([], $controller->viewBlock('ListCiudad')->view()->where);

        // si la petición va dirigida a otra pestaña, es esa la que se carga
        $controller = $this->handle(['code' => $this->country->codpais, 'activetab' => 'ListCiudad']);
        $this->assertSame('ListCiudad', $controller->target());
        $this->assertTrue($controller->block('ListProvincia')->isDeferred());
        $this->assertFalse($controller->block('ListCiudad')->isDeferred());
        $this->assertNotEmpty($controller->viewBlock('ListCiudad')->view()->where);
    }

    public function testCountsOfDeferredBlocksWithoutLoadingThem(): void
    {
        $names = ['ListProvincia', 'ListCiudad', 'ListCodigoPostal', 'map', 'unknown'];
        $controller = $this->handle(['code' => $this->country->codpais, 'counts' => implode(',', $names)]);

        // solo cuentan los listados, y solo los registros de este país; el mapa no sabe contar sin cargarse
        $this->assertSame(
            ['ListProvincia' => 1, 'ListCiudad' => 1],
            $controller->jsonData
        );

        // la petición de contadores no carga ningún bloque
        $this->assertSame([], $controller->viewBlock('ListProvincia')->view()->cursor);
        $this->assertSame([], $controller->viewBlock('ListCiudad')->view()->where);

        // sin registro principal los bloques no se muestran, así que no hay nada que contar
        $controller = $this->handle(['counts' => 'ListProvincia']);
        $this->assertSame([], $controller->jsonData);
    }

    public function testNavigationComesFromTheSourceList(): void
    {
        // la foto que guarda el listado de origen al pintar sus filas
        $codes = [$this->otherCountry->codpais, $this->country->codpais];
        Cache::set('nav-test-block-controller-abc123', [
            'targets' => ['EditPais' => $codes],
            'count' => 20,
            'start' => 4,
            'title' => 'Countries',
            'query' => 'spa',
        ]);

        try {
            // sin token no hay navegación
            $this->assertSame([], $this->handle(['code' => $this->country->codpais])->getNavigation());

            $navigation = $this->handle(['code' => $this->country->codpais, 'navfrom' => 'abc123'])->getNavigation();
            $this->assertSame(20, $navigation['count']);

            // el listado de origen y su búsqueda explican de dónde sale la cuenta
            $this->assertSame('Countries', $navigation['source']);
            $this->assertSame('spa', $navigation['query']);
            $this->assertSame(6, $navigation['position']);
            $this->assertSame('EditPais?code=' . $this->otherCountry->codpais . '&navfrom=abc123', $navigation['prev']);
            $this->assertSame('', $navigation['next']);

            // con un token desconocido, o sin registro, tampoco
            $this->assertSame([], $this->handle(['code' => $this->country->codpais, 'navfrom' => 'zzz999'])->getNavigation());
            $this->assertSame([], $this->handle(['navfrom' => 'abc123'])->getNavigation());
        } finally {
            Cache::delete('nav-test-block-controller-abc123');
        }
    }

    protected function setUp(): void
    {
        MiniLog::clear();

        $this->country = $this->newCountry('A');
        $this->otherCountry = $this->newCountry('B');

        // una provincia y una ciudad en cada país, para comprobar que no se mezclan
        foreach ([$this->otherCountry, $this->country] as $country) {
            $this->province = new Provincia();
            $this->province->codpais = $country->codpais;
            $this->province->provincia = 'Province ' . $country->codpais;
            $this->assertTrue($this->province->save());

            $this->city = new Ciudad();
            $this->city->idprovincia = $this->province->idprovincia;
            $this->city->ciudad = 'City ' . $country->codpais;
            $this->assertTrue($this->city->save());
        }
    }

    protected function tearDown(): void
    {
        foreach ([$this->country, $this->otherCountry] as $country) {
            foreach (Provincia::all([Where::eq('codpais', $country->codpais)]) as $province) {
                foreach (Ciudad::all([Where::eq('idprovincia', $province->idprovincia)]) as $city) {
                    $city->delete();
                }
                $province->delete();
            }
            $country->delete();
        }

        unset($_SERVER['REQUEST_METHOD']);
        $this->logErrors();
    }

    private function handle(array $query, array $form = [], bool $allowUpdate = true, ?callable $setup = null): TestableEditPais
    {
        $controller = new TestableEditPais('EditPais', '/EditPais');

        $user = new User();
        $user->nick = 'test-block-controller';
        $controller->user = $user;

        $controller->permissions = new ControllerPermissions();
        $controller->permissions->set(true, 1, false, $allowUpdate);

        // los listados distinguen si la petición es GET o POST para recordar sus filtros
        $_SERVER['REQUEST_METHOD'] = empty($form) ? 'GET' : 'POST';
        $controller->testRequest = new Request(['query' => $query, 'request' => $form]);
        $controller->handle($setup);

        return $controller;
    }

    private function newCountry(string $suffix): Pais
    {
        $country = new Pais();
        $country->codpais = 'TB' . mt_rand(1000, 9999) . $suffix;
        $country->nombre = 'Test ' . $country->codpais;
        $this->assertTrue($country->save());

        return $country;
    }

    private function reload(): Pais
    {
        $country = new Pais();
        $this->assertTrue($country->load($this->country->codpais));

        return $country;
    }

    private function token(): string
    {
        return (new MultiRequestProtection())->newToken();
    }
}

final class TestableEditPais extends EditPais
{
    /** @var array|null */
    public $jsonData;

    /** @var Request */
    public $testRequest;

    public function handle(?callable $setup = null): void
    {
        $this->createBlocks();
        if (null !== $setup) {
            $setup($this);
        }

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
