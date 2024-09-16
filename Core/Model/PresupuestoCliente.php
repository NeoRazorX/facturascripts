<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2022  Carlos Garcia Gomez     <carlos@facturascripts.com>
 * Copyright (C) 2014       Francesc Pineda Segarra <shawe.ewahs@gmail.com>
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
use FacturaScripts\Core\Model\Base\ModelTrait;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\LineaPresupuestoCliente as LineaPresupuesto;

/**
 * Customer estimation.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PresupuestoCliente extends SalesDocument
{
    use ModelTrait;

    /**
     * Date on which the validity of the estimation ends.
     *
     * @var string
     */
    public $finoferta;

    /**
     * Primary key.
     *
     * @var integer
     */
    public $idpresupuesto;

    public function clear()
    {
        parent::clear();

        // set default expiration
        $expirationDays = Tools::settings('default', 'finofertadays');
        if ($expirationDays) {
            $this->finoferta = Tools::date('+' . $expirationDays . ' days');
        }
    }

    /**
     * Returns the lines associated with the estimation.
     *
     * @return LineaPresupuesto[]
     */
    public function getLines(): array
    {
        $lineaModel = new LineaPresupuesto();
        $where = [new DataBaseWhere('idpresupuesto', $this->idpresupuesto)];
        $orderBy = ['orden' => 'DESC', 'idlinea' => 'ASC'];
        return $lineaModel->all($where, $orderBy, 0, 0);
    }

    /**
     * Returns a new line for this document.
     *
     * @param array $data
     * @param array $exclude
     *
     * @return LineaPresupuesto
     */
    public function getNewLine(array $data = [], array $exclude = ['actualizastock', 'idlinea', 'idpresupuesto', 'servido'])
    {
        $newLine = new LineaPresupuesto();
        $newLine->idpresupuesto = $this->idpresupuesto;
        $newLine->irpf = $this->irpf;
        $newLine->actualizastock = $this->getStatus()->actualizastock;
        $newLine->loadFromData($data, $exclude);

        // allow extensions
        $this->pipe('getNewLine', $newLine, $data, $exclude);

        return $newLine;
    }

    public static function primaryColumn(): string
    {
        return 'idpresupuesto';
    }

    public static function tableName(): string
    {
        return 'presupuestoscli';
    }

    public function test(): bool
    {
        // finoferta can't be previous to fecha
        if (!empty($this->finoferta) && strtotime($this->finoferta) < strtotime($this->fecha)) {
            $this->finoferta = null;
        }

        return parent::test();
    }
}
