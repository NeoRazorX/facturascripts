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
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Controller\EditCliente;
use FacturaScripts\Dinamic\Controller\EditContacto;
use FacturaScripts\Dinamic\Controller\EditEmailSent;
use FacturaScripts\Dinamic\Controller\EditUser;
use FacturaScripts\Dinamic\Model\Agente;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\Contacto;
use FacturaScripts\Dinamic\Model\EmailSent;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

/**
 * Con onlyOwnerData, un registro ajeno no se puede obtener desde las fichas de edición
 * ni con acciones previas a loadData() ni con acciones posteriores como export.
 */
final class OwnerDataActionsTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var Agente */
    private $agent;

    /** @var Cliente */
    private $customer;

    /** @var Agente */
    private $otherAgent;

    /** @var Cliente */
    private $otherCustomer;

    /** @var User */
    private $otherUser;

    /** @var User */
    private $user;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testCustomerOfAnotherAgentIsDenied(): void
    {
        $code = $this->otherCustomer->codcliente;
        $requests = [
            'view' => [[], []],
            'export' => [['action' => 'export', 'option' => 'CSV'], []],
            'check-vies' => [[], ['action' => 'check-vies', 'code' => $code]],
        ];
        foreach ($requests as $label => [$query, $input]) {
            $this->assertAccessDenied(EditCliente::class, ['code' => $code] + $query, $input, $label);
        }

        // la pestaña activa secundaria no evita la comprobación del registro principal
        $this->assertAccessDenied(EditCliente::class, [], [
            'action' => 'check-vies',
            'activetab' => 'EditDireccionContacto',
            'code' => $code,
        ], 'check-vies secondary tab');
    }

    public function testOwnCustomerCanBeExported(): void
    {
        $response = $this->runController(EditCliente::class, [
            'code' => $this->customer->codcliente,
            'action' => 'export',
            'option' => 'CSV',
        ], []);

        $this->assertStringContainsString($this->customer->nombre, $response->getContent());
    }

    public function testContactOfAnotherAgentCanNotBeConverted(): void
    {
        $contact = $this->getRandomContact('OwnerDataActionsTest');
        $contact->codagente = $this->otherAgent->codagente;
        $this->assertTrue($contact->save());

        try {
            $this->assertAccessDenied(EditContacto::class, ['code' => $contact->idcontacto], [
                'action' => 'convert-into-customer',
            ], 'convert-into-customer');

            // no se ha creado ningún cliente a partir del contacto
            $contact->reload();
            $this->assertEmpty($contact->codcliente);
        } finally {
            $contact->delete();
        }
    }

    public function testEmailOfAnotherUserIsDenied(): void
    {
        $email = new EmailSent();
        $email->addressee = 'boss@example.com';
        $email->body = 'OwnerDataActionsTest';
        $email->html = '<b>OwnerDataActionsTest</b>';
        $email->nick = $this->otherUser->nick;
        $email->subject = 'OwnerDataActionsTest';
        $this->assertTrue($email->save());

        try {
            $code = (string)$email->id;
            $this->assertAccessDenied(EditEmailSent::class, ['code' => $code], [], 'view');
            $this->assertAccessDenied(EditEmailSent::class, ['code' => $code, 'action' => 'export', 'option' => 'CSV'], [], 'export');
            $this->assertAccessDenied(EditEmailSent::class, ['action' => 'getHtml', 'code' => $code], [], 'getHtml');

            // getHtml carga el email por su cuenta: tampoco sirve indicar otra pestaña activa
            $this->assertAccessDenied(EditEmailSent::class, ['action' => 'getHtml', 'code' => $code], [
                'activetab' => 'ListEmailSent',
            ], 'getHtml secondary tab');
        } finally {
            $email->delete();
        }
    }

    public function testUserCanNotExportAnotherUser(): void
    {
        $this->assertAccessDenied(EditUser::class, [
            'code' => $this->otherUser->nick,
            'action' => 'export',
            'option' => 'CSV',
        ], [], 'export', false);
    }

    protected function setUp(): void
    {
        MiniLog::clear();

        $this->agent = $this->getRandomAgent();
        $this->assertTrue($this->agent->save());

        $this->otherAgent = $this->getRandomAgent();
        $this->assertTrue($this->otherAgent->save());

        $this->user = $this->getRandomUser();
        $this->user->codagente = $this->agent->codagente;
        $this->assertTrue($this->user->save());

        $this->otherUser = $this->getRandomUser();
        $this->assertTrue($this->otherUser->save());

        $this->customer = $this->getRandomCustomer('OwnerDataActionsTest');
        $this->customer->codagente = $this->agent->codagente;
        $this->assertTrue($this->customer->save());

        $this->otherCustomer = $this->getRandomCustomer('OwnerDataActionsTest');
        $this->otherCustomer->codagente = $this->otherAgent->codagente;
        $this->assertTrue($this->otherCustomer->save());
    }

    protected function tearDown(): void
    {
        $this->customer->delete();
        $this->otherCustomer->delete();
        $this->user->delete();
        $this->otherUser->delete();
        $this->agent->delete();
        $this->otherAgent->delete();

        $this->logErrors();
    }

    private function assertAccessDenied(string $controllerClass, array $query, array $input, string $label, bool $onlyOwner = true): void
    {
        $label = $controllerClass . ' ' . $label;
        try {
            $response = $this->runController($controllerClass, $query, $input, $onlyOwner);
            $this->fail($label . ' not denied: ' . substr((string)$response->getContent(), 0, 200));
        } catch (KernelException $exception) {
            $this->assertEquals('AccessDenied', $exception->handler, $label);
        }
    }

    private function runController(string $controllerClass, array $query, array $input, bool $onlyOwner = true): Response
    {
        $pageName = substr(strrchr($controllerClass, '\\'), 1);
        $controller = new $controllerClass($pageName, '/' . $pageName);

        // token válido, para que la acción no se rechace por otro motivo
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($this->user->nick);
        $input['multireqtoken'] = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();
        $controller->request = new Request(['query' => $query, 'request' => $input]);

        // el constructor lee la pestaña activa de la petición original
        if (isset($input['activetab'])) {
            $controller->active = $input['activetab'];
        }

        // usuario con todos los permisos sobre la página, pero solo sobre sus datos
        $permissions = new ControllerPermissions();
        $permissions->set(true, 1, true, true, $onlyOwner);
        $permissions->allowExport = true;

        $response = new Response();
        $response->disableSend(true);
        $controller->privateCore($response, $this->user, $permissions);

        return $response;
    }
}
