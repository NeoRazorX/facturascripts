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

use FacturaScripts\Core\App\AppSettings;

/**
 * La línea de un asiento.
 * Se relaciona con un asiento y una subcuenta.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
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
     * Identificador del concepto
     *
     * @var int
     */
    public $idconcepto;

    /**
     * Concepto
     *
     * @var string
     */
    public $concepto;

    /**
     * Identificador de la contrapartida
     *
     * @var int
     */
    public $idcontrapartida;

    /**
     * Código de la contrapartida
     *
     * @var string
     */
    public $codcontrapartida;

    /**
     * True si está punteada, sino False
     *
     * @var bool
     */
    public $punteada;

    /**
     * Valor de la tasa de conversión
     *
     * @var float|int
     */
    public $tasaconv;

    /**
     * Código de la divisa
     *
     * @var string
     */
    public $coddivisa;

    /**
     * Haber de la partida
     *
     * @var float|int
     */
    public $haberme;

    /**
     * Debe de la partida
     *
     * @var float|int
     */
    public $debeme;

    /**
     * Importe del recargo
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Importe del iva
     *
     * @var float|int
     */
    public $iva;

    /**
     * Importe de la base imponible
     *
     * @var float|int
     */
    public $baseimponible;

    /**
     * Factura de la partida
     *
     * @var
     */
    public $factura;

    /**
     * Código de serie
     *
     * @var string
     */
    public $codserie;

    /**
     * Tipo de documento
     *
     * @var
     */
    public $tipodocumento;

    /**
     * Documento de la partida
     *
     * @var string
     */
    public $documento;

    /**
     * CIF/NIF de la partida
     *
     * @var string
     */
    public $cifnif;

    /**
     * Haber de la partida
     *
     * @var float|int
     */
    public $haber;

    /**
     * Debe de la partida
     *
     * @var float|int
     */
    public $debe;

    /**
     * Número de la partida
     *
     * @var int
     */
    public $numero;

    /**
     * Fecha
     *
     * @var string
     */
    public $fecha;

    /**
     * Saldo de la partida
     *
     * @var float|int
     */
    public $saldo;

    /**
     * Suma del debe
     *
     * @var float|int
     */
    public $sum_debe;

    /**
     * Suma del haber
     *
     * @var float|int
     */
    public $sum_haber;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'co_partidas';
    }

    /**
     * Devuelve el nombre de la columna que es clave primaria del modelo.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idpartida';
    }

    public function install()
    {
        new Asiento();
        new Subcuenta();

        return '';
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
        $this->coddivisa = AppSettings::get('default', 'coddivisa');
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
     * Devuelve la subcuenta de la partida
     *
     * @return bool|mixed
     */
    public function getSubcuenta()
    {
        $subcuenta = new Subcuenta();

        return $subcuenta->get($this->idsubcuenta);
    }

    /**
     * Devuelve la url de la subcuenta de la partida
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
     * Devuelve la subcuenta de la contrapartida
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
     * Devuelve la url de la subcuenta de la contrapartida
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
     * Devuelve true si no hay errores en los valores de las propiedades del modelo.
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
     * Elimina los datos del registro de la base de datos
     *
     * @return bool
     */
    public function delete()
    {
        $sql = 'DELETE FROM ' . $this->tableName() . ' WHERE idpartida = ' . $this->dataBase->var2str($this->idpartida) . ';';
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
     * Devuelve todas las partidas de la subcuenta desde el offset
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
            . ' WHERE a.idasiento = p.idasiento AND p.idsubcuenta = ' . $this->dataBase->var2str($idsubc)
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
     * Devuelve todas las partidas de la subcuenta
     *
     * @param int $idsubc
     *
     * @return array
     */
    public function fullFromSubcuenta($idsubc)
    {
        $plist = [];
        $sql = 'SELECT a.numero,a.fecha,p.idpartida FROM co_asientos a, co_partidas p'
            . ' WHERE a.idasiento = p.idasiento AND p.idsubcuenta = ' . $this->dataBase->var2str($idsubc)
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
     * Devuelve todas las partidas del ejercici con offset
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
            . ' WHERE a.codejercicio = ' . $this->dataBase->var2str($eje)
            . ' AND p.idasiento = a.idasiento AND p.idsubcuenta = s.idsubcuenta'
            . ' ORDER BY a.numero ASC, p.codsubcuenta ASC';

        $data = $this->dataBase->selectLimit($sql, $limit, $offset);
        if (!empty($data)) {
            return $data;
        }

        return [];
    }

    /**
     * Cuenta las partidas de la subcuenta
     *
     * @param int $idsubc
     *
     * @return int
     */
    public function countFromSubcuenta($idsubc)
    {
        $sql = 'SELECT a.numero,a.fecha,p.idpartida FROM co_asientos a, co_partidas p'
            . ' WHERE a.idasiento = p.idasiento AND p.idsubcuenta = ' . $this->dataBase->var2str($idsubc)
            . ' ORDER BY a.numero ASC, p.idpartida ASC;';

        $ordenadas = $this->dataBase->select($sql);
        if (!empty($ordenadas)) {
            return count($ordenadas);
        }

        return 0;
    }

    /**
     * Devuelve los totales de la partida de la subcuenta
     *
     * @param int $idsubc
     *
     * @return array
     */
    public function totalesFromSubcuenta($idsubc)
    {
        $sql = 'SELECT COALESCE(SUM(debe), 0) as debe,COALESCE(SUM(haber), 0) as haber'
            . ' FROM ' . $this->tableName() . ' WHERE idsubcuenta = ' . $this->dataBase->var2str($idsubc) . ';';

        return $this->getTotalesFromSQL($sql);
    }

    /**
     * Devuelve los totales del ejercicio de la partida
     *
     * @param string $cod
     *
     * @return array
     */
    public function totalesFromEjercicio($cod)
    {
        $sql = 'SELECT COALESCE(SUM(p.debe), 0) as debe,COALESCE(SUM(p.haber), 0) as haber'
            . ' FROM co_partidas p, co_asientos a'
            . ' WHERE p.idasiento = a.idasiento AND a.codejercicio = ' . $this->dataBase->var2str($cod) . ';';

        return $this->getTotalesFromSQL($sql);
    }

    /**
     * Realiza la consulta recibida y reparte los totales en debe, haber y saldo
     *
     * @param string $sql
     *
     * @return array
     */
    public function getTotalesFromSQL($sql)
    {
        $totales = ['debe' => 0, 'haber' => 0, 'saldo' => 0];
        $resultados = $this->dataBase->select($sql);
        if (!empty($resultados)) {
            $totales['debe'] = (float) $resultados[0]['debe'];
            $totales['haber'] = (float) $resultados[0]['haber'];
            $totales['saldo'] = (float) $resultados[0]['debe'] - (float) $resultados[0]['haber'];
        }

        return $totales;
    }

    /**
     * Devuelve los totales de las subcuentas de las partidas entre fechas
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
            $sql = 'SELECT COALESCE(SUM(p.debe), 0) AS debe,
            COALESCE(SUM(p.haber), 0) AS haber FROM co_partidas p, co_asientos a
            WHERE p.idasiento = a.idasiento AND p.idsubcuenta = ' . $this->dataBase->var2str($idsubc) . '
               AND a.fecha BETWEEN ' . $this->dataBase->var2str($fechaini) . ' AND ' . $this->dataBase->var2str($fechafin) . "
               AND p.idasiento NOT IN ('" . implode("','", $excluir) . "');";
            $resultados = $this->dataBase->select($sql);
        } else {
            $sql = 'SELECT COALESCE(SUM(p.debe), 0) AS debe,
            COALESCE(SUM(p.haber), 0) AS haber FROM co_partidas p, co_asientos a
            WHERE p.idasiento = a.idasiento AND p.idsubcuenta = ' . $this->dataBase->var2str($idsubc) . '
               AND a.fecha BETWEEN ' . $this->dataBase->var2str($fechaini) . ' AND ' . $this->dataBase->var2str($fechafin) . ';';
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
