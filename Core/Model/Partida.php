<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

/**
 * The line of a seat.
 * It is related to a seat and a sub-account.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Partida
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idpartida;

    /**
     * Related seat ID.
     *
     * @var int
     */
    public $idasiento;

    /**
     * Related sub-account ID.
     *
     * @var int
     */
    public $idsubcuenta;

    /**
     * Code, not ID, of the related sub-account.
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * Identifier of the concept.
     *
     * @var int
     */
    public $idconcepto;

    /**
     * Concept.
     *
     * @var string
     */
    public $concepto;

    /**
     * Identifier of the counterpart.
     *
     * @var int
     */
    public $idcontrapartida;

    /**
     * Counterparty code.
     *
     * @var string
     */
    public $codcontrapartida;

    /**
     * True if it is dotted, but False.
     *
     * @var bool
     */
    public $punteada;

    /**
     * Value of the conversion rate.
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * Currency code.
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Have of the departure.
     *
     * @var float|int
     */
    public $haberme;

    /**
     * Must of the departure.
     *
     * @var float|int
     */
    public $debeme;

    /**
     * Amount of the surcharge.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Amount of the VAT.
     *
     * @var float|int
     */
    public $iva;

    /**
     * Amount of the tax base.
     *
     * @var float|int
     */
    public $baseimponible;

    /**
     * Invoice of the departure.
     *
     * @var
     */
    public $factura;

    /**
     * Serie code.
     *
     * @var string
     */
    public $codserie;

    /**
     * Document type.
     *
     * @var
     */
    public $tipodocumento;

    /**
     * Document of departure.
     *
     * @var string
     */
    public $documento;

    /**
     * CIF / NIF of the item.
     *
     * @var string
     */
    public $cifnif;

    /**
     * Have of the departure.
     *
     * @var float|int
     */
    public $haber;

    /**
     * Must of the departure.
     *
     * @var float|int
     */
    public $debe;

    /**
     * Number of the departure.
     *
     * @var int
     */
    public $numero;

    /**
     * Date.
     *
     * @var string
     */
    public $fecha;

    /**
     * Balance of the departure.
     *
     * @var float|int
     */
    public $saldo;

    /**
     * Sum of the debit.
     *
     * @var float|int
     */
    public $sum_debe;

    /**
     * Sum of the credit.
     *
     * @var float|int
     */
    public $sum_haber;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_partidas';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idpartida';
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
        new Asiento();
        new Subcuenta();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->concepto = '';
        $this->punteada = false;
        $this->tasaconv = 1.0;
        $this->coddivisa = AppSettings::get('default', 'coddivisa');
        $this->haberme = 0.0;
        $this->debeme = 0.0;
        $this->recargo = 0.0;
        $this->iva = 0.0;
        $this->baseimponible = 0.0;
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->numero = 0;
        $this->fecha = date('d-m-Y');
        $this->saldo = 0.0;
        $this->sum_debe = 0.0;
        $this->sum_haber = 0.0;
    }

    /**
     * Returns the sub-account of the departure.
     *
     * @return bool|mixed
     */
    public function getSubcuenta()
    {
        $subcuenta = new Subcuenta();

        return $subcuenta->get($this->idsubcuenta);
    }

    /**
     * Returns the url of the sub-account of the departure.
     *
     * @return string
     */
    public function subcuentaUrl()
    {
        $subc = $this->getSubcuenta();
        if ($subc) {
            return $subc->url();
        }

        return '#';
    }

    /**
     * Returns the counterpart's subaccount.
     *
     * @return bool|mixed
     */
    public function getContrapartida()
    {
        if ($this->idcontrapartida === null) {
            return false;
        }
        $subc = new Subcuenta();

        return $subc->get($this->idcontrapartida);
    }

    /**
     * Returns the url of the counterpart's subaccount.
     *
     * @return string
     */
    public function contrapartidaUrl()
    {
        $subc = $this->getContrapartida();
        if ($subc) {
            return $subc->url();
        }

        return '#';
    }

    /**
     * Returns True if there is no erros on properties values.
     *
     * @return bool
     */
    public function test()
    {
        $this->concepto = self::noHtml($this->concepto);
        $this->documento = self::noHtml($this->documento);
        $this->cifnif = self::noHtml($this->cifnif);

        return true;
    }

    /**
     * Deletes the data from the database record.
     *
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . static::tableName()
            . ' WHERE idpartida = ' . self::$dataBase->var2str($this->idpartida) . ';';
        if (self::$dataBase->exec($sql)) {
            $subc = $this->getSubcuenta();
            if ($subc) {
                $subc->save(); /// guardamos la subcuenta para actualizar su saldo
            }

            return true;
        }

        return false;
    }

    /**
     * Returns all items in the sub-account from the offset.
     *
     * @param int $idsubc
     * @param int $offset
     *
     * @return array
     */
    public function allFromSubcuenta($idsubc, $offset = 0)
    {
        $plist = [];
        $sql = 'SELECT a.numero,a.fecha,p.idpartida,p.debe,p.haber FROM co_asientos a, co_partidas p'
            . ' WHERE a.idasiento = p.idasiento AND p.idsubcuenta = ' . self::$dataBase->var2str($idsubc)
            . ' ORDER BY a.numero ASC, p.idpartida ASC;';

        $ordenadas = self::$dataBase->select($sql);
        if (!empty($ordenadas)) {
            $partida = new self();
            $i = 0;
            $saldo = 0;
            $sumDebe = 0;
            $sumHaber = 0;
            foreach ($ordenadas as $po) {
                $saldo += (float) $po['debe'] - (float) $po['haber'];
                $sumDebe += (float) $po['debe'];
                $sumHaber += (float) $po['haber'];
                if ($i >= $offset && $i < ($offset + FS_ITEM_LIMIT)) {
                    $aux = $partida->get($po['idpartida']);
                    if ($aux) {
                        $aux->numero = (int) $po['numero'];
                        $aux->fecha = date('d-m-Y', strtotime($po['fecha']));
                        $aux->saldo = $saldo;
                        $aux->sum_debe = $sumDebe;
                        $aux->sum_haber = $sumHaber;
                        $plist[] = $aux;
                    }
                }
                ++$i;
            }
        }

        return $plist;
    }

    /**
     * Returns all the items in the subaccount.
     *
     * @param int $idsubc
     *
     * @return array
     */
    public function fullFromSubcuenta($idsubc)
    {
        $plist = [];
        $sql = 'SELECT a.numero,a.fecha,p.idpartida FROM co_asientos a, co_partidas p'
            . ' WHERE a.idasiento = p.idasiento AND p.idsubcuenta = ' . self::$dataBase->var2str($idsubc)
            . ' ORDER BY a.numero ASC, p.idpartida ASC';

        $saldo = 0;
        $sumDebe = 0;
        $sumHaber = 0;

        $partida = new self();
        $offset = 0;
        $data = self::$dataBase->selectLimit($sql, 100, $offset);
        while (!empty($data)) {
            foreach ($data as $po) {
                $aux = $partida->get($po['idpartida']);
                if ($aux) {
                    $aux->numero = (int) $po['numero'];
                    $aux->fecha = date('d-m-Y', strtotime($po['fecha']));
                    $saldo += $aux->debe - $aux->haber;
                    $sumDebe += $aux->debe;
                    $sumHaber += $aux->haber;
                    $aux->saldo = $saldo;
                    $aux->sum_debe = $sumDebe;
                    $aux->sum_haber = $sumHaber;
                    $plist[] = $aux;
                }

                ++$offset;
            }

            $data = self::$dataBase->selectLimit($sql, 100, $offset);
        }

        return $plist;
    }

    /**
     * Returns all the games of the exercise with offset.
     *
     * @param string $eje
     * @param int    $offset
     * @param int    $limit
     *
     * @return array
     */
    public function fullFromEjercicio($eje, $offset = 0, $limit = FS_ITEM_LIMIT)
    {
        $sql = 'SELECT a.numero,a.fecha,s.codsubcuenta,s.descripcion,p.concepto,p.debe,p.haber'
            . ' FROM co_asientos a, co_subcuentas s, co_partidas p'
            . ' WHERE a.codejercicio = ' . self::$dataBase->var2str($eje)
            . ' AND p.idasiento = a.idasiento AND p.idsubcuenta = s.idsubcuenta'
            . ' ORDER BY a.numero ASC, p.codsubcuenta ASC';

        $data = self::$dataBase->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            return $data;
        }

        return [];
    }

    /**
     * Counts the sub-account items.
     *
     * @param int $idsubc
     *
     * @return int
     */
    public function countFromSubcuenta($idsubc)
    {
        $sql = 'SELECT a.numero,a.fecha,p.idpartida FROM co_asientos a, co_partidas p'
            . ' WHERE a.idasiento = p.idasiento AND p.idsubcuenta = ' . self::$dataBase->var2str($idsubc)
            . ' ORDER BY a.numero ASC, p.idpartida ASC;';

        $ordenadas = self::$dataBase->select($sql);
        if (!empty($ordenadas)) {
            return count($ordenadas);
        }

        return 0;
    }

    /**
     * Returns the totals of the sub-account item.
     *
     * @param int $idsubc
     *
     * @return array
     */
    public function totalesFromSubcuenta($idsubc)
    {
        $sql = 'SELECT COALESCE(SUM(debe), 0) as debe,COALESCE(SUM(haber), 0) as haber'
            . ' FROM ' . static::tableName() . ' WHERE idsubcuenta = ' . self::$dataBase->var2str($idsubc) . ';';

        return $this->getTotalesFromSQL($sql);
    }

    /**
     * Returns the totals of the fiscal year of the game.
     *
     * @param string $cod
     *
     * @return array
     */
    public function totalesFromEjercicio($cod)
    {
        $sql = 'SELECT COALESCE(SUM(p.debe), 0) as debe,COALESCE(SUM(p.haber), 0) as haber'
            . ' FROM co_partidas p, co_asientos a'
            . ' WHERE p.idasiento = a.idasiento AND a.codejercicio = ' . self::$dataBase->var2str($cod) . ';';

        return $this->getTotalesFromSQL($sql);
    }

    /**
     * Make the received query and distribute the totals in debit, credit and balance.
     *
     * @param string $sql
     *
     * @return array
     */
    public function getTotalesFromSQL($sql)
    {
        $totales = ['debe' => 0, 'haber' => 0, 'saldo' => 0];
        $resultados = self::$dataBase->select($sql);
        if (!empty($resultados)) {
            $totales['debe'] = (float) $resultados[0]['debe'];
            $totales['haber'] = (float) $resultados[0]['haber'];
            $totales['saldo'] = (float) $resultados[0]['debe'] - (float) $resultados[0]['haber'];
        }

        return $totales;
    }

    /**
     * Returns the totals of the sub-accounts of the items between dates.
     *
     * @param int          $idsubc
     * @param string       $fechaini
     * @param string       $fechafin
     * @param array|bool   $excluir
     *
     * @return array
     */
    public function totalesFromSubcuentaFechas($idsubc, $fechaini, $fechafin, $excluir = false)
    {
        $totales = ['debe' => 0, 'haber' => 0, 'saldo' => 0];

        if ($excluir) {
            $sql = 'SELECT COALESCE(SUM(p.debe), 0) AS debe, COALESCE(SUM(p.haber), 0) AS haber'
                . ' FROM co_partidas p, co_asientos a'
                . ' WHERE p.idasiento = a.idasiento AND p.idsubcuenta = ' . self::$dataBase->var2str($idsubc)
                . ' AND a.fecha BETWEEN ' . self::$dataBase->var2str($fechaini)
                . ' AND ' . self::$dataBase->var2str($fechafin)
                . " AND p.idasiento NOT IN ('" . implode("','", $excluir) . "');";
            $resultados = self::$dataBase->select($sql);
        } else {
            $sql = 'SELECT COALESCE(SUM(p.debe), 0) AS debe, COALESCE(SUM(p.haber), 0) AS haber'
                . ' FROM co_partidas p, co_asientos a'
                . ' WHERE p.idasiento = a.idasiento AND p.idsubcuenta = ' . self::$dataBase->var2str($idsubc)
                . ' AND a.fecha BETWEEN ' . self::$dataBase->var2str($fechaini)
                . ' AND ' . self::$dataBase->var2str($fechafin) . ';';
            $resultados = self::$dataBase->select($sql);
        }

        if (!empty($resultados)) {
            $totales['debe'] = (float) $resultados[0]['debe'];
            $totales['haber'] = (float) $resultados[0]['haber'];
            $totales['saldo'] = (float) $resultados[0]['debe'] - (float) $resultados[0]['haber'];
        }

        return $totales;
    }
}
