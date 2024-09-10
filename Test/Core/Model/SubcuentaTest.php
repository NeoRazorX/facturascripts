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

use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Core\Model\Subcuenta;
use FacturaScripts\Test\Traits\LogErrorsTrait;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class SubcuentaTest extends TestCase
{
    use LogErrorsTrait;
    use RandomDataTrait;

    public function testCreate()
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
        $this->assertTrue($exercise->save(), 'cant-save-exercise');

        // creamos una cuenta
        $account = $this->getRandomAccount($exercise->codejercicio);
        $this->assertTrue($account->save(), 'cant-save-account');

        // creamos una subcuenta
        $subaccount = new Subcuenta();
        $subaccount->codcuenta = $account->codcuenta;
        $subaccount->codejercicio = $exercise->codejercicio;
        $subaccount->codsubcuenta = $account->codcuenta . '.1';
        $subaccount->descripcion = 'Test';
        $this->assertTrue($subaccount->save(), 'cant-save-subaccount');

        // comprobamos que persiste en la base de datos
        $this->assertTrue($subaccount->exists(), 'subaccount-cant-persist');

        // eliminamos
        $this->assertTrue($subaccount->delete(), 'subaccount-cant-delete');
        $this->assertTrue($account->delete(), 'account-cant-delete');
    }

    public function testCreateBadCode()
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
        $this->assertTrue($exercise->save(), 'cant-save-exercise-2');

        // creamos una cuenta
        $account = $this->getRandomAccount($exercise->codejercicio);
        $this->assertTrue($account->save(), 'cant-save-account-2');

        // creamos una subcuenta
        $subaccount = new Subcuenta();
        $subaccount->codcuenta = $account->codcuenta;
        $subaccount->codejercicio = $exercise->codejercicio;
        $subaccount->codsubcuenta = 'test';
        $subaccount->descripcion = 'Test';
        $this->assertFalse($subaccount->save(), 'can-save-subaccount-bad-code');

        // eliminamos la cuenta
        $this->assertTrue($account->delete(), 'account-cant-delete-2');
    }

    public function testHtmlOnDescription()
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
        $this->assertTrue($exercise->save(), 'cant-save-exercise-3');

        // creamos una cuenta
        $account = $this->getRandomAccount($exercise->codejercicio);
        $this->assertTrue($account->save(), 'cant-save-account-3');

        // creamos una subcuenta
        $subaccount = new Subcuenta();
        $subaccount->codcuenta = $account->codcuenta;
        $subaccount->codejercicio = $exercise->codejercicio;
        $subaccount->codsubcuenta = $account->codcuenta . '.1';
        $subaccount->descripcion = '<b>Test</b>';
        $this->assertTrue($subaccount->save(), 'cant-save-subaccount-3');

        // comprobamos que el html se ha escapado
        $this->assertEquals('&lt;b&gt;Test&lt;/b&gt;', $subaccount->descripcion);

        // eliminamos
        $this->assertTrue($subaccount->delete(), 'subaccount-cant-delete-3');
        $this->assertTrue($account->delete(), 'account-cant-delete-3');
    }

    public function testCreateOnClosedExercise()
    {
        // obtenemos un ejercicio
        $exercise = $this->getRandomExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'cant-save-exercise-4');

        // creamos una cuenta
        $account = $this->getRandomAccount($exercise->codejercicio);
        $this->assertTrue($account->save(), 'cant-save-account-4');

        // cerramos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $this->assertTrue($exercise->save(), 'cant-close-exercise-4');

        // creamos una subcuenta
        $subaccount = new Subcuenta();
        $subaccount->codcuenta = $account->codcuenta;
        $subaccount->codejercicio = $exercise->codejercicio;
        $subaccount->codsubcuenta = $account->codcuenta . '.1';
        $subaccount->descripcion = 'Test';
        $this->assertFalse($subaccount->save(), 'can-save-subaccount-bad-code-4');

        // reabrimos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
        $this->assertTrue($exercise->save(), 'cant-open-exercise-4');

        // eliminamos la cuenta
        $this->assertTrue($account->delete(), 'account-cant-delete-4');
    }

    protected function tearDown(): void
    {
        $this->logErrors();
    }
}
