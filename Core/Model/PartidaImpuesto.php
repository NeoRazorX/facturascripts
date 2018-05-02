<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Auxiliary model to load a list of accounting entries with VAT
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class PartidaImpuesto
{
    /**
     * It provides direct access to the database.
     *
     * @var Base\DataBase
     */
    private static $dataBase;

    /**
     * Exercise code of the accounting entry.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Related accounting entry ID.
     *
     * @var int
     */
    public $idasiento;

    /**
     * Accounting entry number.
     *
     * @var string
     */
    public $numero;

    /**
     * Date of the accounting entry.
     *
     * @var string
     */
    public $fecha;

    /**
     * Related line accounting entry ID.
     *
     * @var int
     */
    public $idpartida;

    /**
     * Related sub-account ID.
     *
     * @var int
     */
    public $idcontrapartida;

    /**
     * Code, not ID, of the related sub-account.
     *
     * @var string
     */
    public $codcontrapartida;

    /**
     * Concept.
     *
     * @var string
     */
    public $concepto;

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
     * Serie code.
     *
     * @var string
     */
    public $codserie;

    /**
     * Invoice of the departure.
     *
     * @var
     */
    public $factura;

    /**
     * Amount of the tax base.
     *
     * @var float|int
     */
    public $baseimponible;

    /**
     * VAT percentage.
     *
     * @var float|int
     */
    public $iva;

    /**
     * VAT amount.
     *
     * @var float|int
     */
    public $cuotaiva;

    /**
     * Equivalence surcharge percentage.
     *
     * @var float|int
     */
    public $recargo;

    /**
     * Equivalence surcharge amount.
     *
     * @var float|int
     */
    public $cuotarecargo;

    /**
     * Identifier of the special account.
     *
     * @var string
     */
    public $codcuentaesp;

    /**
     * Tax code.
     *
     * @var string
     */
    public $codimpuesto;

    private function tableName()
    {
        return 'asientos '
            . ' INNER JOIN partidas ON partidas.idasiento = asientos.idasiento'
            . ' INNER JOIN subcuentas ON subcuentas.idsubcuenta = partidas.idsubcuenta '
            .                       'AND subcuentas.codimpuesto IS NOT NULL '
            .                       'AND subcuentas.codcuentaesp IS NOT NULL';
    }

    private function fieldsList()
    {
        return 'asientos.codejercicio codejercicio, asientos.idasiento idasiento,'
            .  'asientos.numero numero, asientos.fecha fecha,'
            .  'partidas.idpartida idpartida, partidas.idcontrapartida idcontrapartida,'
            .  'partidas.codcontrapartida codcontrapartida, partidas.concepto concepto,'
            .  'partidas.documento documento, partidas.cifnif cifnif,'
            .  'partidas.codserie codserie, partidas.factura factura,'
            .  'partidas.baseimponible baseimponible, partidas.iva iva, partidas.recargo recargo,'
            .  'subcuentas.codcuentaesp codcuentaesp, subcuentas.codimpuesto codimpuesto';
    }

    /**
     * Set initial values
     */
    private function clear()
    {
        $this->codejercicio = null;
        $this->idasiento = null;
        $this->numero = null;
        $this->fecha = null;
        $this->idpartida = null;
        $this->idcontrapartida = null;
        $this->codcontrapartida = '';
        $this->concepto = '';
        $this->documento = '';
        $this->cifnif = '';
        $this->codserie = '';
        $this->factura = '';
        $this->baseimponible = 0.00;
        $this->iva = 0.00;
        $this->cuotaiva = 0.00;
        $this->recargo = 0.00;
        $this->cuotarecargo = 0.00;
        $this->codcuentaesp = null;
        $this->codimpuesto = null;
    }

    /**
     * Set data from array
     *
     * @param array $data
     */
    private function loadFromData($data)
    {
        $this->codejercicio = $data['codejercicio'];
        $this->idasiento = $data['idasiento'];
        $this->numero = $data['numero'];
        $this->fecha = $data['fecha'];
        $this->idpartida = $data['idpartida'];
        $this->idcontrapartida = $data['idcontrapartida'];
        $this->codcontrapartida = $data['codcontrapartida'];
        $this->concepto = Base\Utils::fixHtml($data['concepto']);
        $this->documento = $data['documento'];
        $this->cifnif = $data['cifnif'];
        $this->codserie = $data['codserie'];
        $this->factura = $data['factura'];
        $this->baseimponible = $data['baseimponible'];
        $this->iva = $data['iva'];
        $this->cuotaiva = $this->baseimponible * ($this->iva / 100.00);
        $this->recargo = $data['recargo'];
        $this->cuotarecargo = $this->baseimponible * ($this->recargo / 100.00);
        $this->codcuentaesp = $data['codcuentaesp'];
        $this->codimpuesto = $data['codimpuesto'];
    }

    /**
     * Constructor and class initializer.
     *
     * @param array $data
     */
    public function __construct($data = [])
    {
        if (empty($data)) {
            $this->clear();
        } else {
            $this->loadFromData($data);
        }
    }

    /**
     * Convert an array of filters order by in string.
     *
     * @param array $order
     *
     * @return string
     */
    private function getOrderBy(array $order)
    {
        if (empty($order)) {
            return ' ORDER BY asientos.codejercicio ASC, partidas.codserie ASC, '
                           . 'asientos.fecha ASC, partidas.factura ASC, partidas.documento ASC';
        }

        $result = '';
        $coma = ' ORDER BY ';
        foreach ($order as $key => $value) {
            $result .= $coma . $key . ' ' . $value;
            if ($coma === ' ORDER BY ') {
                $coma = ', ';
            }
        }

        return $result;
    }

    /**
     * Returns the number of records that meet the condition.
     *
     * @param DataBase\DataBaseWhere[] $where filters to apply to records.
     *
     * @return int
     */
    public function count(array $where = [])
    {
        if (self::$dataBase === null) {
            self::$dataBase = new Base\DataBase();
        }

        $sql = 'SELECT COUNT(1) AS total FROM ' . $this->tableName() . DataBaseWhere::getSQLWhere($where);
        $data = self::$dataBase->select($sql);
        return empty($data) ? 0 : $data[0]['total'];
    }

    /**
     * Load a Partida Impuesto list for the indicated where.
     *
     * @param DataBase\DataBaseWhere[] $where  filters to apply to model records.
     * @param array                    $order  fields to use in the sorting. For example ['code' => 'ASC']
     *
     * @return self[]
     */
    public function all(array $where, array $order = [], int $offset = 0, int $limit = 0)
    {
        if (self::$dataBase === null) {
            self::$dataBase = new Base\DataBase();
        }

        if (!self::$dataBase->tableExists('asientos') ||
            !self::$dataBase->tableExists('partidas') ||
            !self::$dataBase->tableExists('subcuentas'))
        {
            return [];
        }

        $result = [];
        $sqlWhere = DataBaseWhere::getSQLWhere($where);
        $sqlOrderBy = $this->getOrderBy($order);
        $sql = 'SELECT ' . $this->fieldsList() . ' FROM ' . $this->tableName() . $sqlWhere . ' ' . $sqlOrderBy;
        foreach (self::$dataBase->selectLimit($sql, $limit, $offset) as $d) {
            $result[] = new self($d);
        }

        return $result;
    }
}