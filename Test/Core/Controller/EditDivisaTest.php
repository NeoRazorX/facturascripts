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
use FacturaScripts\Dinamic\Controller\EditDivisa;
use FacturaScripts\Dinamic\Model\Divisa;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

/**
 * Prueba la página de bloques de una divisa: solo su formulario, centrado en la pantalla.
 */
final class EditDivisaTest extends TestCase
{
    use LogErrorsTrait;

    /** @var Divisa */
    private $currency;

    public function testBreadcrumbIsTheListAndTheCurrency(): void
    {
        $controller = $this->handle(['code' => $this->currency->coddivisa]);
        $crumbs = $controller->header()->breadcrumbFor($controller->record());

        $this->assertSame(
            [Tools::trans('currencies'), $this->currency->descripcion],
            array_column($crumbs, 'title')
        );
        $this->assertSame(['ListPais?activetab=ListDivisa', ''], array_column($crumbs, 'url'));
    }

    public function testDefaultCurrencyShowsItsStatus(): void
    {
        // una divisa cualquiera no tiene estado
        $controller = $this->handle(['code' => $this->currency->coddivisa]);
        $this->assertNull($controller->header()->statusFor($controller->record()));

        // la divisa predeterminada lo indica en la cabecera, en color de aviso
        $controller = $this->handle(['code' => Tools::settings('default', 'coddivisa')]);
        $this->assertTrue($controller->hasData);
        $this->assertSame(
            ['label' => Tools::trans('default'), 'color' => 'warning'],
            $controller->header()->statusFor($controller->record())
        );
    }

    public function testPageIsOnlyTheCenteredForm(): void
    {
        $controller = $this->handle(['code' => $this->currency->coddivisa]);

        $this->assertTrue($controller->hasData);
        $this->assertSame($this->currency->descripcion, $controller->record()->descripcion);

        // no hay más bloques que el formulario, y la página se centra con un ancho máximo
        $this->assertSame(['page', 'EditDivisa'], $controller->page()->names());
        $this->assertSame([], $controller->sections());
        $this->assertSame(Container::CENTERED_WIDTH, $controller->page()->maxWidth());
    }

    protected function setUp(): void
    {
        $this->currency = new Divisa();
        $this->currency->coddivisa = 'T' . mt_rand(10, 99);
        $this->currency->descripcion = 'Test currency';
        $this->assertTrue($this->currency->save());
    }

    protected function tearDown(): void
    {
        $this->currency->delete();

        unset($_SERVER['REQUEST_METHOD']);
        $this->logErrors();
    }

    private function handle(array $query): TestableEditDivisa
    {
        $controller = new TestableEditDivisa('EditDivisa', '/EditDivisa');

        $user = new User();
        $user->nick = 'test-edit-divisa';
        $controller->user = $user;

        $controller->permissions = new ControllerPermissions();
        $controller->permissions->set(true, 1, false, true);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $controller->testRequest = new Request(['query' => $query]);
        $controller->handle();

        return $controller;
    }
}

final class TestableEditDivisa extends EditDivisa
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
