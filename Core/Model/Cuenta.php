<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * First level of the accounting plan.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Cuenta extends Base\ModelClass
{

    use Base\ModelTrait;

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
     * Code of the exercise of this account.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Description of the account.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Configuration parameter.
     *
     * @var bool
     */
    private static $disableAditionTest = false;

    /**
     * All exercises.
     *
     * @var Ejercicio[]
     */
    protected static $ejercicios;

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
     * Identifier of the parent account
     *
     * @var integer
     */
    public $parent_idcuenta;

    public function clear()
    {
        parent::clear();
        $this->codejercicio = $this->getExercise()->codejercicio;
    }

    /**
     * 
     * @param bool $value
     */
    public function disableAditionalTest(bool $value = true)
    {
        self::$disableAditionTest = $value;
    }

    /**
     *
     * @return Cuenta[]
     */
    public function getChildren()
    {
        $where = [new DataBaseWhere('parent_idcuenta', $this->idcuenta)];
        return $this->all($where, ['codcuenta' => 'ASC'], 0, 0);
    }

    /**
     * Returns the current exercise or the default one.
     * 
     * @return Ejercicio
     */
    public function getExercise()
    {
        /// loads all exercise to improve performance
        if (empty(self::$ejercicios)) {
            $exerciseModel = new Ejercicio();
            self::$ejercicios = $exerciseModel->all();
        }

        /// find exercise
        foreach (self::$ejercicios as $exe) {
            if ($exe->codejercicio == $this->codejercicio) {
                return $exe;
            } elseif (empty($this->codejercicio) && $exe->isOpened()) {
                /// return default exercise
                return $exe;
            }
        }

        /// exercise not found? try to get from database
        $exercise = new Ejercicio();
        if ($exercise->loadFromCode($this->codejercicio)) {
            /// add new exercise to cache
            self::$ejercicios[] = $exercise;
        }
        return $exercise;
    }

    /**
     *
     * @return Cuenta
     */
    public function getParent()
    {
        $parent = new Cuenta();
        $parent->loadFromCode($this->parent_idcuenta);
        return $parent;
    }

    /**
     *
     * @return Cuenta
     */
    public function getParentFromCode()
    {
        $where = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('codcuenta', $this->parent_codcuenta)
        ];
        $parent = new Cuenta();
        $parent->loadFromCode('', $where);
        return $parent;
    }

    /**
     *
     * @return Subcuenta[]
     */
    public function getSubcuentas()
    {
        $subcuenta = new Subcuenta();
        $where = [new DataBaseWhere('idcuenta', $this->idcuenta)];
        return $subcuenta->all($where, ['codsubcuenta' => 'ASC'], 0, 0);
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        /// force the parents tables
        new CuentaEspecial();
        new Ejercicio();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idcuenta';
    }

    /**
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'codcuenta';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'cuentas';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->codcuenta = trim($this->codcuenta);
        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);
        if (\strlen($this->descripcion) < 1 || \strlen($this->descripcion) > 255) {
            $this->toolBox()->i18nLog()->warning('invalid-column-lenght', ['%column%' => 'descripcion', '%min%' => '1', '%max%' => '255']);
            return false;
        }

        /// uncomplete parent account data?
        if (empty($this->parent_codcuenta) && !empty($this->parent_idcuenta)) {
            $this->completeParentData();
        }

        if (!empty($this->parent_idcuenta) && !self::$disableAditionTest) {
            $parent = $this->getParent();
            if ($parent->codejercicio != $this->codejercicio || $parent->idcuenta == $this->idcuenta) {
                $this->toolBox()->i18nLog()->warning('account-parent-error');
                return false;
            }
        }

        return parent::test();
    }

    /**
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListCuenta?activetab=List')
    {
        return parent::url($type, $list);
    }

    /**
     *
     * @return bool
     */
    private function completeParentData()
    {
        $parent = $this->getParent();
        if ($parent->exists() && $parent->codejercicio == $this->codejercicio && $parent->idcuenta != $this->idcuenta) {
            $this->parent_codcuenta = $parent->codcuenta;
            return true;
        }

        $this->parent_codcuenta = null;
        $this->parent_idcuenta = null;
        return false;
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    protected function saveInsert(array $values = [])
    {
        if ($this->getExercise()->isOpened()) {
            return parent::saveInsert($values);
        }

        $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->getExercise()->primaryDescription()]);
        return false;
    }
}
