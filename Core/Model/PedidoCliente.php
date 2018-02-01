<?php
/**
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2018    Carlos Garcia Gomez        <carlos@facturascripts.com>
 * Copyright (C) 2014         Francesc Pineda Segarra    <shawe.ewahs@gmail.com>
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
 * Customer order.
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
     * Related delivery note ID.
     *
     * @var integer
     */
    public $idalbaran;

    /**
     * Expected date of departure of the material.
     *
     * @var string
     */
    public $fechasalida;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'pedidoscli';
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
     * Returns the lines associated with the order.
     *
     * @return LineaPedidoCliente[]
     */
    public function getLineas()
    {
        $lineaModel = new LineaPedidoCliente();
        $where = [new DataBaseWhere('idpedido', $this->idpedido)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];

        return $lineaModel->all($where, $order);
    }

    /**
     * Remove the order from the database.
          * Returns False in case of failure.
     *
     * @return boolean
     */
    public function delete()
    {
        if (self::$dataBase->exec('DELETE FROM ' . static::tableName() . ' WHERE idpedido = ' . self::$dataBase->var2str($this->idpedido) . ';')) {
            /// we modify the related budget
            self::$dataBase->exec('UPDATE presupuestoscli SET idpedido = NULL, editable = TRUE,'
                . ' status = 0 WHERE idpedido = ' . self::$dataBase->var2str($this->idpedido) . ';');

            return true;
        }

        return false;
    }
}
