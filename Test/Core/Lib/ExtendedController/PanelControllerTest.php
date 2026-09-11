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
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\MenuManager;
use FacturaScripts\Dinamic\Model\User;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class PanelControllerTest extends TestCase
{
    public function testViewsWithoutGroupAreReturnedUnderEmptyKey(): void
    {
        $controller = $this->controllerWithViews();

        $groups = $controller->getViewGroups();
        $this->assertSame([''], array_keys($groups));
        $this->assertSame(array_keys($controller->views), array_keys($groups['']));
    }

    public function testSetViewGroupKeepsInsertionOrder(): void
    {
        $controller = $this->controllerWithViews();
        $viewNames = array_keys($controller->views);
        $this->assertGreaterThanOrEqual(3, count($viewNames));

        // agrupamos la última vista en 'sales' y la segunda en 'crm'
        $last = end($viewNames);
        $controller->setViewGroup($last, 'sales');
        $controller->setViewGroup($viewNames[1], 'crm');
        $this->assertSame('sales', $controller->tab($last)->settings['group']);

        $groups = $controller->getViewGroups();

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
        $controller->setViewGroup(end($viewNames), 'sales');
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
