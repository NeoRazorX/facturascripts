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
 * Auxiliary model to load a resume of accounting entries with VAT
 *
 * @author Artex Trading sa     <jcuello@artextrading.com>
 */
class PartidaImpuestoResumen extends Base\ModelView
{

    /**
     * Exercise code of the accounting entry.
     *
     * @var string
     */
    public $codejercicio;

    /**
     * Identifier of the special account.
     *
     * @var string
     */
    public $codcuentaesp;

    /**
     * Description of the special account.
     *
     * @var string
     */
    public $descripcion;

    /**
     * Tax code.
     *
     * @var string
     */
    public $codimpuesto;

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
     * Return default order by
     */
    protected function getDefaultOrderBy(): string
    {
        return 'ORDER BY subcuentas.codcuentaesp, partidas.iva, partidas.recargo';
    }

    /**
     * Return Group By clausule
     */
    protected function getGroupBy(): string
    {
        return 'GROUP BY subcuentas.codcuentaesp, cuentasesp.descripcion,'
            . 'subcuentas.codimpuesto, partidas.iva, partidas.recargo';
    }

    /**
     * List of fields or columns to select clausule
     */
    protected function getFields(): array
    {
        return [
            'codejercicio' => 'asiento.codejercicio',
            'codcuentaesp' => 'subcuentas.codcuentaesp',
            'descripcion' => 'cuentasesp.descripcion',
            'codimpuesto' => 'subcuentas.codimpuesto',
            'iva' => 'partidas.iva',
            'recargo' => 'partidas.recargo',
            'baseimponible' => 'SUM(partidas.baseimponible)'
        ];
    }

    /**
     * List of tables related to from clausule
     */
    protected function getSQLFrom(): string
    {
        return 'asientos'
            . ' INNER JOIN partidas ON partidas.idasiento = asientos.idasiento'
            . ' INNER JOIN subcuentas ON subcuentas.idsubcuenta = partidas.idsubcuenta'
            . ' AND subcuentas.codimpuesto IS NOT NULL'
            . ' AND subcuentas.codcuentaesp IS NOT NULL'
            . ' LEFT JOIN cuentasesp ON cuentasesp.codcuentaesp = subcuentas.codcuentaesp';
    }

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
     * Reset the values of all model view properties.
     */
    protected function clear()
    {
        $this->codejercicio = null;
        $this->codcuentaesp = null;
        $this->descripcion = null;
        $this->codimpuesto = null;
        $this->baseimponible = 0.00;
        $this->iva = 0.00;
        $this->recargo = 0.00;
        $this->cuotaiva = 0.00;
        $this->cuotarecargo = 0.00;
    }

    /**
     * Assign the values of the $data array to the model view properties.
     *
     * @param array $data
     */
    protected function loadFromData($data)
    {
        $this->codejercicio = $data['codejercicio'];
        $this->codcuentaesp = $data['codcuentaesp'];
        $this->descripcion = Core\Base\Utils::fixHtml($data['descripcion']);
        $this->codimpuesto = $data['codimpuesto'];
        $this->baseimponible = $data['baseimponible'];
        $this->iva = $data['iva'];
        $this->recargo = $data['recargo'];
        $this->cuotaiva = $this->baseimponible * ($this->iva / 100.00);
        $this->cuotarecargo = $this->baseimponible * ($this->recargo / 100.00);
    }
}
