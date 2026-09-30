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

namespace FacturaScripts\Test\Core\Lib\AjaxForms;

use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Controller\EditPresupuestoCliente;
use FacturaScripts\Core\Controller\EditPresupuestoProveedor;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\PresupuestoCliente;
use FacturaScripts\Dinamic\Model\PresupuestoProveedor;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class DeleteDocActionTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var User */
    private $user;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testPurchaseDeleteDocRequiresToken(): void
    {
        $subject = $this->getRandomSupplier();
        $this->assertTrue($subject->save(), 'can-not-save-supplier');

        $doc = new PresupuestoProveedor();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject');
        $this->assertTrue($doc->save(), 'can-not-save-doc');

        try {
            $this->checkDeleteDoc(EditPresupuestoProveedor::class, $doc);
        } finally {
            if ($doc->exists()) {
                $doc->delete();
            }
            $subject->delete();
        }
    }

    public function testSalesDeleteDocRequiresToken(): void
    {
        $subject = $this->getRandomCustomer();
        $this->assertTrue($subject->save(), 'can-not-save-customer');

        $doc = new PresupuestoCliente();
        $this->assertTrue($doc->setSubject($subject), 'can-not-set-subject');
        $this->assertTrue($doc->save(), 'can-not-save-doc');

        try {
            $this->checkDeleteDoc(EditPresupuestoCliente::class, $doc);
        } finally {
            if ($doc->exists()) {
                $doc->delete();
            }
            $subject->getDefaultAddress()->delete();
            $subject->delete();
        }
    }

    protected function setUp(): void
    {
        MiniLog::clear();

        $this->user = $this->getRandomUser();
        $this->user->admin = true;
        $this->assertTrue($this->user->save());
    }

    protected function tearDown(): void
    {
        $this->user->delete();
        $this->logErrors();
    }

    private function checkDeleteDoc(string $controllerClass, BusinessDocument $doc): void
    {
        // un enlace GET sin token no borra el documento
        $this->runController($controllerClass, $doc, null);
        $this->assertTrue($doc->exists(), 'doc-deleted-by-get-without-token');

        // un POST con token inválido tampoco
        $this->runController($controllerClass, $doc, false);
        $this->assertTrue($doc->exists(), 'doc-deleted-with-invalid-token');

        // con token válido se borra
        $this->runController($controllerClass, $doc, true);
        $this->assertFalse($doc->exists(), 'doc-not-deleted-with-valid-token');
    }

    /**
     * @param bool|null $validToken null envía la acción por GET sin token
     */
    private function runController(string $controllerClass, BusinessDocument $doc, ?bool $validToken): void
    {
        $name = substr($controllerClass, strrpos($controllerClass, '\\') + 1);
        $controller = new $controllerClass($name, '/' . $name);
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($this->user->nick);
        $token = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();

        $query = ['code' => $doc->id()];
        $request = [];
        if (null === $validToken) {
            $query['action'] = 'delete-doc';
        } else {
            $request['action'] = 'delete-doc';
            $request['multireqtoken'] = $validToken ? $token : 'invalid-token';
        }

        $controller->request = new Request(['query' => $query, 'request' => $request]);

        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, true);

        $response = new Response();
        $response->disableSend(true);
        $controller->privateCore($response, $this->user, $permissions);
    }
}
