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
use FacturaScripts\Core\Controller\DocumentStitcher;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Dinamic\Model\PedidoCliente;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class DocumentStitcherTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
    }

    public function testPrivateCoreRejectsQuantityGreaterThanPending(): void
    {
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'can-not-save-customer');

        $doc = new PedidoCliente();
        $doc->setSubject($customer);
        $this->assertTrue($doc->save(), 'can-not-save-document');

        $line = $doc->getNewLine();
        $line->cantidad = 10;
        $line->servido = 8;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line');

        $statusId = 0;
        foreach ($doc->getAvailableStatus() as $status) {
            if (empty($status->generadoc)) {
                continue;
            }

            $statusId = (int)$status->idestado;
            break;
        }
        $this->assertGreaterThan(0, $statusId, 'missing-generating-status');

        $user = $this->getRandomUser();
        $user->admin = true;
        $this->assertTrue($user->save(), 'can-not-save-user');

        $controller = new DocumentStitcher('DocumentStitcher', '/DocumentStitcher');
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($user->nick);
        $token = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();
        $controller->request = new Request([
            'request' => [
                'codes' => [$doc->id()],
                'model' => 'PedidoCliente',
                'multireqtoken' => $token,
                'status' => (string)$statusId,
                'approve_quant_' . $line->id() => '5',
            ],
        ]);

        try {
            $response = new Response();
            $permissions = new ControllerPermissions($user, 'DocumentStitcher');
            $controller->privateCore($response, $user, $permissions);

            $this->assertEquals(8.0, (float)$doc->getLines()[0]->servido, 'line-served-quantity-changed');
            $this->assertEmpty($doc->childrenDocuments(), 'generated-document-with-invalid-quantity');
        } finally {
            // eliminamos
            foreach ($doc->childrenDocuments() as $child) {
                $this->assertTrue($child->delete(), 'can-not-delete-child-document');
            }

            $this->assertTrue($doc->delete(), 'can-not-delete-document');
            $this->assertTrue($user->delete(), 'can-not-delete-user');
            $this->assertTrue($customer->getDefaultAddress()->delete(), 'can-not-delete-address');
            $this->assertTrue($customer->delete(), 'can-not-delete-customer');
        }
    }

    public function testPrivateCoreRejectsPositiveQuantityForNegativeLine(): void
    {
        $customer = $this->getRandomCustomer();
        $this->assertTrue($customer->save(), 'can-not-save-customer');

        $doc = new PedidoCliente();
        $doc->setSubject($customer);
        $this->assertTrue($doc->save(), 'can-not-save-document');

        $line = $doc->getNewLine();
        $line->cantidad = -10;
        $line->servido = -8;
        $line->pvpunitario = 100;
        $this->assertTrue($line->save(), 'can-not-save-line');

        $statusId = 0;
        foreach ($doc->getAvailableStatus() as $status) {
            if (empty($status->generadoc)) {
                continue;
            }

            $statusId = (int)$status->idestado;
            break;
        }
        $this->assertGreaterThan(0, $statusId, 'missing-generating-status');

        $user = $this->getRandomUser();
        $user->admin = true;
        $this->assertTrue($user->save(), 'can-not-save-user');

        $controller = new DocumentStitcher('DocumentStitcher', '/DocumentStitcher');
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($user->nick);
        $token = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();
        $controller->request = new Request([
            'request' => [
                'codes' => [$doc->id()],
                'model' => 'PedidoCliente',
                'multireqtoken' => $token,
                'status' => (string)$statusId,
                'approve_quant_' . $line->id() => '1',
            ],
        ]);

        try {
            $response = new Response();
            $permissions = new ControllerPermissions($user, 'DocumentStitcher');
            $controller->privateCore($response, $user, $permissions);

            $this->assertEquals(-8.0, (float)$doc->getLines()[0]->servido, 'line-served-quantity-changed');
            $this->assertEmpty($doc->childrenDocuments(), 'generated-document-with-invalid-quantity');
        } finally {
            // eliminamos
            foreach ($doc->childrenDocuments() as $child) {
                $this->assertTrue($child->delete(), 'can-not-delete-child-document');
            }

            $this->assertTrue($doc->delete(), 'can-not-delete-document');
            $this->assertTrue($user->delete(), 'can-not-delete-user');
            $this->assertTrue($customer->getDefaultAddress()->delete(), 'can-not-delete-address');
            $this->assertTrue($customer->delete(), 'can-not-delete-customer');
        }
    }

    protected function setUp(): void
    {
        MiniLog::clear();
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
