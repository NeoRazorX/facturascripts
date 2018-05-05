<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2018  Carlos Garcia Gomez       <carlos@facturascripts.com>
 * Copyright (C) 2014-2015  Francesc Pineda Segarra   <shawe.ewahs@gmail.com>
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
use FacturaScripts\Dinamic\Model\LineaPedidoProveedor;

/**
 * Supplier order.
 */
class PedidoProveedor extends Base\PurchaseDocument
{

    use Base\ModelTrait;

    /**
     * Related delivery note ID.
     *
     * @var int
     */
    public $idalbaran;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idpedido;

    /**
     * Returns the lines associated with the order.
     *
     * @return LineaPedidoProveedor[]
     */
    public function getLines()
    {
        $lineaModel = new LineaPedidoProveedor();
        $where = [new DataBaseWhere('idpedido', $this->idpedido)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];

        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Returns a new line for this document.
     * 
     * @param array $data
     *
     * @return LineaPedidoProveedor
     */
    public function getNewLine(array $data = [])
    {
        $newLine = new LineaPedidoProveedor($data);
        $newLine->idpedido = $this->idpedido;

        $state = $this->getState();
        $newLine->actualizastock = $state->actualizastock;

        return $newLine;
    }

    public function install()
    {
        parent::install();
        new AlbaranProveedor();

        return '';
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
        return 'pedidosprov';
    }
}
