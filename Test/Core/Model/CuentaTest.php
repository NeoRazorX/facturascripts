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

use FacturaScripts\Core\Base\ToolBox;
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
        $account = $this->getAccount();
        $account->codejercicio = $exercise->codejercicio;
        $this->assertTrue($account->save(), 'account-cant-save');
        $this->assertNotNull($account->primaryColumnValue(), 'account-not-stored');
        $this->assertTrue($account->exists(), 'account-cant-persist');
        $this->assertTrue($account->delete(), 'account-cant-delete');
    }

    public function testCreateClosed()
    {
        $exercise = $this->getExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $exercise->save();

        $account = $this->getAccount();
        $account->codejercicio = $exercise->codejercicio;
        $account->clearExerciseCache();
        try {
            $this->assertFalse($account->save(), 'account-can-create-in-closed-exercise');

            $account->disableAditionalTest(true);
            $this->assertTrue($account->save(), 'account-cant-save');
            $this->assertTrue($account->delete(), 'account-cant-delete');
        } finally {
            // Force restore status and cache. Prevents errors in other tests.
            $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
            $exercise->save();
            $account->clearExerciseCache();
        }
    }

    public function testDeleteClosed()
    {
        $exercise = $this->getExercise();
        $account = $this->getAccount();
        $account->codejercicio = $exercise->codejercicio;
        $this->assertTrue($account->save(), 'account-cant-save');

        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $exercise->save();
        $account->clearExerciseCache();
        try {
            $this->assertFalse($account->delete(), 'account-can-delete-in-closed-exercise');

            $account->disableAditionalTest(true);
            $this->assertTrue($account->delete(), 'account-cant-delete');
        } finally {
            // Force restore status and cache. Prevents errors in other tests.
            $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
            $exercise->save();
            $account->clearExerciseCache();
        }
    }

    public function testExercise()
    {
        /**
         * Al grabar y no coincidir el codejercicio,
         * busca la cuenta padre en el ejercicio de la hija,
         * como no la encuentra retorna un Cuenta "vacío"
         * y Cuenta.test() sobrescribe con valores vacíos los
         * id_parent y cod_parent permitiendo grabar la cuenta hija
         * cuando no debería.
         * 
        $exercise1 = $this->getExercise();
        $start = \strtotime($exercise1->fechainicio . '-1 year');
        $year = \date('Y', $start);

        $exercise2 = new Ejercicio();
        $exercise2->idempresa = $exercise1->idempresa;
        $exercise2->codejercicio = 'TE2';
        $exercise2->nombre = 'Test Exercise 2';
        $exercise2->fechainicio = \date('01-01-' . $year);
        $exercise2->fechafin = \date('31-12-' . $year);
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
        $this->assertTrue($exercise2->delete(), 'exercise-cant-delete');
        *
        */
    }

    public function testLength()
    {
        $exercise = $this->getExercise();

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
    }

    public function testParent()
    {
        $exercise = $this->getExercise();
        $account = $this->getAccount();
        $account->codejercicio = $exercise->codejercicio;
        $account->parent_codcuenta = $account->codcuenta;
        $this->assertTrue($account->save(), 'account-cant-save');
        $this->assertFalse($account->parent_codcuenta == $account->codcuenta, 'parent-account-can-be-same-account-code');

        $account->parent_idcuenta = $account->idcuenta;
        $this->assertTrue($account->save(), 'account-cant-save');
        $this->assertFalse($account->parent_idcuenta == $account->idcuenta, 'parent-account-can-be-same-account-id');

        $this->assertTrue($account->delete(), 'account-cant-delete');
    }

    protected function tearDown()
    {
        $this->logErrors();
    }

    private function getAccount()
    {
        $account = new Cuenta();
        $account->codcuenta = 'Test';
        $account->descripcion = 'Test Account';
        return $account;
    }

    private function getExercise()
    {
        $exercise = new Ejercicio();
        $exercise->idempresa = $this->toolBox()->appSettings()->get('default', 'idempresa');
        $exercise->loadFromDate(\date(Ejercicio::DATE_STYLE), true, false);
        return $exercise;
    }

    private function toolBox()
    {
        return new ToolBox();
    }
}
