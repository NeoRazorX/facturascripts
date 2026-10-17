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
use FacturaScripts\Dinamic\Controller\EditContacto;
use FacturaScripts\Dinamic\Controller\EditUser;
use FacturaScripts\Core\Lib\ExtendedController\PanelController;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * La búsqueda en la biblioteca la resuelve PanelController, no cada controlador. Si
 * dependiera del switch de cada uno, los que usan el trait sin enrutar la acción
 * devolverían la página entera y la petición ajax no podría leerla.
 */
final class DocFilesLibraryRoutingTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testTraitControllerAnswersWithoutRoutingTheAction(): void
    {
        // EditContacto usa DocFilesTrait y no enumera la acción en su propio switch
        $this->assertStringNotContainsString(
            'search-library',
            file_get_contents(FS_FOLDER . '/Core/Controller/EditContacto.php')
        );

        $contact = $this->getRandomContact('DocFilesLibraryRoutingTest');
        $this->assertTrue($contact->save());

        try {
            [$controller, $response] = $this->runController(
                EditContacto::class, 'EditContacto', $contact->idcontacto
            );

            // la respuesta es json, no la plantilla de la página
            $this->assertFalse($controller->getTemplate());

            $data = json_decode($response->getContent(), true);
            $this->assertIsArray($data);
            $this->assertArrayHasKey('files', $data);
            $this->assertIsArray($data['files']);
        } finally {
            $contact->delete();
        }
    }

    public function testControllerWithoutTraitIsNotAffected(): void
    {
        [$controller] = $this->runController(EditUser::class, 'EditUser', '');

        // sin el trait la acción sigue siendo desconocida y la página se renderiza
        $this->assertFalse(method_exists($controller, 'searchLibraryAction'));
        $this->assertNotFalse($controller->getTemplate());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function runController(string $class, string $name, $code): array
    {
        $user = $this->getAdminUser();

        /** @var PanelController $controller */
        $controller = new $class($name, '/' . $name);
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($user->nick);
        $token = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();

        $controller->request = new Request([
            'query' => ['code' => $code, 'action' => 'search-library'],
            'request' => ['multireqtoken' => $token],
        ]);

        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, true);

        $response = new Response();
        $response->disableSend();
        $controller->privateCore($response, $user, $permissions);

        return [$controller, $response];
    }

    private function getAdminUser(): User
    {
        foreach (User::all() as $user) {
            if ($user->admin) {
                return $user;
            }
        }

        $this->fail('no admin user found');
    }
}
