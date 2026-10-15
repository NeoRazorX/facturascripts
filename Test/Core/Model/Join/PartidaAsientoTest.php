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

namespace FacturaScripts\Test\Core\Model\Join;

use FacturaScripts\Core\Model\Asiento;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Join\PartidaAsiento;
use FacturaScripts\Core\Model\Partida;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Subcuenta;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class PartidaAsientoTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testCantDeleteOnLockedEntry(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test PartidaAsiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');

        // creamos una partida
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($asiento->getExercise());
        $partida->concepto = 'Test PartidaAsiento';
        $partida->debe = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save');

        // bloqueamos el asiento
        $asiento->editable = false;
        $this->assertTrue($asiento->save(), 'asiento-cant-lock');

        // cargamos la partida desde el join
        $join = new PartidaAsiento();
        $this->assertTrue($join->load($partida->idpartida), 'partida-asiento-cant-load');

        // el borrado debe respetar las comprobaciones de Partida::delete()
        $this->assertFalse($join->delete(), 'partida-asiento-can-delete-on-locked-entry');
        $this->assertTrue($partida->exists(), 'partida-deleted-on-locked-entry');

        // desbloqueamos el asiento
        $asiento->editable = true;
        $this->assertTrue($asiento->save(), 'asiento-cant-unlock');

        // ahora sí se puede eliminar desde el join
        $this->assertTrue($join->delete(), 'partida-asiento-cant-delete');
        $this->assertFalse($partida->exists(), 'partida-still-exists');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    public function testDeleteMissingMasterKeepsPrimaryKey(): void
    {
        // apuntamos a una partida que no existe
        $join = $this->getRecordingJoin();
        $join->idpartida = 99999999;

        // no se puede cargar el master, pero debe recibir la clave igualmente
        $this->assertTrue($join->delete(), 'partida-asiento-cant-delete-missing');
        $this->assertEquals(99999999, $join->deleted()['idpartida'], 'master-without-primary-key');
    }

    public function testDeleteWithEmptyKeyDoesNotReusePreviousMaster(): void
    {
        // creamos el asiento
        $asiento = new Asiento();
        $asiento->concepto = 'Test PartidaAsiento';
        $this->assertTrue($asiento->save(), 'asiento-cant-save');

        // creamos una partida
        $partida = new Partida();
        $partida->idasiento = $asiento->idasiento;
        $partida->codsubcuenta = $this->getSampleSubaccount($asiento->getExercise());
        $partida->concepto = 'Test PartidaAsiento';
        $partida->debe = 100;
        $this->assertTrue($partida->save(), 'partida-cant-save');

        // la eliminamos desde el join, que carga el master con sus datos
        $join = $this->getRecordingJoin();
        $this->assertTrue($join->load($partida->idpartida), 'partida-asiento-cant-load');
        $this->assertTrue($join->delete(), 'partida-asiento-cant-delete');
        $this->assertEquals($asiento->idasiento, $join->deleted()['idasiento'], 'master-not-loaded');

        // con el mismo join y sin clave, el master no debe conservar los datos de la partida anterior
        $join->idpartida = null;
        $join->delete();
        $this->assertNull($join->deleted()['idasiento'], 'master-keeps-previous-data');

        // eliminamos
        $this->assertTrue($asiento->delete(), 'asiento-cant-delete');
    }

    private function getRecordingJoin(): PartidaAsiento
    {
        // join cuyo modelo master guarda los datos con los que se llama a su delete()
        return new class extends PartidaAsiento {
            public function __construct(array $data = [])
            {
                parent::__construct($data);
                $this->setMasterModel(new class extends Partida {
                    public static $deleted = [];

                    public function delete(): bool
                    {
                        self::$deleted = ['idasiento' => $this->idasiento, 'idpartida' => $this->id()];
                        return parent::delete();
                    }
                });
            }

            public function deleted(): array
            {
                return $this->masterModel::$deleted;
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

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
