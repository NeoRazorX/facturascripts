<?php
/**
 * This file is part of facturacion_base
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

use FacturaScripts\Core\Model;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * El cuarto nivel de un plan contable. Está relacionada con una única cuenta.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Subcuenta
{
    use Base\ModelTrait;

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $idsubcuenta;

    /**
     * Código de subcuenta
     *
     * @var float|int
     */
    public $codsubcuenta;

    /**
     * ID de la cuenta a la que pertenece.
     *
     * @var int
     */
    public $idcuenta;

    /**
     * Código de cuenta
     *
     * @var string
     */
    public $codcuenta;

    /**
     * Código de ejercicio
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Código de divisa
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Código de impuesto
     *
     * @var string
     */
    public $codimpuesto;

    /**
     * Descripción de la subcuenta.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Importe del Haber
     *
     * @var float|int
     */
    public $haber;

    /**
     * Importe del Debe
     *
     * @var float|int
     */
    public $debe;

    /**
     * Importe del Saldo
     *
     * @var float|int
     */
    public $saldo;

    /**
     * Importe del Recargo
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Importe del Iva
     *
     * @var float|int
     */
    public $iva;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public function tableName()
    {
        return 'co_subcuentas';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idsubcuenta';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->idsubcuenta = null;
        $this->codsubcuenta = null;
        $this->idcuenta = null;
        $this->codcuenta = null;
        $this->codejercicio = null;
        $this->coddivisa = $this->defaultItems->codDivisa();
        $this->codimpuesto = null;
        $this->descripcion = '';
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->saldo = 0.0;
        $this->recargo = 0.0;
        $this->iva = 0.0;
    }

    /**
     * Devuelve la tasa de conversión para la divisa
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
     * Devuelve la cuenta
     *
     * @return bool|mixed
     */
    public function getCuenta()
    {
        $cuenta = new Cuenta();

        return $cuenta->get($this->idcuenta);
    }

    /**
     * Devuelve el ejercicio
     *
     * @return bool|mixed
     */
    public function getEjercicio()
    {
        $eje = new Ejercicio();

        return $eje->get($this->codejercicio);
    }

    /**
     * Devuelve todas las partidas de una subcuenta con offset
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
     * Devuelve todas las partidas de una subcuenta
     *
     * @return array
     */
    public function getPartidasFull()
    {
        $part = new Partida();

        return $part->fullFromSubcuenta($this->idsubcuenta);
    }

    /**
     * Cuenta las partidas de una subcuenta
     *
     * @return int
     */
    public function countPartidas()
    {
        $part = new Partida();

        return $part->countFromSubcuenta($this->idsubcuenta);
    }

    /**
     * Devuelve los totales de una subcuenta
     *
     * @return array
     */
    public function getTotales()
    {
        $part = new Partida();

        return $part->totalesFromSubcuenta($this->idsubcuenta);
    }

    /**
     * Devuelve todas las subcuentas por código, código de ejercicio
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
            /// buscamos la subcuenta equivalente en otro ejercicio
            foreach ($this->all([new DataBaseWhere('codsubcuenta', $cod)], ['idsubcuenta' => 'DESC']) as $oldSc) {
                /// buscamos la cuenta equivalente es ESTE ejercicio
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

                $this->miniLog->alert($this->i18n->trans('equivalent-account-not-found', [$oldSc->codcuenta, $codejercicio, 'index.php?page=ContabilidadEjercicio&cod=' . $codejercicio]));

                return false;
            }

            $this->miniLog->alert($this->i18n->trans('equivalent-subaccount-not-found', [$cod]));

            return false;
        }

        return false;
    }

    /**
     * Devuelve la primera subcuenta del ejercicio $codeje cuya cuenta madre
     * está marcada como cuenta especial $id.
     *
     * @param int    $idcuesp
     * @param string $codeje
     *
     * @return Subcuenta|bool
     */
    public function getCuentaesp($idcuesp, $codeje)
    {
        $sql = 'SELECT * FROM co_subcuentas WHERE idcuenta IN '
            . '(SELECT idcuenta FROM co_cuentas WHERE idcuentaesp = ' . $this->var2str($idcuesp)
            . ' AND codejercicio = ' . $this->var2str($codeje) . ') ORDER BY codsubcuenta ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            return new self($data[0]);
        }

        return false;
    }

    /**
     * Devuelve si una subcuenta tiene saldo o no
     *
     * @return bool
     */
    public function tieneSaldo()
    {
        return !static::floatcmp($this->debe, $this->haber, FS_NF0, true);
    }

    /**
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
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
            $this->miniLog->alert($this->i18n->trans('account-data-missing'));
            return false;
        }        
        
        $where = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('codcuenta', $this->codcuenta)
        ];

        $count = new Model\Cuenta();
        if ($count->loadFromCode(NULL, $where) === FALSE) {
            $this->miniLog->alert($this->i18n->trans('account-data-error'));
            return false;         
        }
        
        $this->idcuenta = $count->idcuenta;
        
        if (strlen($this->codsubcuenta) === 0 || strlen($this->descripcion) === 0) {
            $this->miniLog->alert($this->i18n->trans('missing-data-subaccount'));
            return false;
        }
        
        return true;
    }

    /**
     * Devuelve las subcuentas del ejercicio $codeje cuya cuenta madre
     * está marcada como cuenta especial $id.
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
            . '(SELECT idcuenta FROM co_cuentas WHERE idcuentaesp = ' . $this->var2str($idcuesp)
            . ' AND codejercicio = ' . $this->var2str($codeje) . ') ORDER BY codsubcuenta ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $d) {
                $cuentas[] = new self($d);
            }
        }

        return $cuentas;
    }

    /**
     * Devuelve un array con las combinaciones que contienen $query en su codsubcuenta
     * o descripcion.
     *
     * @param string $query
     *
     * @return self[]
     */
    public function search($query)
    {
        $sublist = [];
        $query = mb_strtolower(self::noHtml($query), 'UTF8');
        $sql = 'SELECT * FROM ' . $this->tableName() . " WHERE codsubcuenta LIKE '" . $query . "%'"
            . " OR codsubcuenta LIKE '%" . $query . "'"
            . " OR lower(descripcion) LIKE '%" . $query . "%'"
            . ' ORDER BY codejercicio DESC, codcuenta ASC;';

        $data = $this->dataBase->select($sql);
        if (!empty($data)) {
            foreach ($data as $s) {
                $sublist[] = new self($s);
            }
        }

        return $sublist;
    }

    /**
     * Devuelve los resultados de la búsuqeda $query sobre las subcuentas del
     * ejercicio $codejercicio
     *
     * @param string $codejercicio
     * @param string $query
     *
     * @return Subcuenta
     */
    public function searchByEjercicio($codejercicio, $query)
    {
        $query = $this->escapeString(mb_strtolower(trim($query), 'UTF8'));

        $sublist = $this->cache->get('search_subcuenta_ejercicio_' . $codejercicio . '_' . $query);
        if (count($sublist) < 1) {
            $sql = 'SELECT * FROM ' . $this->tableName() . ' WHERE codejercicio = ' . $this->var2str($codejercicio)
                . " AND (codsubcuenta LIKE '" . $query . "%' OR codsubcuenta LIKE '%" . $query . "'"
                . " OR lower(descripcion) LIKE '%" . $query . "%') ORDER BY codcuenta ASC;";

            $data = $this->dataBase->select($sql);
            if (!empty($data)) {
                foreach ($data as $s) {
                    $sublist[] = new self($s);
                }
            }

            $this->cache->set('search_subcuenta_ejercicio_' . $codejercicio . '_' . $query, $sublist);
        }

        return $sublist;
    }
}
