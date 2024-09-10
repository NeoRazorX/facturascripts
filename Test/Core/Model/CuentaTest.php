<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Cuenta;
use FacturaScripts\Core\Model\Ejercicio;
use FacturaScripts\Test\Traits\RandomDataTrait;
use PHPUnit\Framework\TestCase;

final class CuentaTest extends TestCase
{
    use RandomDataTrait;

    public static function setUpBeforeClass(): void
    {
        $account = new Cuenta();
        $where = [new DataBaseWhere('codcuenta', '9999')];
        if ($account->loadFromCode('', $where)) {
            $account->delete();
        }
    }

    public function testCreate()
    {
        // creamos una cuenta
        $account = new Cuenta();
        $account->codcuenta = '9999';
        $account->codejercicio = $this->getRandomExercise()->codejercicio;
        $account->descripcion = 'Test';
        $this->assertTrue($account->save(), 'can-not-create-account');

        // comprobamos que persiste en la base de datos
        $this->assertTrue($account->exists(), 'account-cant-persist');

        // eliminamos
        $this->assertTrue($account->delete(), 'account-cant-delete');
    }

    public function testCreateBadCode()
    {
        // creamos una cuenta con un código no válido
        $account = new Cuenta();
        $account->codcuenta = 'test';
        $account->codejercicio = $this->getRandomExercise()->codejercicio;
        $account->descripcion = 'Test';

        // comprobamos si se guarda
        $save = $account->save();
        $this->assertFalse($save, 'can-create-bad-code-account');

        // eliminamos en caso de fallo
        if ($save) {
            $account->delete();
        }
    }

    public function testHtmlOnDescription()
    {
        $account = new Cuenta();
        $account->codcuenta = '9999';
        $account->codejercicio = $this->getRandomExercise()->codejercicio;
        $account->descripcion = '<b>Test</b>';
        $this->assertTrue($account->save(), 'can-not-create-account');

        // comprobamos que el html se ha escapado
        $this->assertEquals('&lt;b&gt;Test&lt;/b&gt;', $account->descripcion);

        // eliminamos
        $this->assertTrue($account->delete(), 'account-cant-delete');
    }

    public function testCreateClosed()
    {
        // cerramos un ejercicio
        $exercise = $this->getRandomExercise();
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $exercise->save();

        // creamos una cuenta
        $account = $this->getRandomAccount($exercise->codejercicio);

        try {
            // guardamos la cuenta
            $this->assertFalse($account->save(), 'account-can-create-in-closed-exercise');

            // desactivamos las comprobaciones y guardamos
            $account->disableAdditionalTest(true);
            $this->assertTrue($account->save(), 'account-cant-save');

            // eliminamos la cuenta
            $this->assertTrue($account->delete(), 'account-cant-delete');
        } finally {
            // reabrimos el ejercicio
            $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
            $exercise->save();
        }
    }

    public function testDeleteClosed()
    {
        $exercise = $this->getRandomExercise();

        // creamos una cuenta
        $account = $this->getRandomAccount($exercise->codejercicio);
        $this->assertTrue($account->save(), 'account-cant-save');

        // cerramos el ejercicio
        $exercise->estado = Ejercicio::EXERCISE_STATUS_CLOSED;
        $exercise->save();

        try {
            // eliminamos la cuenta
            $this->assertFalse($account->delete(), 'account-can-delete-in-closed-exercise');

            // desactivamos las comprobaciones y eliminamos
            $account->disableAdditionalTest(true);
            $this->assertTrue($account->delete(), 'account-cant-delete');
        } finally {
            // reabrimos el ejercicio
            $exercise->estado = Ejercicio::EXERCISE_STATUS_OPEN;
            $exercise->save();
        }
    }

    public function testCreateChild()
    {
        $exercise = $this->getRandomExercise();

        // creamos una cuenta
        $account1 = $this->getRandomAccount($exercise->codejercicio);
        $this->assertTrue($account1->save(), 'account-cant-save');

        // creamos una cuenta hija
        $account2 = $this->getRandomAccount($exercise->codejercicio);
        $account2->codcuenta = $account1->codcuenta . '9';
        $account2->parent_idcuenta = $account1->idcuenta;
        $account2->parent_codcuenta = $account1->codcuenta;
        $this->assertTrue($account2->save(), 'account-cant-longer-parent');

        // eliminamos
        $this->assertTrue($account1->delete(), 'account-cant-delete');
        $this->assertTrue($account2->delete(), 'account-cant-delete');
    }

    public function createBadChild()
    {
        $exercise = $this->getRandomExercise();

        // creamos una cuenta
        $account1 = $this->getRandomAccount($exercise->codejercicio);
        $this->assertTrue($account1->save(), 'account-cant-save');

        // creamos una cuenta hija
        $account2 = $this->getRandomAccount($exercise->codejercicio);
        $account2->codcuenta = substr($account1->codcuenta, 0, -1);
        $account2->parent_idcuenta = $account1->idcuenta;
        $account2->parent_codcuenta = $account1->codcuenta;
        $this->assertFalse($account2->save(), 'account-cant-longer-parent');

        // eliminamos
        $this->assertTrue($account1->delete(), 'account-cant-delete');
    }
}
