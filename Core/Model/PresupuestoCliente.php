<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2020  Carlos Garcia Gomez     <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\LineaPresupuestoCliente as LineaPresupuesto;

/**
 * Customer estimation.
 * 
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PresupuestoCliente extends Base\SalesDocument
{

    use Base\ModelTrait;

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

    /**
     * Returns the lines associated with the estimation.
     *
     * @return LineaPresupuesto[]
     */
    public function getLines()
    {
        $lineaModel = new LineaPresupuesto();
        $where = [new DataBaseWhere('idpresupuesto', $this->idpresupuesto)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];
        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Returns a new line for this document.
     * 
     * @param array $data
     * @param array $exclude
     * 
     * @return LineaPresupuesto
     */
    public function getNewLine(array $data = [], array $exclude = ['actualizastock', 'idlinea', 'idpresupuesto'])
    {
        $newLine = new LineaPresupuesto();
        $newLine->idpresupuesto = $this->idpresupuesto;
        $newLine->irpf = $this->irpf;
        $newLine->actualizastock = $this->getStatus()->actualizastock;

        $newLine->loadFromData($data, $exclude);
        return $newLine;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn()
    {
        return 'idpresupuesto';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'presupuestoscli';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        /// finoferta can't be previous to fecha
        if (!empty($this->finoferta) && \strtotime($this->finoferta) < \strtotime($this->fecha)) {
            $this->finoferta = null;
        }

        return parent::test();
    }
}
