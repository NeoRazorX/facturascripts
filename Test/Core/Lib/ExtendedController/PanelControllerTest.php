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

use FacturaScripts\Core\Controller\EditContacto;
use FacturaScripts\Core\Controller\EditImpuesto;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Model\Subcuenta;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\MenuManager;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class PanelControllerTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var array Registros creados por los tests que se eliminan al terminar. */
    private $created = [];

    public function testViewsWithoutGroupAreReturnedUnderEmptyKey(): void
    {
        // cargar las vistas no debe alterar el límite estático de CodeModel,
        // aunque EditContacto.xml tenga un select con limit="9000"
        $limit = CodeModel::getLimit();
        $controller = $this->controllerWithViews();
        $this->assertSame($limit, CodeModel::getLimit());

        $groups = $controller->getTabGroups();
        $this->assertSame([''], array_keys($groups));
        $this->assertSame(array_keys($controller->views), array_keys($groups['']));
    }

    public function testSetTabGroupKeepsInsertionOrder(): void
    {
        $controller = $this->controllerWithViews();
        $viewNames = array_keys($controller->views);
        $this->assertGreaterThanOrEqual(3, count($viewNames));

        // agrupamos la última vista en 'sales' y la segunda en 'crm'
        $last = end($viewNames);
        $controller->setTabGroup($last, 'sales');
        $controller->setTabGroup($viewNames[1], 'crm');
        $this->assertSame('sales', $controller->tab($last)->settings['group']);

        $groups = $controller->getTabGroups();

        // las vistas sin grupo van primero; después los grupos según la primera vista que los usa
        $this->assertSame(['', 'crm', 'sales'], array_keys($groups));
        $this->assertSame([$viewNames[1]], array_keys($groups['crm']));
        $this->assertSame([$last], array_keys($groups['sales']));
        $this->assertArrayNotHasKey($last, $groups['']);
        $this->assertArrayNotHasKey($viewNames[1], $groups['']);
        $this->assertSame($viewNames[0], array_key_first($groups['']));
    }

    public function testLeftTemplateRendersGroupHeaders(): void
    {
        $controller = $this->controllerWithViews();
        $viewNames = array_keys($controller->views);
        $controller->setTabGroup(end($viewNames), 'sales');
        Session::set('user', $controller->user);

        $html = Html::render($controller->getTemplate(), [
            'controllerName' => 'EditContacto',
            'fsc' => $controller,
            'menuManager' => MenuManager::init(),
            'template' => $controller->getTemplate(),
        ]);

        // un único encabezado de grupo, con el texto traducido de 'sales', y todas las pestañas
        $this->assertSame(1, substr_count($html, 'nav-group-header'));
        $this->assertMatchesRegularExpression('/nav-group-header[^>]*>\s*' . preg_quote(Tools::trans('sales'), '/') . '\s*</', $html);
        foreach ($viewNames as $viewName) {
            $this->assertStringContainsString('id="' . $viewName . '-tab"', $html);
        }
    }

    public function testWidgetSubcuentaSearchReturnsMatchValue(): void
    {
        $subaccount = $this->createSubaccounts(1)[0];

        // configuramos el widget para guardar el id en lugar del código
        $results = $this->subcuentaSearch([
            'codejercicio' => $subaccount->codejercicio,
            'query' => $subaccount->codsubcuenta,
        ], 'idsubcuenta');

        $this->assertCount(1, $results);
        $this->assertSame($subaccount->codsubcuenta, $results[0]['codsubcuenta']);
        $this->assertSame($subaccount->idsubcuenta, $results[0]['match']);
    }

    public function testWidgetSubcuentaSearchShowsSelectedFirst(): void
    {
        [$first, $second] = $this->createSubaccounts(2);
        $request = [
            'codejercicio' => $first->codejercicio,
            'query' => $first->codcuenta,
            'sort' => 'cod-desc',
        ];

        // sin valor seleccionado se respeta el orden pedido
        $results = $this->subcuentaSearch($request);
        $this->assertSame([$second->codsubcuenta, $first->codsubcuenta], array_column($results, 'codsubcuenta'));

        // en la carga inicial, la subcuenta seleccionada aparece la primera y una sola vez
        $results = $this->subcuentaSearch($request + ['selected' => $first->codsubcuenta]);
        $this->assertSame([$first->codsubcuenta, $second->codsubcuenta], array_column($results, 'codsubcuenta'));
    }

    protected function tearDown(): void
    {
        // eliminamos en orden inverso: primero la subcuenta y después la cuenta
        foreach (array_reverse($this->created) as $model) {
            $model->delete();
        }
        $this->created = [];

        $this->logErrors();
    }

    /** @return Subcuenta[] */
    private function createSubaccounts(int $count): array
    {
        $exercise = $this->getRandomExercise();
        $this->assertTrue($exercise->save());
        $account = $this->getRandomAccount($exercise->codejercicio);
        $this->assertTrue($account->save());
        $this->created[] = $account;

        $list = [];
        for ($num = 1; $num <= $count; $num++) {
            $subaccount = new Subcuenta();
            $subaccount->codcuenta = $account->codcuenta;
            $subaccount->codejercicio = $exercise->codejercicio;
            $subaccount->codsubcuenta = $account->codcuenta . '.' . $num;
            $subaccount->descripcion = 'Test';
            $this->assertTrue($subaccount->save());
            $this->created[] = $subaccount;
            $list[] = $subaccount;
        }

        return $list;
    }

    private function subcuentaSearch(array $request, string $match = ''): array
    {
        $controller = new EditImpuesto('EditImpuesto', '/EditImpuesto');
        $controller->user = new User();
        $controller->user->admin = true;
        $method = new ReflectionMethod(EditImpuesto::class, 'createViews');
        $method->invoke($controller);
        if ($match) {
            $controller->tab('EditImpuesto')->columnForField('codsubcuentarep')->widget->match = $match;
        }

        $controller->request = new Request(['request' => $request + [
            'active_tab' => 'EditImpuesto',
            'col_name' => 'codsubcuentarep',
        ]]);
        $method = new ReflectionMethod(EditImpuesto::class, 'widgetSubcuentaSearchAction');
        return $method->invoke($controller);
    }

    private function controllerWithViews(): EditContacto
    {
        $controller = new EditContacto('EditContacto', '/EditContacto');
        $controller->user = new User();
        $controller->user->admin = true;
        $method = new ReflectionMethod(EditContacto::class, 'createViews');
        $method->invoke($controller);

        return $controller;
    }
}
