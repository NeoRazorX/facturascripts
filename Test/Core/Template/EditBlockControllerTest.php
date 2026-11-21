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
use FacturaScripts\Core\Lib\ControllerPermissions;
use FacturaScripts\Core\Request;
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
 * un formulario, dos listados en columnas y dos listados en pestañas perezosas.
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

        $cities = $controller->viewBlock('ListCiudad')->view();
        $this->assertSame(1, $cities->count);
        $this->assertSame($this->city->idciudad, $cities->cursor[0]->idciudad);
    }

    public function testBlocksNeedTheRecord(): void
    {
        $controller = $this->handle([]);

        $this->assertFalse($controller->hasData);
        $this->assertTrue($controller->block('EditPais')->isVisible($controller->hasRecord()));
        $this->assertFalse($controller->block('geography')->isVisible($controller->hasRecord()));
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
        $controller = $this->handle(['code' => $this->country->codpais, 'fragment' => 'ListPuntoInteresCiudad']);

        $this->assertTrue($controller->hasData);
        $this->assertFalse($controller->block('ListPuntoInteresCiudad')->isDeferred());
        $this->assertNotEmpty($controller->viewBlock('ListPuntoInteresCiudad')->view()->where);

        // el resto de bloques no se cargan
        $this->assertSame([], $controller->viewBlock('ListProvincia')->view()->cursor);
        $this->assertSame([], $controller->viewBlock('ListCiudad')->view()->cursor);
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

    public function testLazyTabsLoadOnlyTheActiveTab(): void
    {
        $controller = $this->handle(['code' => $this->country->codpais]);

        // la primera pestaña se carga y la segunda queda aplazada, sin consultar sus datos
        $this->assertFalse($controller->block('ListCiudad')->isDeferred());
        $this->assertTrue($controller->block('ListPuntoInteresCiudad')->isDeferred());
        $this->assertSame([], $controller->viewBlock('ListPuntoInteresCiudad')->view()->where);

        // si la petición va dirigida a la segunda pestaña, es esa la que se carga
        $controller = $this->handle(['code' => $this->country->codpais, 'activetab' => 'ListPuntoInteresCiudad']);
        $this->assertSame('ListPuntoInteresCiudad', $controller->target());
        $this->assertTrue($controller->block('ListCiudad')->isDeferred());
        $this->assertFalse($controller->block('ListPuntoInteresCiudad')->isDeferred());
        $this->assertNotEmpty($controller->viewBlock('ListPuntoInteresCiudad')->view()->where);
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
}

final class TestableEditPais extends EditPais
{
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
}
