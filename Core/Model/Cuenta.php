<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\CuentaEspecial as DinCuentaEspecial;
use FacturaScripts\Dinamic\Model\Ejercicio as DinEjercicio;
use FacturaScripts\Dinamic\Model\Subcuenta as DinSubcuenta;

/**
 * First level of the accounting plan.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Cuenta extends Base\ModelClass
{
    use Base\ModelTrait;
    use Base\ExerciseRelationTrait;

    /** @var string */
    public $codcuenta;

    /** @var string */
    public $codcuentaesp;

    /** @var float */
    public $debe;

    /** @var string */
    public $descripcion;

    /** @var bool */
    private $disableAdditionalTest = false;

    /** @var float */
    public $haber;

    /** @var int */
    public $idcuenta;

    /** @var string */
    public $parent_codcuenta;

    /** @var int */
    public $parent_idcuenta;

    /** @var float */
    public $saldo;

    public function clear()
    {
        parent::clear();
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->saldo = 0.0;
    }

    public function createSubcuenta(string $codsubcuenta, string $description): Subcuenta
    {
        $subcuenta = new DinSubcuenta();
        $subcuenta->codcuenta = $this->codcuenta;
        $subcuenta->codejercicio = $this->codejercicio;
        $subcuenta->codsubcuenta = $codsubcuenta;
        $subcuenta->descripcion = $description;
        $subcuenta->idcuenta = $this->idcuenta;
        $subcuenta->save();

        return $subcuenta;
    }

    public function delete(): bool
    {
        if ($this->getExercise()->isOpened() || $this->disableAdditionalTest) {
            return parent::delete();
        }

        Tools::log()->warning('closed-exercise', ['%exerciseName%' => $this->getExercise()->nombre]);
        return false;
    }

    public function disableAdditionalTest(bool $value): void
    {
        $this->disableAdditionalTest = $value;
    }

    /**
     * Returns all children accounts for this account.
     *
     * @return static[]
     */
    public function getChildren(): array
    {
        $where = [new DataBaseWhere('parent_idcuenta', $this->idcuenta)];
        return $this->all($where, ['codcuenta' => 'ASC'], 0, 0);
    }

    public function getFreeSubjectAccountCode($subject): string
    {
        // nos quedamos solamente con los números del código
        $code = preg_replace('/[^0-9]/', '', $subject->primaryColumnValue());
        if (strlen($code) === $this->getExercise()->longsubcuenta) {
            // si el código ya tiene la longitud de una subcuenta, lo usamos como subcuenta
            return $code;
        }

        // conformamos un array con el número del cliente, los 99 primeros números y un número aleatorio
        $numbers = array_merge(
            [$code],
            range(1, 99),
            [rand(100, 9999)]
        );

        // añadimos también los 100 siguientes números al total de subcuentas
        $subcuenta = new Subcuenta();
        $whereTotal = [
            new DataBaseWhere('codcuenta', $this->codcuenta),
            new DataBaseWhere('codejercicio', $this->codejercicio)
        ];
        $total = $subcuenta->count($whereTotal);
        if ($total > 99) {
            $numbers = array_merge($numbers, range($total, $total + 99));
        }

        // probamos los números para elegir el primer código de subcuenta que no exista
        foreach ($numbers as $num) {
            $newCode = $this->fillToLength($this->getExercise()->longsubcuenta, $num, $this->codcuenta);
            if (empty($newCode)) {
                continue;
            }

            // comprobamos que esta subcuenta no esté en uso en otro cliente o proveedor
            $where = [new DataBaseWhere('codsubcuenta', $newCode)];
            $count = $subject->count($where);
            if ($count > 0) {
                continue;
            }

            // si la subcuenta no existe, la elegimos
            $where = [
                new DataBaseWhere('codejercicio', $this->codejercicio),
                new DataBaseWhere('codsubcuenta', $newCode)
            ];
            if (false === $subcuenta->loadFromCode('', $where)) {
                return $newCode;
            }
        }

        // no hemos encontrado ninguna subcuenta libre
        Tools::log()->error('no-empty-account-found');

        return '';
    }

    /**
     * Returns parent account.
     *
     * @return static
     */
    public function getParent(): self
    {
        $parent = new static();

        // no parent data?
        if (empty($this->parent_idcuenta) && empty($this->parent_codcuenta)) {
            return $parent;
        }

        // parent id?
        if (!empty($this->parent_idcuenta) && $parent->loadFromCode($this->parent_idcuenta) && $parent->codejercicio === $this->codejercicio) {
            return $parent;
        }

        $where = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('codcuenta', $this->parent_codcuenta)
        ];
        $parent->loadFromCode('', $where);
        return $parent;
    }

    /**
     * Returns all subaccounts from this account.
     *
     * @return DinSubcuenta[]
     */
    public function getSubcuentas(): array
    {
        $subcuenta = new DinSubcuenta();
        $where = [new DataBaseWhere('idcuenta', $this->idcuenta)];
        return $subcuenta->all($where, ['codsubcuenta' => 'ASC'], 0, 0);
    }

    public function install(): string
    {
        // force the parents tables
        new DinCuentaEspecial();
        new DinEjercicio();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idcuenta';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'codcuenta';
    }

    public function save(): bool
    {
        if ($this->getExercise()->isOpened() || $this->disableAdditionalTest) {
            return parent::save();
        }

        Tools::log()->warning('closed-exercise', ['%exerciseName%' => $this->getExercise()->nombre]);
        return false;
    }

    public static function tableName(): string
    {
        return 'cuentas';
    }

    public function test(): bool
    {
        $this->codcuenta = trim($this->codcuenta);
        $this->descripcion = Tools::noHtml($this->descripcion);

        if (empty($this->codcuenta) || false === is_numeric($this->codcuenta)) {
            Tools::log()->warning('invalid-number', ['%number%' => $this->codcuenta]);
            return false;
        }

        if (strlen($this->descripcion) < 1 || strlen($this->descripcion) > 255) {
            Tools::log()->warning('invalid-column-lenght', [
                '%column%' => 'descripcion',
                '%min%' => '1',
                '%max%' => '255'
            ]);
            return false;
        }

        // prevent loops
        if (!empty($this->parent_idcuenta) && $this->parent_idcuenta === $this->idcuenta) {
            $this->parent_idcuenta = null;
        }
        if (!empty($this->parent_codcuenta) && $this->parent_codcuenta === $this->codcuenta) {
            $this->parent_codcuenta = null;
        }

        // uncompleted parent account data?
        if (!empty($this->parent_idcuenta) || !empty($this->parent_codcuenta)) {
            $parent = $this->getParent();
            $this->parent_codcuenta = $parent->codcuenta;
            $this->parent_idcuenta = $parent->idcuenta;

            // code length must be bigger than the parent
            if (strlen($this->codcuenta) <= strlen($parent->codcuenta)) {
                Tools::log()->warning('account-code-lower-than-parent', ['%code%' => $this->codcuenta]);
                return false;
            }
        }

        // code length must be lower than subaccounts
        if (strlen($this->codcuenta) >= $this->getExercise()->longsubcuenta) {
            Tools::log()->warning('account-code-bigger-than-subaccounts', ['%code%' => $this->codcuenta]);
            return false;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListCuenta?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    protected function fillToLength(int $length, string $value, string $prefix = ''): string
    {
        $value2 = trim($value);
        $count = $length - strlen($prefix) - strlen($value2);
        if ($count > 0) {
            return $prefix . str_repeat('0', $count) . $value2;
        } elseif ($count == 0) {
            return $prefix . $value2;
        }

        return '';
    }
}
