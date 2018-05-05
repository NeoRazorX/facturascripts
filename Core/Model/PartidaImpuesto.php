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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\Model;

use FacturaScripts\Core;

/**
 * Auxiliary model to load a list of accounting entries with VAT
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class PartidaImpuesto extends Base\ModelView
{

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

    /**
     * List of tables required for the execution of the view.
     */
    protected function getTables(): array
    {
        return [
            'asientos',
            'partidas',
            'subcuentas'
        ];
    }

    /**
     * List of fields or columns to select clausule
     */
    protected function getFields(): array
    {
        return [
            'codejercicio' => 'asientos.codejercicio',
            'idasiento' => 'asientos.idasiento',
            'numero' => 'asientos.numero',
            'fecha' => 'asientos.fecha',
            'idpartida' => 'partidas.idpartida',
            'idcontrapartida' => 'partidas.idcontrapartida',
            'codcontrapartida' => 'partidas.codcontrapartida',
            'concepto' => 'partidas.concepto',
            'documento' => 'partidas.documento',
            'cifnif' => 'partidas.cifnif',
            'codserie' => 'partidas.codserie',
            'factura' => 'partidas.factura',
            'baseimponible' => 'partidas.baseimponible',
            'iva' => 'partidas.iva',
            'recargo' => 'partidas.recargo',
            'codcuentaesp' => 'subcuentas.codcuentaesp',
            'codimpuesto' => 'subcuentas.codimpuesto'
        ];
    }

    /**
     * List of tables related to from clausule
     */
    protected function getSQLFrom(): string
    {
        return 'asientos '
            . ' INNER JOIN partidas ON partidas.idasiento = asientos.idasiento'
            . ' INNER JOIN subcuentas ON subcuentas.idsubcuenta = partidas.idsubcuenta '
            . 'AND subcuentas.codimpuesto IS NOT NULL '
            . 'AND subcuentas.codcuentaesp IS NOT NULL';
    }

    /**
     * Return default order by
     */
    protected function getDefaultOrderBy(): string
    {
        return ' ORDER BY asientos.codejercicio ASC, partidas.codserie ASC, '
            . 'asientos.fecha ASC, partidas.factura ASC, partidas.documento ASC';
    }

    /**
     * Reset the values of all model view properties.
     */
    protected function clear()
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
     * Assign the values of the $data array to the model view properties.
     *
     * @param array $data
     */
    protected function loadFromData($data)
    {
        $this->codejercicio = $data['codejercicio'];
        $this->idasiento = $data['idasiento'];
        $this->numero = $data['numero'];
        $this->fecha = $data['fecha'];
        $this->idpartida = $data['idpartida'];
        $this->idcontrapartida = $data['idcontrapartida'];
        $this->codcontrapartida = $data['codcontrapartida'];
        $this->concepto = Core\Base\Utils::fixHtml($data['concepto']);
        $this->documento = $data['documento'];
        $this->cifnif = $data['cifnif'];
        $this->codserie = $data['codserie'];
        $this->factura = $data['factura'];
        $this->codcuentaesp = $data['codcuentaesp'];
        $this->codimpuesto = $data['codimpuesto'];
        $this->baseimponible = $data['baseimponible'];
        $this->iva = $data['iva'];
        $this->recargo = $data['recargo'];
        $this->cuotaiva = $this->baseimponible * ($this->iva / 100.00);
        $this->cuotarecargo = $this->baseimponible * ($this->recargo / 100.00);
    }
}
