<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

    /**
     * Account code.
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Identifier of the special account.
     *
     * @var string
     */
    public $codcuentaesp;

    /**
     * Description of the account.
     *
     * @var string
     */
    public $descripcion;

    /**
     * @var bool
     */
    private $disableAdditionalTest = false;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idcuenta;

    /**
     * Parent account code
     *
     * @var string
     */
    public $parent_codcuenta;

    /**
     * Parent account identifier
     *
     * @var integer
     */
    public $parent_idcuenta;

    public function delete(): bool
    {
        if ($this->getExercise()->isOpened() || $this->disableAdditionalTest) {
            return parent::delete();
        }

        $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->getExercise()->nombre]);
        return false;
    }

    public function disableAdditionalTest(bool $value)
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

    /**
     * Returns parent account.
     *
     * @return static
     */
    public function getParent()
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

        $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->getExercise()->nombre]);
        return false;
    }

    public static function tableName(): string
    {
        return 'cuentas';
    }

    public function test(): bool
    {
        $this->codcuenta = trim($this->codcuenta);
        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);

        if (empty($this->codcuenta) || false === is_numeric($this->codcuenta)) {
            $this->toolBox()->i18nLog()->warning('invalid-number', ['%number%' => $this->codcuenta]);
            return false;
        }

        if (strlen($this->descripcion) < 1 || strlen($this->descripcion) > 255) {
            $this->toolBox()->i18nLog()->warning('invalid-column-lenght', ['%column%' => 'descripcion', '%min%' => '1', '%max%' => '255']);
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
                $this->toolBox()->i18nLog()->warning('account-code-lower-than-parent', ['%code%' => $this->codcuenta]);
                return false;
            }
        }

        // code length must be lower than subaccounts
        if (strlen($this->codcuenta) >= $this->getExercise()->longsubcuenta) {
            $this->toolBox()->i18nLog()->warning('account-code-bigger-than-subaccounts', ['%code%' => $this->codcuenta]);
            return false;
        }

        return parent::test();
    }

    public function url(string $type = 'auto', string $list = 'ListCuenta?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
