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

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Lib\ListFilter\AutocompleteFilter;
use FacturaScripts\Core\Lib\Widget\WidgetAutocomplete;
use FacturaScripts\Core\Lib\Widget\WidgetDatalist;
use FacturaScripts\Core\Model\CodeModel;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Controller\EditContacto;
use FacturaScripts\Dinamic\Controller\ListCliente;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * Las acciones autocomplete, datalist y select solo consultan la tabla y las columnas
 * que firmó el servidor al pintar el widget o el filtro.
 */
final class CodeModelActionsTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    const ACTIONS = [
        [ListCliente::class, 'autocomplete'],
        [EditContacto::class, 'autocomplete'],
        [EditContacto::class, 'datalist'],
        [EditContacto::class, 'select'],
    ];

    /** @var string */
    private $logkey;

    /** @var User */
    private $user;

    public function testRejectsUnsignedRequests(): void
    {
        foreach (self::ACTIONS as [$controllerClass, $action]) {
            $results = $this->runAction($controllerClass, $action, [
                'source' => 'users',
                'fieldcode' => 'nick',
                'fieldtitle' => 'nick',
                'term' => $this->user->nick,
            ]);
            $this->assertNotContains($this->user->nick, array_column($results, 'key'), $controllerClass . ' ' . $action);
        }
    }

    public function testRejectsTamperedRequests(): void
    {
        // la firma de un widget legítimo no sirve para otra tabla ni para otras columnas
        $sign = CodeModel::sign('clientes', 'codcliente', 'nombre');
        foreach (self::ACTIONS as [$controllerClass, $action]) {
            $results = $this->runAction($controllerClass, $action, [
                'source' => 'users',
                'fieldcode' => 'nick',
                'fieldtitle' => 'nick',
                'fieldsign' => $sign,
                'term' => $this->user->nick,
            ]);
            $this->assertNotContains($this->user->nick, array_column($results, 'key'), $controllerClass . ' ' . $action);
        }
    }

    public function testAcceptsSignedRequests(): void
    {
        $sign = CodeModel::sign('users', 'nick', 'nick');
        foreach (self::ACTIONS as [$controllerClass, $action]) {
            $results = $this->runAction($controllerClass, $action, [
                'source' => 'users',
                'fieldcode' => 'nick',
                'fieldtitle' => 'nick',
                'fieldsign' => $sign,
                'term' => $this->user->nick,
            ]);
            $this->assertContains($this->user->nick, array_column($results, 'key'), $controllerClass . ' ' . $action);
        }
    }

    public function testSignedRequestsNeverReturnHiddenColumns(): void
    {
        // aunque un widget declarase una columna oculta, CodeModel no la devuelve
        $sign = CodeModel::sign('users', 'logkey', 'nick');
        foreach (self::ACTIONS as [$controllerClass, $action]) {
            $results = $this->runAction($controllerClass, $action, [
                'source' => 'users',
                'fieldcode' => 'logkey',
                'fieldtitle' => 'nick',
                'fieldsign' => $sign,
                'term' => $this->user->nick,
            ]);
            $this->assertNotContains($this->logkey, array_column($results, 'key'), $controllerClass . ' ' . $action);
        }
    }

    public function testWidgetsAndFiltersRenderValidSign(): void
    {
        $values = [
            'tag' => 'values',
            'source' => 'clientes',
            'fieldcode' => 'codcliente',
            'fieldtitle' => 'nombre',
            'fieldfilter' => 'codgrupo',
        ];
        $data = ['children' => [$values], 'fieldname' => 'codcliente'];

        $widgets = [
            new WidgetAutocompleteForTest($data + ['type' => 'autocomplete']),
            new WidgetDatalistForTest($data + ['type' => 'datalist']),
        ];
        foreach ($widgets as $widget) {
            $sign = $this->extractSign($widget->renderInput());
            $this->assertTrue(CodeModel::verifySign($sign, 'clientes', 'codcliente', 'nombre', 'codgrupo'), get_class($widget));
        }

        $filter = new AutocompleteFilter('cliente', 'codcliente', 'customer', 'clientes', 'codcliente', 'nombre');
        $sign = $this->extractSign($filter->render());
        $this->assertTrue(CodeModel::verifySign($sign, 'clientes', 'codcliente', 'nombre'));
    }

    protected function setUp(): void
    {
        $this->user = $this->getRandomUser();
        $this->logkey = $this->user->newLogkey('127.0.0.1');
        $this->assertTrue($this->user->save());
    }

    protected function tearDown(): void
    {
        $this->user->delete();
        $this->logErrors();
    }

    private function extractSign(string $html): string
    {
        $this->assertSame(1, preg_match('/data-fieldsign="([0-9a-f]{64})"/', $html, $matches), 'data-fieldsign not found');
        return $matches[1];
    }

    private function runAction(string $controllerClass, string $action, array $params): array
    {
        $pageName = substr(strrchr($controllerClass, '\\'), 1);
        $controller = new $controllerClass($pageName, '/' . $pageName);
        $controller->request = new Request(['query' => array_merge(['action' => $action], $params)]);

        // usuario sin privilegios de administrador, con acceso a la página
        $permissions = new ControllerPermissions();
        $permissions->set(true, 1, false, false);

        $response = new Response();
        $response->disableSend(true);

        $limit = CodeModel::getLimit();
        $controller->privateCore($response, $this->user, $permissions);
        CodeModel::setLimit($limit);

        $results = json_decode($response->getContent(), true);
        $this->assertIsArray($results, $pageName . ' ' . $action . ' did not return json');
        return $results;
    }
}

class WidgetAutocompleteForTest extends WidgetAutocomplete
{
    public function renderInput(): string
    {
        return $this->inputHtml();
    }
}

class WidgetDatalistForTest extends WidgetDatalist
{
    public function renderInput(): string
    {
        return $this->inputHtml();
    }
}
