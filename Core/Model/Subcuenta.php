<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\Cuenta as DinCuenta;
use FacturaScripts\Dinamic\Model\CuentaEspecial as DinCuentaEspecial;
use FacturaScripts\Dinamic\Model\Partida as DinPartida;

/**
 * Detail level of an accounting plan. It is related to a single account.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Subcuenta extends Base\ModelClass
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
     * Sub-account code.
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * Amount of the debit.
     *
     * @var float|int
     */
    public $debe;

    /**
     * Description of the subaccount.
     *
     * @var string
     */
    public $descripcion;

    /**
     *
     * @var bool
     */
    private $disableAditionalTest = false;

    /**
     * Amount of credit.
     *
     * @var float|int
     */
    public $haber;

    /**
     * Account identifier.
     *
     * @var int
     */
    public $idcuenta;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idsubcuenta;

    /**
     * Balance amount.
     *
     * @var float|int
     */
    public $saldo;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->codejercicio = $this->getExercise()->codejercicio;
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->saldo = 0.0;
    }

    /**
     * Removes this subaccount from the database.
     * 
     * @return bool
     */
    public function delete()
    {
        if ($this->getExercise()->isOpened() || $this->disableAditionalTest) {
            return parent::delete();
        }

        $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->getExercise()->nombre]);
        return false;
    }

    /**
     * 
     * @param bool $value
     */
    public function disableAditionalTest(bool $value)
    {
        $this->disableAditionalTest = $value;
    }

    /**
     * Returns the parent account.
     *
     * @return DinCuenta
     */
    public function getAccount()
    {
        $account = new DinCuenta();

        /// find account by id
        if (!empty($this->idcuenta) && $account->loadFromCode($this->idcuenta) && $account->codejercicio === $this->codejercicio) {
            return $account;
        }

        /// find account by code and exercise
        $where = [
            new DataBaseWhere('codcuenta', $this->codcuenta),
            new DataBaseWhere('codejercicio', $this->codejercicio)
        ];
        $account->loadFromCode('', $where);
        return $account;
    }

    /**
     * Returns the related special account code.
     *
     * @return string
     */
    public function getSpecialAccountCode()
    {
        if (empty($this->codcuentaesp)) {
            $account = $this->getAccount();
            if ($account->exists()) {
                return $account->codcuentaesp;
            }
        }

        return $this->codcuentaesp;
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
        new DinCuentaEspecial();
        new DinCuenta();

        return parent::install();
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idsubcuenta';
    }

    /**
     *
     * @return string
     */
    public function primaryDescriptionColumn()
    {
        return 'codsubcuenta';
    }

    /**
     * 
     * @return bool
     */
    public function save()
    {
        if ($this->getExercise()->isOpened() || $this->disableAditionalTest) {
            return parent::save();
        }

        $this->toolBox()->i18nLog()->warning('closed-exercise', ['%exerciseName%' => $this->getExercise()->nombre]);
        return false;
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'subcuentas';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->saldo = $this->debe - $this->haber;

        $this->codcuenta = \trim($this->codcuenta);
        $this->codsubcuenta = empty($this->idsubcuenta) ? $this->transformCodsubcuenta($this->codsubcuenta) : \trim($this->codsubcuenta);
        $this->descripcion = $this->toolBox()->utils()->noHtml($this->descripcion);
        if (\strlen($this->descripcion) < 1 || \strlen($this->descripcion) > 255) {
            $this->toolBox()->i18nLog()->warning(
                'invalid-column-lenght',
                ['%column%' => 'descripcion', '%min%' => '1', '%max%' => '255']
            );
            return false;
        }

        /// check exercise
        $exercise = $this->getExercise();
        if (!$this->disableAditionalTest && \strlen($this->codsubcuenta) !== $exercise->longsubcuenta) {
            $this->toolBox()->i18nLog()->warning('account-length-error', ['%code%' => $this->codsubcuenta]);
            return false;
        }

        /// sets account data
        $account = $this->getAccount();
        $this->codcuenta = $account->codcuenta;
        $this->idcuenta = $account->idcuenta;

        return parent::test();
    }

    /**
     * Transform subaccount code if necesary
     * 
     * @param string $code
     *
     * @return string
     */
    public function transformCodsubcuenta(string $code): string
    {
        if (\strpos($code, '.') === false) {
            return \trim($code);
        }

        $parts = \explode('.', \trim($code));
        if (\count($parts) === 2) {
            return \str_pad($parts[0], $this->getExercise()->longsubcuenta - \strlen($parts[1]), '0', \STR_PAD_RIGHT) . $parts[1];
        }

        return \trim($code);
    }

    /**
     * Update subaccount balance.
     * 
     * @param float $debit
     * @param float $credit
     */
    public function updateBalance($debit = 0.0, $credit = 0.0)
    {
        /// supplied debit and credit?
        if ($debit + $credit != 0.0) {
            $this->debe += $debit;
            $this->haber += $credit;
            $this->save();
            return;
        }

        /// calculate account balance
        $sql = "SELECT COALESCE(SUM(debe), 0) as debe, COALESCE(SUM(haber), 0) as haber"
            . " FROM " . DinPartida::tableName()
            . " WHERE idsubcuenta = " . self::$dataBase->var2str($this->idsubcuenta) . ";";

        foreach (self::$dataBase->select($sql) as $row) {
            $this->debe = (float) $row['debe'];
            $this->haber = (float) $row['haber'];
            $this->save();
        }
    }

    /**
     * Returns the url where to see / modify the data.
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
}
