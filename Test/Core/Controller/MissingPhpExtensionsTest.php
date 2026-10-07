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

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Controller\AdminPlugins;
use FacturaScripts\Core\Controller\Updater;
use FacturaScripts\Core\Internal\Forja;
use FacturaScripts\Core\Internal\PhpExtensions;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * Sin las extensiones de PHP necesarias, el actualizador y la gestión de plugins
 * deben avisar y no ejecutar las acciones que acabarían en un error fatal.
 */
final class MissingPhpExtensionsTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var User */
    private $user;

    public function testAdminPluginsUploadNeedsFileinfo(): void
    {
        // con las extensiones, la subida (sin archivos) termina recargando la página
        $this->runController(new AdminPlugins('AdminPlugins', '/AdminPlugins'), 'upload');
        $this->assertContains('reloading', $this->originals('notice'));

        MiniLog::clear();
        PhpExtensions::$simulateMissing = ['fileinfo'];
        $this->runController(new AdminPlugins('AdminPlugins', '/AdminPlugins'), 'upload');
        $this->assertNotContains('reloading', $this->originals('notice'), 'upload-not-blocked');
        $this->assertSame(['fileinfo'], $this->missingWarnings());
    }

    public function testUpdaterUpdateNeedsZip(): void
    {
        // con la extensión zip, la acción intenta abrir el paquete (que no existe)
        $this->runController(new Updater('Updater', '/Updater'), 'update');
        $this->assertNotEmpty(MiniLog::read('', ['critical']), 'update-action-not-run');

        MiniLog::clear();
        PhpExtensions::$simulateMissing = ['zip'];
        $this->runController(new Updater('Updater', '/Updater'), 'update');
        $this->assertSame([], MiniLog::read('', ['critical']), 'update-not-blocked');
        $this->assertSame(['zip'], $this->missingWarnings());
    }

    protected function setUp(): void
    {
        MiniLog::clear();

        // evitamos consultar la forja
        Forja::$builds = [];
        Cache::set('forja_plugins', []);

        $this->user = $this->getRandomUser();
        $this->user->admin = true;
        $this->assertTrue($this->user->save());
    }

    protected function tearDown(): void
    {
        PhpExtensions::$simulateMissing = [];
        Forja::$builds = null;
        Cache::delete('forja_plugins');

        $this->user->delete();
        $this->logErrors();
    }

    private function missingWarnings(): array
    {
        $extensions = [];
        foreach (MiniLog::read('', ['warning']) as $item) {
            if ($item['original'] === 'php-extension-not-found') {
                $extensions[] = $item['context']['%extension%'];
            }
        }

        return $extensions;
    }

    private function originals(string $level): array
    {
        return array_column(MiniLog::read('', [$level]), 'original');
    }

    private function runController(Controller $controller, string $action): void
    {
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($this->user->nick);
        $token = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();

        $controller->request = new Request([
            'request' => [
                'action' => $action,
                'item' => 'missing-php-extensions-test',
                'multireqtoken' => $token,
            ],
        ]);

        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, true);

        $response = new Response();
        $response->disableSend(true);
        $controller->privateCore($response, $this->user, $permissions);
    }
}
