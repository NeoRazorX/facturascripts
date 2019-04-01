<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2019  Carlos Garcia Gomez     <carlos@facturascripts.com>
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
use FacturaScripts\Dinamic\Model\LineaPresupuestoCliente;

/**
 * Customer estimation.
 * 
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PresupuestoCliente extends Base\SalesDocument
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var integer
     */
    public $idpresupuesto;

    /**
     * Date on which the validity of the estimation ends.
     *
     * @var string
     */
    public $finoferta;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->finoferta = date('d-m-Y', strtotime(date('d-m-Y') . ' +1 month'));
    }

    /**
     * Returns the lines associated with the estimation.
     *
     * @return LineaPresupuestoCliente[]
     */
    public function getLines()
    {
        $lineaModel = new LineaPresupuestoCliente();
        $where = [new DataBaseWhere('idpresupuesto', $this->idpresupuesto)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];

        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Returns a new line for this document.
     * 
     * @param array $data
     * 
     * @return LineaPresupuestoCliente
     */
    public function getNewLine(array $data = [])
    {
        $newLine = new LineaPresupuestoCliente($data);
        $newLine->idpresupuesto = $this->idpresupuesto;
        if (empty($data)) {
            $newLine->irpf = $this->irpf;
        }

        $status = $this->getStatus();
        $newLine->actualizastock = $status->actualizastock;

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
}
