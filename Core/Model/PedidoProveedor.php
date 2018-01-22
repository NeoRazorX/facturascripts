<?php
/**
 * This file is part of presupuestos_y_pedidos
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;

/**
 * Supplier order.
 */
class PedidoProveedor
{
    use Base\DocumentoCompra;

    /**
     * Primary key.
     *
     * @var int
     */
    public $idpedido;

    /**
     * Related delivery note ID.
     *
     * @var int
     */
    public $idalbaran;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'pedidosprov';
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public function primaryColumn()
    {
        return 'idpedido';
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install()
    {
        new Serie();
        new Ejercicio();

        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->clearDocumentoCompra();
    }

    /**
     * Returns the lines associated with the order.
     *
     * @return LineaPedidoProveedor[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaPedidoProveedor();

        return $lineaModel->all([new DataBaseWhere('idpedido', $this->idpedido)]);
    }

    /**
     * Check the order data, return True if it is correct.
     *
     * @return boolean
     */
    public function test()
    {
        return $this->testTrait();
    }
}
