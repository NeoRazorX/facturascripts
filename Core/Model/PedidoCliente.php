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
use FacturaScripts\Dinamic\Model\LineaPedidoCliente as LineaPedido;

/**
 * Customer order.
 * 
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PedidoCliente extends Base\SalesDocument
{

    use Base\ModelTrait;

    /**
     * Primary key.
     *
     * @var integer
     */
    public $idpedido;

    /**
     * Expected date of departure of the material.
     *
     * @var string
     */
    public $fechasalida;

    /**
     * Returns the lines associated with the order.
     *
     * @return LineaPedido[]
     */
    public function getLines()
    {
        $lineaModel = new LineaPedido();
        $where = [new DataBaseWhere('idpedido', $this->idpedido)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];

        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Returns a new line for the document.
     * 
     * @param array $data
     * @param array $exclude
     *
     * @return LineaPedido
     */
    public function getNewLine(array $data = [], array $exclude = ['actualizastock', 'idlinea', 'idpedido'])
    {
        $newLine = new LineaPedido();
        $newLine->idpedido = $this->idpedido;
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
        return 'idpedido';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'pedidoscli';
    }
}
