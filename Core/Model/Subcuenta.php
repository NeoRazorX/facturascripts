<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
     * @var bool
     */
    private $disableAdditionalTest = false;

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

    public function clear()
    {
        parent::clear();
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->saldo = 0.0;
    }

    public function delete(): bool
    {
        if ($this->getExercise()->isOpened() || $this->disableAdditionalTest) {
            return parent::delete();
        }

        Tools::log()->warning('closed-exercise', ['%exerciseName%' => $this->getExercise()->nombre]);
        return false;
    }

    public function disableAdditionalTest(bool $value)
    {
        $this->disableAdditionalTest = $value;
    }

    /**
     * Returns the parent account.
     *
     * @return DinCuenta
     */
    public function getAccount(): DinCuenta
    {
        $account = new DinCuenta();

        // find account by id
        if (!empty($this->idcuenta) && $account->loadFromCode($this->idcuenta) && $account->codejercicio === $this->codejercicio) {
            return $account;
        }

        // find account by code and exercise
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
     * @return ?string
     */
    public function getSpecialAccountCode(): ?string
    {
        if (empty($this->codcuentaesp)) {
            $account = $this->getAccount();
            if ($account->exists()) {
                return $account->codcuentaesp;
            }
        }

        return $this->codcuentaesp;
    }

    public function install(): string
    {
        // force the parents tables
        new DinCuentaEspecial();
        new DinCuenta();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'idsubcuenta';
    }

    public function primaryDescriptionColumn(): string
    {
        return 'codsubcuenta';
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
        return 'subcuentas';
    }

    public function test(): bool
    {
        $this->saldo = $this->debe - $this->haber;

        // escape html
        foreach (['codcuenta', 'codsubcuenta', 'descripcion', 'codcuentaesp'] as $field) {
            $this->{$field} = Tools::noHtml($this->{$field});
        }

        $this->codsubcuenta = empty($this->idsubcuenta) ? $this->transformCodsubcuenta($this->codsubcuenta) : $this->codsubcuenta;
        $this->descripcion = Tools::noHtml($this->descripcion);
        if (strlen($this->descripcion) < 1 || strlen($this->descripcion) > 255) {
            Tools::log()->warning(
                'invalid-column-lenght',
                ['%column%' => 'descripcion', '%min%' => '1', '%max%' => '255']
            );
            return false;
        }

        // check exercise
        $exercise = $this->getExercise();
        if (false === $this->disableAdditionalTest && strlen($this->codsubcuenta) !== $exercise->longsubcuenta) {
            Tools::log()->warning('account-length-error', ['%code%' => $this->codsubcuenta]);
            return false;
        }

        // sets account data
        $account = $this->getAccount();
        $this->codcuenta = $account->codcuenta;
        $this->idcuenta = $account->idcuenta;

        return parent::test();
    }

    /**
     * Transform subaccount code if necessary
     *
     * @param string $code
     * @param string $codejercicio
     * @return string
     */
    public function transformCodsubcuenta(string $code, string $codejercicio = ''): string
    {
        if (strpos($code, '.') === false) {
            return trim($code);
        }

        $parts = explode('.', trim($code));
        if (count($parts) === 2) {
            return str_pad($parts[0], $this->getExercise($codejercicio)->longsubcuenta - strlen($parts[1]), '0', STR_PAD_RIGHT) . $parts[1];
        }

        return trim($code);
    }

    /**
     * Update subaccount balance.
     *
     * @param float $debit
     * @param float $credit
     */
    public function updateBalance(float $debit = 0.0, float $credit = 0.0): void
    {
        // Si nos proporcionan un importe, lo usamos para actualizar el saldo.
        if ($debit + $credit != 0.0) {
            $this->debe += $debit;
            $this->haber += $credit;
            $this->save();
            return;
        }

        // calculamos el saldo de la subcuenta
        $sql = "SELECT COALESCE(SUM(debe), 0) as debe, COALESCE(SUM(haber), 0) as haber"
            . " FROM " . DinPartida::tableName()
            . " WHERE idsubcuenta = " . self::$dataBase->var2str($this->idsubcuenta) . ";";

        foreach (self::$dataBase->select($sql) as $row) {
            $decimals = Tools::settings('default', 'decimals');
            $debe = round($row['debe'], $decimals);
            $haber = round($row['haber'], $decimals);

            // si no hay cambios, no actualizamos
            if (abs($debe - $this->debe) < 0.01 && abs($haber - $this->haber) < 0.01) {
                continue;
            }

            $this->debe = $debe;
            $this->haber = $haber;
            $this->save();
        }
    }

    public function url(string $type = 'auto', string $list = 'ListCuenta?activetab=List'): string
    {
        return parent::url($type, $list);
    }
}
