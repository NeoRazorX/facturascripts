<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022  Carlos Garcia Gomez     <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Test\Core\LogErrorsTrait;
use PHPUnit\Framework\TestCase;

final class CuentaTest extends TestCase
{
    use LogErrorsTrait;

    public function testCreate()
    {
        $exercise = $this->getExercise();
        $this->assertTrue($exercise->save(), 'exercise-cant-save');

        $account = $this->getAccount($exercise);
        $this->assertTrue($account->save(), 'account-cant-save');
        $this->assertNotNull($account->primaryColumnValue(), 'account-not-stored');
        $this->assertTrue($account->exists(), 'account-cant-persist');
        $this->assertTrue($account->delete(), 'account-cant-delete');
        $this->assertTrue($exercise->delete(), 'exercise-cant-delete');
    }

    public function testCreateClosed()
    {
        $exercise = $this->getExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $this->assertTrue($exercise->save(), 'exercise-cant-save');

        $account = $this->getAccount($exercise);
        $this->assertFalse($account->save(), 'account-can-create-in-closed-exercise');

        $account->disableAditionalTest(true);
        $this->assertTrue($account->save(), 'account-cant-save');
        $this->assertTrue($account->delete(), 'account-cant-delete');
        $this->assertTrue($exercise->delete(), 'exercise-cant-delete');
    }

    public function testDeleteClosed()
    {
        $exercise = $this->getExercise();
        $this->assertTrue($exercise->save(), 'exercise-cant-save');

        $account = $this->getAccount($exercise);
        $this->assertTrue($account->save(), 'account-can-create-in-closed-exercise');

        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $this->assertTrue($exercise->save(), 'exercise-cant-save');
        $this->assertFalse($account->delete(), 'account-can-delete-in-closed-exercise');

        $account->disableAditionalTest(true);
        $this->assertTrue($account->delete(), 'account-cant-delete');
        $this->assertTrue($exercise->delete(), 'exercise-cant-delete');
    }

    public function testExercise()
    {
        $exercise1 = new Ejercicio();
        $exercise1->codejercicio = 'Test1';
        $exercise1->nombre = 'Test Exercise 1';
        $this->assertTrue($exercise1->save(), 'exercise-cant-save');

        $exercise2 = new Ejercicio();
        $exercise2->codejercicio = 'Test2';
        $exercise2->nombre = 'Test Exercise 2';
        $this->assertTrue($exercise2->save(), 'exercise-cant-save');

        $account1 = new Cuenta();
        $account1->codcuenta = 'Test1';
        $account1->descripcion = 'Test Account 1';
        $account1->codejercicio = $exercise1->codejercicio;
        $this->assertTrue($account1->save(), 'account-cant-save');

        $account2 = new Cuenta();
        $account2->codcuenta = 'Test12';
        $account2->descripcion = 'Test Account 2';
        $account2->codejercicio = $exercise2->codejercicio;
        $account2->parent_idcuenta = $account1->idcuenta;
        $this->assertFalse($account2->save(), 'account-different-parent-exercise');

        $this->assertTrue($account1->delete(), 'account-cant-delete');
        $this->assertTrue($exercise1->delete(), 'exercise-cant-delete');
        $this->assertTrue($exercise2->delete(), 'exercise-cant-delete');
    }

    public function testLength()
    {
        $exercise = $this->getExercise();
        $this->assertTrue($exercise->save(), 'exercise-cant-save');

        $account1 = new Cuenta();
        $account1->codcuenta = 'Test1';
        $account1->descripcion = 'Test Account 1';
        $account1->codejercicio = $exercise->codejercicio;
        $this->assertTrue($account1->save(), 'account-cant-save');

        $account2 = new Cuenta();
        $account2->codcuenta = 'T2';
        $account2->descripcion = 'Test Account 2';
        $account2->codejercicio = $exercise->codejercicio;
        $account2->parent_idcuenta = $account1->idcuenta;
        $account2->parent_codcuenta = $account1->codcuenta;
        $this->assertFalse($account2->save(), 'account-cant-longer-parent');

        $account2->codcuenta = 'Test2';
        $this->assertFalse($account2->save(), 'account-cant-equal-parent');

        $this->assertTrue($account1->delete(), 'account-cant-delete');
        $this->assertTrue($exercise->delete(), 'exercise-cant-delete');
    }

    public function testParent()
    {
        $exercise = $this->getExercise();
        $this->assertTrue($exercise->save(), 'exercise-cant-save');

        $account = $this->getAccount($exercise);
        $this->assertTrue($account->save(), 'account-cant-save');

        $account->parent_idcuenta = $account->idcuenta;
        $this->assertFalse($account->save(), 'parent-account-can-be-same-account-id');
        $this->assertTrue($account->delete(), 'account-cant-delete');
        $this->assertTrue($exercise->delete(), 'exercise-cant-delete');
    }

    protected function tearDown()
    {
        $this->logErrors();
    }

    /**
     *
     * @param Ejercicio $exercise
     * @return Cuenta
     */
    private function getAccount(&$exercise)
    {
        $account = new Cuenta();
        $account->codcuenta = 'Test';
        $account->descripcion = 'Test Account';
        $account->codejercicio = $exercise->codejercicio;
        return $account;
    }

    /**
     *
     * @return Ejercicio
     */
    private function getExercise()
    {
        $exercise = new Ejercicio();
        $exercise->codejercicio = 'Test';
        $exercise->nombre = 'Test Exercise';
        return $exercise;
    }
}
