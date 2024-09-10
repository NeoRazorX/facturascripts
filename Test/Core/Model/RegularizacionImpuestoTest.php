<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Test\Core\Model;

use FacturaScripts\Core\DataSrc\Ejercicios;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\RegularizacionImpuesto;
use FacturaScripts\Test\Traits\DefaultSettingsTrait;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class RegularizacionImpuestoTest extends TestCase
{
    use DefaultSettingsTrait;
    use LogErrorsTrait;
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        self::setDefaultSettings();
        self::installAccountingPlan();
    }

    public function testCreate()
    {
        // creamos una regularización
        $reg = new RegularizacionImpuesto();
        $this->assertTrue($reg->save());

        // comprobamos que existe
        $this->assertTrue($reg->exists());

        // eliminamos la regularización
        $this->assertTrue($reg->delete());
    }

    public function testAnotherCompany()
    {
        // creamos una nueva empresa
        $empresa = $this->getRandomCompany();
        $this->assertTrue($empresa->save());

        // creamos un ejercicio para la nueva empresa
        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = $ejercicio->nombre = 'T001';
        $ejercicio->idempresa = $empresa->idempresa;
        $this->assertTrue($ejercicio->save());

        // creamos una regularización
        $reg = new RegularizacionImpuesto();
        $reg->codejercicio = $ejercicio->codejercicio;
        $reg->idempresa = $empresa->idempresa;
        $this->assertTrue($reg->save());

        // comprobamos que existe
        $this->assertTrue($reg->exists());

        // eliminamos
        $this->assertTrue($reg->delete());
        $this->assertTrue($ejercicio->delete());
        $this->assertTrue($empresa->delete());
    }

    public function testDifferentCompanyAndExercise()
    {
        // creamos una nueva empresa
        $empresa = $this->getRandomCompany();
        $this->assertTrue($empresa->save());

        // creamos una regularización
        $reg = new RegularizacionImpuesto();
        $reg->idempresa = $empresa->idempresa;

        // asignamos un ejercicio diferente
        foreach (Ejercicios::all() as $ejercicio) {
            if ($ejercicio->idempresa !== $empresa->idempresa) {
                $reg->codejercicio = $ejercicio->codejercicio;
                break;
            }
        }

        // comprobamos que no se puede guardar
        $this->assertFalse($reg->save());

        // eliminamos
        $this->assertTrue($empresa->delete());
    }

    public function testCreateOnClosedExercise()
    {
        // creamos una empresa
        $empresa = $this->getRandomCompany();
        $this->assertTrue($empresa->save());

        // creamos un ejercicio para la empresa
        $ejercicio = new Ejercicio();
        $ejercicio->codejercicio = $ejercicio->nombre = 'T002';
        $ejercicio->idempresa = $empresa->idempresa;
        $this->assertTrue($ejercicio->save());

        // cerramos el ejercicio
        $ejercicio->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $this->assertTrue($ejercicio->save());

        // creamos una regularización
        $reg = new RegularizacionImpuesto();
        $reg->codejercicio = $ejercicio->codejercicio;
        $reg->idempresa = $empresa->idempresa;
        $this->assertFalse($reg->save());

        // eliminamos
        $this->assertTrue($reg->delete());
        $this->assertTrue($ejercicio->delete());
        $this->assertTrue($empresa->delete());
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
