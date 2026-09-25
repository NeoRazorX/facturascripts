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

namespace FacturaScripts\Test\Core\Lib\ExtendedController;

use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Controller\EditProducto;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Lib\ExtendedController\ListView;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Pais;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\User;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class NavigationTest extends TestCase
{
    /** @var string[] */
    private $tokens = [];

    public function testCodesAreGroupedByTargetController(): void
    {
        // como las partidas de EditSubcuenta: la fila abre el asiento, no la partida
        $view = $this->listView([
            $this->row('EditAsiento?code=7'),
            $this->row('EditFacturaCliente?code=3'),
            $this->row('EditAsiento?code=5'),
        ]);
        $view->saveNavigation();

        $seed = $this->seed($view);
        $this->assertSame(['EditAsiento' => ['7', '5'], 'EditFacturaCliente' => ['3']], $seed['targets']);
        $this->assertNull($seed['count']);
    }

    public function testDuplicatedCodesAreRemovedAndCountIsNotExact(): void
    {
        // como las variantes de ListProducto: varias filas abren el mismo producto
        $view = $this->listView([
            $this->row('EditProducto?code=1'),
            $this->row('EditProducto?code=1'),
            $this->row('EditProducto?code=2'),
        ]);
        $view->saveNavigation();

        $seed = $this->seed($view);
        $this->assertSame(['EditProducto' => ['1', '2']], $seed['targets']);
        $this->assertNull($seed['count']);
        $this->assertNull($seed['start']);
    }

    public function testExtendedWindowUsesListOrder(): void
    {
        $this->assertGreaterThanOrEqual(6, Pais::count());

        // segunda página de 2 países: la ventana amplía una página antes y otra después
        $order = ['codpais' => 'DESC'];
        $view = new ListView('ListPais', 'countries', Pais::class, 'fa-solid fa-globe');
        $view->settings['itemLimit'] = 2;
        $view->loadData('', [], $order, 2, 2);
        $view->saveNavigation(true);

        $expected = [];
        foreach (Pais::all([], $order, 0, 6) as $pais) {
            $expected[] = $pais->codpais;
        }

        $seed = $this->seed($view);
        $this->assertSame(['EditPais' => $expected], $seed['targets']);
        $this->assertSame($view->count, $seed['count']);
        $this->assertSame(0, $seed['start']);
    }

    public function testGetNavigationIgnoresOtherControllers(): void
    {
        $view = $this->listView([$this->row('EditAsiento?code=1'), $this->row('EditAsiento?code=2')]);
        $view->saveNavigation();

        $this->assertSame([], $this->productNavigation($view->navToken, 1));
    }

    public function testGetNavigationLocatesTheRecord(): void
    {
        $view = $this->listView([
            $this->row('EditProducto?code=10'),
            $this->row('EditProducto?code=20'),
            $this->row('EditProducto?code=30'),
        ]);
        $view->count = 100;
        $view->offset = 40;
        $view->saveNavigation();

        $navigation = $this->productNavigation($view->navToken, 20);
        $this->assertSame(100, $navigation['count']);
        $this->assertSame(42, $navigation['position']);
        $this->assertSame('EditProducto?code=10&navfrom=' . $view->navToken, $navigation['prev']);
        $this->assertSame('EditProducto?code=30&navfrom=' . $view->navToken, $navigation['next']);

        // en los extremos de la foto no hay anterior o siguiente
        $this->assertSame('', $this->productNavigation($view->navToken, 10)['prev']);
        $this->assertSame('', $this->productNavigation($view->navToken, 30)['next']);

        // sin registro en la foto, sin token o con un token desconocido no hay navegación
        $this->assertSame([], $this->productNavigation($view->navToken, 99));
        $this->assertSame([], $this->productNavigation('', 20));
        $this->assertSame([], $this->productNavigation('abc123', 20));
    }

    public function testGetNavigationWithoutExactCount(): void
    {
        $view = $this->listView([
            $this->row('EditProducto?code=1'),
            $this->row('EditProducto?code=1'),
            $this->row('EditProducto?code=2'),
        ]);
        $view->saveNavigation();

        $navigation = $this->productNavigation($view->navToken, 1);
        $this->assertNull($navigation['count']);
        $this->assertNull($navigation['position']);
        $this->assertSame('EditProducto?code=2&navfrom=' . $view->navToken, $navigation['next']);
    }

    public function testRowsWithoutCodeDoNotSaveNavigation(): void
    {
        $view = $this->listView([$this->row('ListProducto'), $this->row('EditProducto')]);
        $view->saveNavigation();
        $this->assertSame('', $view->navToken);

        // tampoco cuando la navegación o el clic están desactivados
        $view = $this->listView([$this->row('EditProducto?code=1')]);
        $view->settings['navigation'] = false;
        $view->saveNavigation();
        $this->assertSame('', $view->navToken);

        $view->settings['navigation'] = true;
        $view->settings['clickable'] = false;
        $view->saveNavigation();
        $this->assertSame('', $view->navToken);
    }

    public function testTemplateRendersLinks(): void
    {
        $navData = ['count' => 27, 'next' => 'EditCliente?code=3&navfrom=abc', 'position' => 2, 'prev' => ''];

        // enlaces pequeños, sin botones: anterior desactivado, posición y siguiente
        $html = Html::render('Master/PanelNavigation.html.twig', ['navData' => $navData]);
        $this->assertStringNotContainsString('btn', $html);
        $this->assertMatchesRegularExpression('/chevron-left.*2 \/ 27.*chevron-right/s', $html);
        $this->assertStringContainsString('href="EditCliente?code=3&amp;navfrom=abc"', $html);
        $this->assertSame(1, substr_count($html, '<a '));
        $this->assertStringContainsString('aria-label="' . Tools::trans('next') . '"', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);

        // sin posición exacta no se muestra el contador
        $navData['position'] = null;
        $html = Html::render('Master/PanelNavigation.html.twig', ['navData' => $navData]);
        $this->assertStringNotContainsString(' / ', $html);

        // sin navegación no se pinta nada
        $this->assertSame('', trim(Html::render('Master/PanelNavigation.html.twig', ['navData' => []])));
    }

    protected function setUp(): void
    {
        $user = new User();
        $user->nick = 'test_nav';
        Session::set('user', $user);
    }

    protected function tearDown(): void
    {
        foreach ($this->tokens as $token) {
            Cache::delete('nav-test_nav-' . $token);
        }
        Cache::delete('nav-tokens-' . Session::get('controllerName') . '-ListTest-test_nav');
    }

    private function listView(array $cursor): ListView
    {
        $view = new ListView('ListTest', 'test', Producto::class, 'fa-solid fa-cubes');
        $view->count = count($cursor);
        $view->cursor = $cursor;
        return $view;
    }

    private function productNavigation(string $token, int $idproducto): array
    {
        $controller = new EditProducto('EditProducto', '/EditProducto');
        $controller->request = new Request(['query' => ['navfrom' => $token]]);
        $controller->user = Session::user();
        $controller->user->admin = true;

        // EditProducto cambia el límite estático de CodeModel al crear sus vistas
        $limit = CodeModel::getLimit();
        $method = new ReflectionMethod(EditProducto::class, 'createViews');
        $method->invoke($controller);
        CodeModel::setLimit($limit);

        $controller->tab($controller->getMainViewName())->model->idproducto = $idproducto;
        return $controller->getNavigation();
    }

    private function row(string $url): object
    {
        return new class ($url) {
            private $url;

            public function __construct(string $url)
            {
                $this->url = $url;
            }

            public function url(): string
            {
                return $this->url;
            }
        };
    }

    private function seed(ListView $view): array
    {
        $this->assertNotEmpty($view->navToken);
        $this->tokens[] = $view->navToken;
        return Cache::get('nav-test_nav-' . $view->navToken);
    }
}
