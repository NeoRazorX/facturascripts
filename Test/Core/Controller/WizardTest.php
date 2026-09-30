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

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Controller\Wizard;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Page;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class WizardTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    private const TEST_HOMEPAGE = 'ListFacturaCliente';

    /** @var string|null */
    private $codrole;

    /** @var Page[] */
    private $createdPages = [];

    /** @var User */
    private $user;

    public function testStep3RequiresToken(): void
    {
        // un enlace GET sin token no aplica el paso 3
        $this->runStep3(null);
        $this->assertEquals(self::TEST_HOMEPAGE, $this->reloadUser()->homepage, 'step3-applied-without-token');

        // con un token inválido tampoco
        $this->runStep3(false);
        $this->assertEquals(self::TEST_HOMEPAGE, $this->reloadUser()->homepage, 'step3-applied-with-invalid-token');

        // con el token de la redirección del paso 2 sí se aplica
        $this->runStep3(true);
        $this->assertContains($this->reloadUser()->homepage, ['AdminPlugins', 'Dashboard'], 'step3-not-applied-with-valid-token');
    }

    protected function setUp(): void
    {
        MiniLog::clear();

        // el paso 3 cambia el rol predeterminado; lo guardamos para restaurarlo
        $this->codrole = Tools::settings('default', 'codrole');

        // homepage es clave ajena de pages: nos aseguramos de que existan las páginas que usa el test
        foreach ([self::TEST_HOMEPAGE, 'AdminPlugins', 'Dashboard'] as $name) {
            $this->ensurePage($name);
        }

        $this->user = $this->getRandomUser();
        $this->user->admin = true;
        $this->user->homepage = self::TEST_HOMEPAGE;
        $this->assertTrue($this->user->save());
    }

    protected function tearDown(): void
    {
        Tools::settingsSet('default', 'codrole', $this->codrole);
        Tools::settingsSave();

        $this->user->delete();

        // eliminamos solo las páginas que ha creado el test
        foreach ($this->createdPages as $page) {
            $page->delete();
        }

        $this->logErrors();
    }

    private function ensurePage(string $name): void
    {
        $page = new Page();
        if ($page->load($name)) {
            return;
        }

        $page->name = $name;
        $page->title = $name;
        $this->assertTrue($page->save(), 'can-not-save-page-' . $name);
        $this->createdPages[] = $page;
    }

    private function reloadUser(): User
    {
        $user = new User();
        $this->assertTrue($user->load($this->user->nick));
        return $user;
    }

    /**
     * @param bool|null $validToken null envía la acción sin token
     */
    private function runStep3(?bool $validToken): void
    {
        $controller = new Wizard('Wizard', '/Wizard');
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($this->user->nick);
        $token = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();

        // el paso 2 redirige al 3 por GET, con el token en la URL
        $query = ['action' => 'step3'];
        if (null !== $validToken) {
            $query['multireqtoken'] = $validToken ? $token : 'invalid-token';
        }

        $controller->request = new Request(['query' => $query]);

        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, true);

        $response = new Response();
        $response->disableSend(true);
        $controller->privateCore($response, $this->reloadUser(), $permissions);
    }
}
