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

namespace FacturaScripts\Test\Core\Model\Base;

use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Base\JoinModel;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Partida;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class JoinModelTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testDeleteLoadsMasterModel(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test JoinModel';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');

        // creamos una partida
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($asiento->getExercise());
        $partida->concepto = 'Test JoinModel';
        $partida->debe = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save');

        // bloqueamos el asiento
        $asiento->editable = false;
        $this->assertTrue($asiento->save(), 'asiento-cant-lock');

        // cargamos la partida desde el join
        $join = $this->getJoinModel();
        $this->assertTrue($join->loadFromCode($partida->idpartida), 'join-cant-load');

        // el borrado debe respetar las comprobaciones de Partida::delete()
        $this->assertFalse($join->delete(), 'join-can-delete-on-locked-entry');
        $this->assertTrue($partida->exists(), 'partida-deleted-on-locked-entry');

        // desbloqueamos el asiento
        $asiento->editable = true;
        $this->assertTrue($asiento->save(), 'asiento-cant-unlock');

        // ahora sí se puede eliminar desde el join
        $this->assertTrue($join->delete(), 'join-cant-delete');
        $this->assertFalse($partida->exists(), 'partida-still-exists');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }

    private function getJoinModel(): JoinModel
    {
        // el core no tiene joins sobre esta clase, así que creamos uno de partidas y asientos
        return new class extends JoinModel {
            public function __construct(array $data = [])
            {
                parent::__construct($data);
                $this->setMasterModel(new Partida());
            }

            protected function getFields(): array
            {
                return [
                    'concepto' => 'partidas.concepto',
                    'idasiento' => 'partidas.idasiento',
                    'idpartida' => 'partidas.idpartida',
                ];
            }

            protected function getSQLFrom(): string
            {
                return 'partidas LEFT JOIN asientos ON asientos.idasiento = partidas.idasiento';
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
