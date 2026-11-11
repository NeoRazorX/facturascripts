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

namespace FacturaScripts\Test\Core\Template;

use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Partida;
use FacturaScripts\Core\Template\JoinModel;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class JoinModelTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testCountWithGroupBy(): void
    {
        // creamos dos asientos, el primero con tres partidas y el segundo con dos
        $asiento1 = $this->createAsiento(3);
        $asiento2 = $this->createAsiento(2);

        // sin agrupar, count() devuelve el número de partidas
        $join = $this->getJoinModel(false);
        $this->assertEquals(3, $join::count([Where::eq('partidas.idasiento', $asiento1->idasiento)]));
        $this->assertEquals(5, $join::count([
            Where::in('partidas.idasiento', [$asiento1->idasiento, $asiento2->idasiento])
        ]));

        // agrupando por asiento, count() devuelve el número de grupos, también si solo hay uno
        $groupJoin = $this->getJoinModel(true);
        $this->assertEquals(1, $groupJoin::count([Where::eq('partidas.idasiento', $asiento1->idasiento)]));
        $this->assertEquals(2, $groupJoin::count([
            Where::in('partidas.idasiento', [$asiento1->idasiento, $asiento2->idasiento])
        ]));
        $this->assertEquals(0, $groupJoin::count([Where::eq('partidas.idasiento', -1)]));

        // el número de grupos coincide con el de registros que devuelve all()
        $where = [Where::eq('partidas.idasiento', $asiento1->idasiento)];
        $this->assertCount($groupJoin::count($where), $groupJoin::all($where));

        // eliminamos
        $this->assertTrue($asiento1->delete(), 'asiento-cant-delete');
        $this->assertTrue($asiento2->delete(), 'asiento-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function createAsiento(int $lines): Asiento
    {
        $asiento = new Asiento();
        $asiento->concepto = 'Test JoinModel count';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');

        $codsubcuenta = $this->getSampleSubaccount($asiento->getExercise());
        for ($num = 0; $num < $lines; $num++) {
            $partida = new Partida();
            $partida->idasiento = $asiento->idasiento;
            $partida->codsubcuenta = $codsubcuenta;
            $partida->concepto = 'Test JoinModel count';
            $partida->debe = 10;
            $this->assertTrue($partida->save(), 'partida-cant-save');
        }

        return $asiento;
    }

    private function getJoinModel(bool $grouped): JoinModel
    {
        if ($grouped) {
            // agrupa las partidas por asiento
            return new class extends JoinModel {
                protected function getFields(): array
                {
                    return [
                        'idasiento' => 'partidas.idasiento',
                        'debe' => 'SUM(partidas.debe)',
                    ];
                }

                protected function getGroupFields(): string
                {
                    return 'partidas.idasiento';
                }

                protected function getSQLFrom(): string
                {
                    return 'partidas INNER JOIN asientos ON asientos.idasiento = partidas.idasiento';
                }

                protected function getTables(): array
                {
                    return ['asientos', 'partidas'];
                }
            };
        }

        return new class extends JoinModel {
            protected function getFields(): array
            {
                return [
                    'idasiento' => 'partidas.idasiento',
                    'idpartida' => 'partidas.idpartida',
                ];
            }

            protected function getSQLFrom(): string
            {
                return 'partidas INNER JOIN asientos ON asientos.idasiento = partidas.idasiento';
            }

            protected function getTables(): array
            {
                return ['asientos', 'partidas'];
            }
        };
    }

    private function getSampleSubaccount(Ejercicio $eje): ?string
    {
        $where = [Where::eq('codejercicio', $eje->codejercicio)];
        foreach (Subcuenta::all($where) as $item) {
            return $item->codsubcuenta;
        }

        return null;
    }
}
