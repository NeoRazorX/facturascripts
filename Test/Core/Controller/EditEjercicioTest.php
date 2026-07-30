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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Controller\EditEjercicio;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Retencion;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class EditEjercicioTest extends TestCase
{
    use LogErrorsTrait;

    public function testConfiguredSubaccountLengthWarning(): void
    {
        $retention = new Retencion();
        $retention->codretencion = 'LEN' . mt_rand(1000, 9999);
        $retention->descripcion = 'Test subaccount length';
        $retention->porcentaje = 15;
        $retention->codsubcuentaret = '4750001';
        $retention->codsubcuentaacr = '4750000001';
        $this->assertTrue($retention->save());

        try {
            $controller = new TestableEditEjercicio('EditEjercicio', '/EditEjercicio');
            $where = [Where::eq('codretencion', $retention->codretencion)];

            $this->assertSame(1, $controller->countWrongLengthSubaccounts(
                Retencion::class,
                ['codsubcuentaret', 'codsubcuentaacr'],
                10,
                $where
            ));
            $this->assertSame(1, $controller->countWrongLengthSubaccounts(
                Retencion::class,
                ['codsubcuentaret', 'codsubcuentaacr'],
                7,
                $where
            ));

            MiniLog::clear();
            $controller->checkConfiguredSubaccountLengths(10, 0);
            $warnings = MiniLog::read('', ['warning']);
            $this->assertNotEmpty(array_filter($warnings, static function (array $item): bool {
                return $item['original'] === 'configured-subaccounts-wrong-length';
            }));
        } finally {
            $retention->delete();
        }
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}

final class TestableEditEjercicio extends EditEjercicio
{
    public function checkConfiguredSubaccountLengths(int $length, int $idempresa): void
    {
        parent::checkConfiguredSubaccountLengths($length, $idempresa);
    }

    public function countWrongLengthSubaccounts(
        string $modelClass,
        array $fields,
        int $length,
        array $where = []
    ): int {
        return parent::countWrongLengthSubaccounts($modelClass, $fields, $length, $where);
    }
}
