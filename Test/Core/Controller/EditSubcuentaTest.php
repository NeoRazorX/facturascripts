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
use FacturaScripts\Core\Controller\EditSubcuenta;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Asiento;
use FacturaScripts\Dinamic\Model\Partida;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class EditSubcuentaTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    /** @var User */
    private $user;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testDotAccountingRequiresUpdatePermissionTokenAndSubaccount(): void
    {
        // creamos un asiento con una partida en cada una de dos subcuentas
        $asiento = new Asiento();
        $asiento->concepto = 'EditSubcuentaTest';
        $this->assertTrue($asiento->save(), 'can-not-save-asiento');

        $subaccounts = Subcuenta::all([Where::eq('codejercicio', $asiento->codejercicio)], [], 0, 2);
        $this->assertCount(2, $subaccounts, 'not-enough-subaccounts');

        $line1 = $this->newLine($asiento, $subaccounts[0], 100, 0);
        $line2 = $this->newLine($asiento, $subaccounts[1], 0, 100);
        $codes = [$line1->idpartida, $line2->idpartida];

        try {
            // sin permiso de modificación no se puntea nada
            $this->runController($subaccounts[0], $codes, false, true);
            $this->assertFalse($this->reload($line1)->punteada, 'dotted-without-update-permission');

            // con un token inválido tampoco
            $this->runController($subaccounts[0], $codes, true, false);
            $this->assertFalse($this->reload($line1)->punteada, 'dotted-with-invalid-token');

            // con permiso y token solo se puntea la partida de la subcuenta editada
            $this->runController($subaccounts[0], $codes, true, true);
            $this->assertTrue($this->reload($line1)->punteada, 'line-not-dotted');
            $this->assertFalse($this->reload($line2)->punteada, 'other-subaccount-line-dotted');
        } finally {
            $asiento->delete();
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

    private function newLine(Asiento $asiento, Subcuenta $subaccount, float $debe, float $haber): Partida
    {
        $line = $asiento->getNewLine();
        $line->setAccount($subaccount);
        $line->debe = $debe;
        $line->haber = $haber;
        $this->assertTrue($line->save(), 'can-not-save-partida');
        return $line;
    }

    private function reload(Partida $line): Partida
    {
        $reloaded = new Partida();
        $this->assertTrue($reloaded->load($line->idpartida));
        return $reloaded;
    }

    private function runController(Subcuenta $subaccount, array $codes, bool $allowUpdate, bool $validToken): void
    {
        $controller = new EditSubcuenta('EditSubcuenta', '/EditSubcuenta');
        $controller->multiRequestProtection->clearSeed();
        $controller->multiRequestProtection->addSeed($this->user->nick);
        $token = $controller->multiRequestProtection->newToken();
        $controller->multiRequestProtection->clearSeed();

        $controller->request = new Request([
            'query' => ['code' => $subaccount->idsubcuenta],
            'request' => [
                'action' => 'dot-accounting-on',
                'codes' => $codes,
                'multireqtoken' => $validToken ? $token : 'invalid-token',
            ],
        ]);

        $permissions = new ControllerPermissions();
        $permissions->set(true, 99, true, $allowUpdate);

        $response = new Response();
        $response->disableSend(true);
        $controller->privateCore($response, $this->user, $permissions);
    }
}
