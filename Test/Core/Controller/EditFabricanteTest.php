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
use FacturaScripts\Core\Controller\EditFabricante;
use FacturaScripts\Core\Request;
use FacturaScripts\Dinamic\Model\Fabricante;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class EditFabricanteTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testProductActionsRequireUpdatePermissionAndToken(): void
    {
        $manufacturer = new Fabricante();
        $manufacturer->codfabricante = 'TEF' . mt_rand(100, 999);
        $manufacturer->nombre = 'Test EditFabricante';
        $this->assertTrue($manufacturer->save());

        $product = $this->getRandomProduct();
        $this->assertTrue($product->save());

        try {
            // sin permiso de actualización no se asigna el producto
            $this->runAction('addProductAction', $manufacturer->codfabricante, [$product->idproducto], false, true);
            $this->assertNull($this->reload($product)->codfabricante);

            // con permiso, pero sin token válido, tampoco
            $this->runAction('addProductAction', $manufacturer->codfabricante, [$product->idproducto], true, false);
            $this->assertNull($this->reload($product)->codfabricante);

            // con permiso y token se asigna
            $this->runAction('addProductAction', $manufacturer->codfabricante, [$product->idproducto], true, true);
            $this->assertSame($manufacturer->codfabricante, $this->reload($product)->codfabricante);

            // sin permiso de actualización no se desasigna
            $this->runAction('removeProductAction', $manufacturer->codfabricante, [$product->idproducto], false, true);
            $this->assertSame($manufacturer->codfabricante, $this->reload($product)->codfabricante);

            // con permiso y token se desasigna
            $this->runAction('removeProductAction', $manufacturer->codfabricante, [$product->idproducto], true, true);
            $this->assertNull($this->reload($product)->codfabricante);
        } finally {
            $product->delete();
            $manufacturer->delete();
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

    private function reload(Producto $product): Producto
    {
        $reloaded = new Producto();
        $this->assertTrue($reloaded->load($product->idproducto));
        return $reloaded;
    }

    private function runAction(string $method, string $code, array $codes, bool $allowUpdate, bool $validToken): void
    {
        $controller = new TestableEditFabricante('EditFabricante', '/EditFabricante');

        $user = new User();
        $user->nick = 'test-edit-fabricante';
        $controller->user = $user;

        $controller->permissions = new ControllerPermissions();
        $controller->permissions->set(true, 1, false, $allowUpdate);

        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($user->nick);
        $token = $validToken ? $controller->multiRequestProtection->newToken() : 'invalid-token';

        $controller->request = new Request([
            'query' => ['code' => $code],
            'request' => ['codes' => $codes, 'multireqtoken' => $token],
        ]);
        $controller->runAction($method);
    }
}

final class TestableEditFabricante extends EditFabricante
{
    public function runAction(string $method): void
    {
        $this->{$method}();
    }
}
