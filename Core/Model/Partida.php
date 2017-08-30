<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

/**
 * La línea de un asiento.
 * Se relaciona con un asiento y una subcuenta.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class Partida
{
    use Base\ModelTrait;

    /**
     * Clave primaria.
     *
     * @var int
     */
    public $idpartida;

    /**
     * ID del asiento relacionado.
     *
     * @var int
     */
    public $idasiento;

    /**
     * ID de la subcuenta relacionada.
     *
     * @var int
     */
    public $idsubcuenta;

    /**
     * Código, que no ID, de la subcuenta relacionada.
     *
     * @var string
     */
    public $codsubcuenta;

    /**
     * TODO
     *
     * @var int
     */
    public $idconcepto;

    /**
     * TODO
     *
     * @var string
     */
    public $concepto;

    /**
     * TODO
     *
     * @var int
     */
    public $idcontrapartida;

    /**
     * TODO
     *
     * @var string
     */
    public $codcontrapartida;

    /**
     * TODO
     *
     * @var
     */
    public $punteada;

    /**
     * TODO
     *
     * @var float
     */
    public $tasaconv;

    /**
     * TODO
     *
     * @var string
     */
    public $coddivisa;

    /**
     * TODO
     *
     * @var float
     */
    public $haberme;

    /**
     * TODO
     *
     * @var float
     */
    public $debeme;

    /**
     * TODO
     *
     * @var float
     */
    public $recargo;

    /**
     * TODO
     *
     * @var float
     */
    public $iva;

    /**
     * TODO
     *
     * @var float
     */
    public $baseimponible;

    /**
     * TODO
     *
     * @var
     */
    public $factura;

    /**
     * TODO
     *
     * @var string
     */
    public $codserie;

    /**
     * TODO
     *
     * @var
     */
    public $tipodocumento;

    /**
     * TODO
     *
     * @var
     */
    public $documento;

    /**
     * TODO
     *
     * @var string
     */
    public $cifnif;

    /**
     * TODO
     *
     * @var float
     */
    public $haber;

    /**
     * TODO
     *
     * @var float
     */
    public $debe;

    /**
     * TODO
     *
     * @var int
     */
    public $numero;

    /**
     * TODO
     *
     * @var string
     */
    public $fecha;

    /**
     * TODO
     *
     * @var float
     */
    public $saldo;

    /**
     * TODO
     *
     * @var float
     */
    public $sum_debe;

    /**
     * TODO
     *
     * @var float
     */
    public $sum_haber;

    public function tableName()
    {
        return 'co_partidas';
    }

    public function primaryColumn()
    {
        return 'idpartida';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->idpartida = null;
        $this->idasiento = null;
        $this->idsubcuenta = null;
        $this->codsubcuenta = null;
        $this->idconcepto = null;
        $this->concepto = '';
        $this->idcontrapartida = null;
        $this->codcontrapartida = null;
        $this->punteada = false;
        $this->tasaconv = 1.0;
        $this->coddivisa = $this->defaultItems->codDivisa();
        $this->haberme = 0.0;
        $this->debeme = 0.0;
        $this->recargo = 0.0;
        $this->iva = 0.0;
        $this->baseimponible = 0.0;
        $this->factura = null;
        $this->codserie = null;
        $this->tipodocumento = null;
        $this->documento = null;
        $this->cifnif = null;
        $this->debe = 0.0;
        $this->haber = 0.0;
        $this->numero = 0.0;
        $this->fecha = date('d-m-Y');
        $this->saldo = 0.0;
        $this->sum_debe = 0.0;
        $this->sum_haber = 0.0;
    }

    /**
     * TODO
     *
     * @return bool|mixed
     */
    public function getSubcuenta()
    {
        $subcuenta = new Subcuenta();

        return $subcuenta->get($this->idsubcuenta);
    }

    /**
     * TODO
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
     * TODO
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
     * TODO
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

    public function test()
    {
        $this->concepto = self::noHtml($this->concepto);
        $this->documento = self::noHtml($this->documento);
        $this->cifnif = self::noHtml($this->cifnif);

        return true;
    }

    /**
     * Elimina los datos del registro de la base de datos
     *
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE idpartida = ' . $this->var2str($this->idpartida) . ';';
        if ($this->dataBase->exec($sql)) {
            $subc = $this->getSubcuenta();
            if ($subc) {
                $subc->save(); /// guardamos la subcuenta para actualizar su saldo
            }

            return true;
        }

        return false;
    }

    /**
     * TODO
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
            . ' WHERE a.idasiento = p.idasiento AND p.idsubcuenta = ' . $this->var2str($idsubc)
            . ' ORDER BY a.numero ASC, p.idpartida ASC;';

        $ordenadas = $this->dataBase->select($sql);
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
     * TODO
     *
     * @param int $idsubc
     *
     * @return array
     */
    public function fullFromSubcuenta($idsubc)
    {
        $plist = [];
        $sql = 'SELECT a.numero,a.fecha,p.idpartida FROM co_asientos a, co_partidas p'
            . ' WHERE a.idasiento = p.idasiento AND p.idsubcuenta = ' . $this->var2str($idsubc)
            . ' ORDER BY a.numero ASC, p.idpartida ASC';

        $saldo = 0;
        $sumDebe = 0;
        $sumHaber = 0;

        $partida = new self();
        $offset = 0;
        $data = $this->dataBase->selectLimit($sql, 100, $offset);
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

            $data = $this->dataBase->selectLimit($sql, 100, $offset);
        }

        return $plist;
    }

    /**
     * TODO
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
            . ' WHERE a.codejercicio = ' . $this->var2str($eje)
            . ' AND p.idasiento = a.idasiento AND p.idsubcuenta = s.idsubcuenta'
            . ' ORDER BY a.numero ASC, p.codsubcuenta ASC';

        $data = $this->dataBase->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            return $data;
        }

        return [];
    }

    /**
     * TODO
     *
     * @param int $idsubc
     *
     * @return int
     */
    public function countFromSubcuenta($idsubc)
    {
        $sql = 'SELECT a.numero,a.fecha,p.idpartida FROM co_asientos a, co_partidas p'
            . ' WHERE a.idasiento = p.idasiento AND p.idsubcuenta = ' . $this->var2str($idsubc)
            . ' ORDER BY a.numero ASC, p.idpartida ASC;';

        $ordenadas = $this->dataBase->select($sql);
        if (!empty($ordenadas)) {
            return count($ordenadas);
        }

        return 0;
    }

    /**
     * TODO
     *
     * @param int $idsubc
     *
     * @return array
     */
    public function totalesFromSubcuenta($idsubc)
    {
        $totales = ['debe' => 0, 'haber' => 0, 'saldo' => 0];
        $sql = 'SELECT COALESCE(SUM(debe), 0) as debe,COALESCE(SUM(haber), 0) as haber'
            . ' FROM ' . $this->tableName() . ' WHERE idsubcuenta = ' . $this->var2str($idsubc) . ';';

        $resultados = $this->dataBase->select($sql);
        if (!empty($resultados)) {
            $totales['debe'] = (float) $resultados[0]['debe'];
            $totales['haber'] = (float) $resultados[0]['haber'];
            $totales['saldo'] = (float) $resultados[0]['debe'] - (float) $resultados[0]['haber'];
        }

        return $totales;
    }

    /**
     * TODO
     *
     * @param string $cod
     *
     * @return array
     */
    public function totalesFromEjercicio($cod)
    {
        $totales = ['debe' => 0, 'haber' => 0, 'saldo' => 0];
        $sql = 'SELECT COALESCE(SUM(p.debe), 0) as debe,COALESCE(SUM(p.haber), 0) as haber'
            . ' FROM co_partidas p, co_asientos a'
            . ' WHERE p.idasiento = a.idasiento AND a.codejercicio = ' . $this->var2str($cod) . ';';

        $resultados = $this->dataBase->select($sql);
        if (!empty($resultados)) {
            $totales['debe'] = (float) $resultados[0]['debe'];
            $totales['haber'] = (float) $resultados[0]['haber'];
            $totales['saldo'] = (float) $resultados[0]['debe'] - (float) $resultados[0]['haber'];
        }

        return $totales;
    }

    /**
     * TODO
     *
     * @param int    $idsubc
     * @param string $fechaini
     * @param string $fechafin
     * @param bool   $excluir
     *
     * @return array
     */
    public function totalesFromSubcuentaFechas($idsubc, $fechaini, $fechafin, $excluir = false)
    {
        $totales = ['debe' => 0, 'haber' => 0, 'saldo' => 0];

        if ($excluir) {
            $sql = 'SELECT COALESCE(SUM(p.debe), 0) AS debe,
            COALESCE(SUM(p.haber), 0) AS haber FROM co_partidas p, co_asientos a
            WHERE p.idasiento = a.idasiento AND p.idsubcuenta = ' . $this->var2str($idsubc) . '
               AND a.fecha BETWEEN ' . $this->var2str($fechaini) . ' AND ' . $this->var2str($fechafin) . "
               AND p.idasiento NOT IN ('" . implode("','", $excluir) . "');";
            $resultados = $this->dataBase->select($sql);
        } else {
            $sql = 'SELECT COALESCE(SUM(p.debe), 0) AS debe,
            COALESCE(SUM(p.haber), 0) AS haber FROM co_partidas p, co_asientos a
            WHERE p.idasiento = a.idasiento AND p.idsubcuenta = ' . $this->var2str($idsubc) . '
               AND a.fecha BETWEEN ' . $this->var2str($fechaini) . ' AND ' . $this->var2str($fechafin) . ';';
            $resultados = $this->dataBase->select($sql);
        }

        if (!empty($resultados)) {
            $totales['debe'] = (float) $resultados[0]['debe'];
            $totales['haber'] = (float) $resultados[0]['haber'];
            $totales['saldo'] = (float) $resultados[0]['debe'] - (float) $resultados[0]['haber'];
        }

        return $totales;
    }
}
