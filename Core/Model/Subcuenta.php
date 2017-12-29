<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * The fourth level of an accounting plan. It is related to a single account.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Subcuenta
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idsubcuenta;
    
    /**
     *Identificacion de la empresa
     *
     * @var int
     */
    public $idempresa;

    /**
     * Sub-account code.
     *
     * @var float|int
     */
    public $codsubcuenta;

    /**
     * ID of the account to which it belongs.
     *
     * @var int
     */
    public $idcuenta;

    /**
     * Account code.
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Exercise code.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Currency code.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Tax code.
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Description of the subaccount.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Amount of credit.
     *
     * @var float|int
     */
    public $haber;

    /**
     * Amount of the debit.
     *
     * @var float|int
     */
    public $debe;

    /**
     * Balance amount.
     *
     * @var float|int
     */
    public $saldo;

    /**
     * Surcharge amount.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * VAT amount.
     *
     * @var float|int
     */
    public $iva;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_subcuentas';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idsubcuenta';
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
        new Ejercicio();
        new Cuenta();
        
        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->idsubcuenta = null;
        $this->codsubcuenta = null;
        $this->idcuenta = null;
        $this->codcuenta = null;
        $this->codejercicio = null;
        $this->coddivisa = AppSettings::get('default', 'coddivisa');
        $this->codimpuesto = null;
        $this->descripcion = '';
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->saldo = 0.0;
        $this->recargo = 0.0;
        $this->iva = 0.0;
    }

    /**
     * Returns the conversion rate for the currency.
     *
     * @return int
     */
    public function tasaconv()
    {
        if ($this->coddivisa !== null) {
            $divisaModel = new Divisa();
            $div = $divisaModel->get($this->coddivisa);
            if ($div) {
                return $div->tasaconv;
            }
        }

        return 1.0;
    }

    /**
     * Return the account.
     *
     * @return bool|mixed
     */
    public function getCuenta()
    {
        $cuenta = new Cuenta();

        return $cuenta->get($this->idcuenta);
    }

    /**
     * Returns the exercise.
     *
     * @return bool|mixed
     */
    public function getEjercicio()
    {
        $eje = new Ejercicio();

        return $eje->get($this->codejercicio);
    }

    /**
     * Returns all the items in a sub-account with offset.
     *
     * @param int $offset
     *
     * @return array
     */
    public function getPartidas($offset = 0)
    {
        $part = new Partida();

        return $part->allFromSubcuenta($this->idsubcuenta, $offset);
    }

    /**
     * Returns all the items in a sub-account.
     *
     * @return array
     */
    public function getPartidasFull()
    {
        $part = new Partida();

        return $part->fullFromSubcuenta($this->idsubcuenta);
    }

    /**
     * Counts the items of a sub-account.
     *
     * @return int
     */
    public function countPartidas()
    {
        $part = new Partida();

        return $part->countFromSubcuenta($this->idsubcuenta);
    }

    /**
     * Returns the totals of a sub-account.
     *
     * @return array
     */
    public function getTotales()
    {
        $part = new Partida();

        return $part->totalesFromSubcuenta($this->idsubcuenta);
    }

    /**
     * Returns all sub-accounts by code, exercise code.
     *
     * @param string $cod
     * @param string $codejercicio
     * @param bool   $crear
     *
     * @return bool|Subcuenta
     */
    public function getByCodigo($cod, $codejercicio, $crear = false)
    {
        foreach ($this->all([new DataBaseWhere('codsubcuenta', $cod), new DataBaseWhere('codejercicio', $codejercicio)]) as $subc) {
            return $subc;
        }

        if ($crear) {
            /// we look for the equivalent subaccount in another year
            foreach ($this->all([new DataBaseWhere('codsubcuenta', $cod)], ['idsubcuenta' => 'DESC']) as $oldSc) {
                /// we look for the equivalent account is THIS exercise
                $cuentaModel = new Cuenta();
                $newC = $cuentaModel->getByCodigo($oldSc->codcuenta, $codejercicio);
                if ($newC) {
                    $newSc = new self();
                    $newSc->codcuenta = $newC->codcuenta;
                    $newSc->coddivisa = $oldSc->coddivisa;
                    $newSc->codejercicio = $codejercicio;
                    $newSc->codimpuesto = $oldSc->codimpuesto;
                    $newSc->codsubcuenta = $oldSc->codsubcuenta;
                    $newSc->descripcion = $oldSc->descripcion;
                    $newSc->idcuenta = $newC->idcuenta;
                    $newSc->iva = $oldSc->iva;
                    $newSc->recargo = $oldSc->recargo;
                    if ($newSc->save()) {
                        return $newSc;
                    }

                    return false;
                }

                self::$miniLog->alert(self::$i18n->trans('equivalent-account-not-found', ['%accountCode%' => $oldSc->codcuenta, '%exerciseCode%' => $codejercicio, '%link%' => 'index.php?page=ContabilidadEjercicio&cod=' . $codejercicio]));

                return false;
            }

            self::$miniLog->alert(self::$i18n->trans('equivalent-subaccount-not-found', ['%subAccountCode%' => $cod]));

            return false;
        }

        return false;
    }

    /**
     * Returns the first subaccount of the exercise $codeje whose parent account
     * is marked as special account $id.
     *
     * @param int    $idcuesp
     * @param string $codeje
     *
     * @return Subcuenta|bool
     */
    public function getCuentaesp($idcuesp, $codeje)
    {
        $sql = 'SELECT * FROM co_subcuentas WHERE idcuenta IN '
            . '(SELECT idcuenta FROM co_cuentas WHERE idcuentaesp = ' . self::$dataBase->var2str($idcuesp)
            . ' AND codejercicio = ' . self::$dataBase->var2str($codeje) . ') ORDER BY codsubcuenta ASC;';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Returns if a sub-account has a balance or not.
     *
     * @return bool
     */
    public function tieneSaldo()
    {
        return !static::floatcmp($this->debe, $this->haber, FS_NF0, true);
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->descripcion = self::noHtml($this->descripcion);

        $limpiarCache = false;
        $totales = $this->getTotales();

        if (abs($this->debe - $totales['debe']) > .001) {
            $this->debe = $totales['debe'];
            $limpiarCache = true;
        }

        if (abs($this->haber - $totales['haber']) > .001) {
            $this->haber = $totales['haber'];
            $limpiarCache = true;
        }

        if (abs($this->saldo - $totales['saldo']) > .001) {
            $this->saldo = $totales['saldo'];
            $limpiarCache = true;
        }

        if ($limpiarCache) {
            $this->cleanCache();
        }

        if (strlen($this->codcuenta) === 0 || strlen($this->codejercicio) === 0) {
            self::$miniLog->alert(self::$i18n->trans('account-data-missing'));
            return false;
        }

        $where = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('codcuenta', $this->codcuenta)
        ];

        $count = new Cuenta();
        if ($count->loadFromCode(NULL, $where) === FALSE) {
            self::$miniLog->alert(self::$i18n->trans('account-data-error'));
            return false;
        }

        $this->idcuenta = $count->idcuenta;

        if (strlen($this->codsubcuenta) === 0 || strlen($this->descripcion) === 0) {
            self::$miniLog->alert(self::$i18n->trans('missing-data-subaccount'));
            return false;
        }

        return true;
    }

    /**
     * Returns the sub-accounts of the fiscal year $codeje whose parent account
     * is marked as special account $id.
     *
     * @param int    $idcuesp
     * @param string $codeje
     *
     * @return self[]
     */
    public function allFromCuentaesp($idcuesp, $codeje)
    {
        $cuentas = [];
        $sql = 'SELECT * FROM co_subcuentas WHERE idcuenta IN '
            . '(SELECT idcuenta FROM co_cuentas WHERE idcuentaesp = ' . self::$dataBase->var2str($idcuesp)
            . ' AND codejercicio = ' . self::$dataBase->var2str($codeje) . ') ORDER BY codsubcuenta ASC;';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $cuentas[] = new self($d);
            }
        }

        return $cuentas;
    }

    /**
     * Returns an array with the combinations containing $query in its codsubcuenta
     * or description.
     *
     * @param string $query
     *
     * @return self[]
     */
    public function search($query)
    {
        $sublist = [];
        $query = mb_strtolower(self::noHtml($query), 'UTF8');
        $sql = 'SELECT * FROM ' . static::tableName() . " WHERE codsubcuenta LIKE '" . $query . "%'"
            . " OR codsubcuenta LIKE '%" . $query . "'"
            . " OR lower(descripcion) LIKE '%" . $query . "%'"
            . ' ORDER BY codejercicio DESC, codcuenta ASC;';

        $data = self::$dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $s) {
                $sublist[] = new self($s);
            }
        }

        return $sublist;
    }

    /**
     * Returns the results of the $ query search on the subaccounts of the
     * exercise $codejercicio
     *
     * @param string $codejercicio
     * @param string $query
     *
     * @return Subcuenta
     */
    public function searchByEjercicio($codejercicio, $query)
    {
        $query = self::$dataBase->escapeString(mb_strtolower(trim($query), 'UTF8'));

        $sublist = self::$cache->get('search_subcuenta_ejercicio_' . $codejercicio . '_' . $query);
        if (count($sublist) < 1) {
            $sql = 'SELECT * FROM ' . static::tableName()
                . ' WHERE codejercicio = ' . self::$dataBase->var2str($codejercicio)
                . " AND (codsubcuenta LIKE '" . $query . "%' OR codsubcuenta LIKE '%" . $query . "'"
                . " OR lower(descripcion) LIKE '%" . $query . "%') ORDER BY codcuenta ASC;";

            $data = self::$dataBase->select($sql);
            if (!empty($data)) {
                foreach ($data as $s) {
                    $sublist[] = new self($s);
                }
            }

            self::$cache->set('search_subcuenta_ejercicio_' . $codejercicio . '_' . $query, $sublist);
        }

        return $sublist;
    }
}
