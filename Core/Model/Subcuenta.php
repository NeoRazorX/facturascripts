<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Model\Base\ExerciseRelationTrait;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cuenta as DinCuenta;
use FacturaScripts\Dinamic\Model\CuentaEspecial as DinCuentaEspecial;
use FacturaScripts\Dinamic\Model\Partida as DinPartida;

/**
 * Nivel de detalle de un plan contable. Se relaciona con una única cuenta.
 *
 * @author Carlos García Gómez  <carlos@facturascripts.com>
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class Subcuenta extends ModelClass
{
    use ModelTrait;
    use ExerciseRelationTrait;

    /**
     * Código de la cuenta.
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Identificador de la cuenta especial.
     *
     * @var string
     */
    public $codcuentaesp;

    /**
     * Código de la subcuenta.
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * Importe del debe.
     *
     * @var float|int
     */
    public $debe;

    /**
     * Descripción de la subcuenta.
     *
     * @var string
     */
    public $descripcion;

    /**
     * @var bool
     */
    private $disable_additional_test = false;

    /**
     * Importe del haber.
     *
     * @var float|int
     */
    public $haber;

    /**
     * Identificador de la cuenta.
     *
     * @var int
     */
    public $idcuenta;

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $idsubcuenta;

    /**
     * Importe del saldo.
     *
     * @var float|int
     */
    public $saldo;

    public function clear(): void
    {
        parent::clear();
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->saldo = 0.0;
    }

    public function delete(): bool
    {
        if ($this->getExercise()->isOpened() || $this->disable_additional_test) {
            return parent::delete();
        }

        Tools::log()->warning('closed-exercise', ['%exerciseName%' => $this->getExercise()->nombre]);
        return false;
    }

    public function disableAdditionalTest(bool $value): void
    {
        $this->disable_additional_test = $value;
    }

    /**
     * Devuelve la cuenta padre.
     *
     * @return DinCuenta
     */
    public function getAccount(): DinCuenta
    {
        $account = new DinCuenta();

        // buscar cuenta por identificador
        if (!empty($this->idcuenta) && $account->load($this->idcuenta) && $account->codejercicio === $this->codejercicio) {
            return $account;
        }

        // buscar cuenta por código y ejercicio
        $where = [
            new DataBaseWhere('codcuenta', $this->codcuenta),
            new DataBaseWhere('codejercicio', $this->codejercicio)
        ];
        $account->loadWhere($where);
        return $account;
    }

    /**
     * Devuelve el código de la cuenta especial relacionada.
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
        // forzar las tablas padre
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
        if ($this->getExercise()->isOpened() || $this->disable_additional_test) {
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
        $this->saldo = Tools::round($this->debe - $this->haber);

        // escapar html
        foreach (['codcuenta', 'codsubcuenta', 'descripcion', 'codcuentaesp'] as $field) {
            $this->{$field} = Tools::noHtml($this->{$field});
        }

        $this->codsubcuenta = empty($this->idsubcuenta) ? $this->transformCodsubcuenta($this->codsubcuenta) : $this->codsubcuenta;
        $this->descripcion = Tools::noHtml($this->descripcion);
        if (strlen($this->descripcion) < 1 || strlen($this->descripcion) > 255) {
            Tools::log()->warning('invalid-column-lenght', [
                '%column%' => 'descripcion',
                '%min%' => '1',
                '%max%' => '255'
            ]);
            return false;
        }

        // comprobar ejercicio
        $exercise = $this->getExercise();
        if (false === $this->disable_additional_test && strlen($this->codsubcuenta) !== $exercise->longsubcuenta) {
            Tools::log()->warning('account-length-error', [
                '%code%' => $this->codsubcuenta,
                '%length%' => $exercise->longsubcuenta,
                '%exercise%' => $exercise->id()
            ]);
            return false;
        }

        // establecer datos de cuenta
        $account = $this->getAccount();
        $this->codcuenta = $account->codcuenta;
        $this->idcuenta = $account->idcuenta;

        return parent::test();
    }

    /**
     * Transforma el código de subcuenta si es necesario
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
     * Actualiza el saldo de la subcuenta.
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
            . " WHERE idsubcuenta = " . self::db()->var2str($this->idsubcuenta) . ";";

        foreach (self::db()->select($sql) as $row) {
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

    protected function saveInsert(): bool
    {
        // antes de insertar, comprobar si la cuenta padre es correcta
        // basándose en el prefijo del código de subcuenta
        $account = $this->getAccount();

        // comprobar si el código de subcuenta comienza con el código de cuenta
        // si no, buscar la cuenta padre correcta automáticamente
        if ($account->exists()) {
            // si la subcuenta no comienza con el código de cuenta, buscar la cuenta padre correcta
            if (strpos($this->codsubcuenta, $account->codcuenta) !== 0) {
                $correctAccount = $this->findCorrectParentAccount();
                if ($correctAccount->exists()) {
                    $this->codcuenta = $correctAccount->codcuenta;
                    $this->idcuenta = $correctAccount->idcuenta;
                }
            }
        } else {
            // si no se encontró cuenta, intentar buscar la correcta basándose en el código de subcuenta
            $correctAccount = $this->findCorrectParentAccount();
            if ($correctAccount->exists()) {
                $this->codcuenta = $correctAccount->codcuenta;
                $this->idcuenta = $correctAccount->idcuenta;
            }
        }

        return parent::saveInsert();
    }

    /**
     * Encuentra la cuenta padre correcta basándose en el prefijo del código de subcuenta.
     * Por ejemplo, para la subcuenta "572.11" o "57211", encontrará la cuenta "57".
     *
     * @return DinCuenta
     */
    private function findCorrectParentAccount(): DinCuenta
    {
        $account = new DinCuenta();

        // si no tenemos código de subcuenta o ejercicio, devolver cuenta vacía
        if (empty($this->codsubcuenta) || empty($this->codejercicio)) {
            return $account;
        }

        // eliminar puntos del código de subcuenta (no transformar de nuevo, puede haber sido transformado ya)
        $subaccountCode = str_replace('.', '', $this->codsubcuenta);

        // buscar todas las cuentas para este ejercicio
        $where = [new DataBaseWhere('codejercicio', $this->codejercicio)];
        $accounts = $account->all($where, [], 0, 0);

        // encontrar la cuenta con el código más largo que sea prefijo del código de subcuenta
        $bestMatch = null;
        $bestMatchLength = 0;

        foreach ($accounts as $acc) {
            $accountCode = str_replace('.', '', $acc->codcuenta);

            // comprobar si el código de subcuenta comienza con este código de cuenta
            if (strlen($accountCode) > 0 && strpos($subaccountCode, $accountCode) === 0) {
                $accountCodeLength = strlen($accountCode);

                // mantener la coincidencia más larga
                if ($accountCodeLength > $bestMatchLength) {
                    $bestMatch = $acc;
                    $bestMatchLength = $accountCodeLength;
                }
            }
        }

        // si encontramos una cuenta coincidente, devolverla
        if ($bestMatch !== null) {
            return $bestMatch;
        }

        // si no se encontró coincidencia, devolver cuenta vacía
        return $account;
    }
}
