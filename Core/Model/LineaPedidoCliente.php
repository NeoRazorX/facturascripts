<?php
/**
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017    Carlos Garcia Gomez        <carlos@facturascripts.com>
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

/**
 * Línea de pedido de cliente.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaPedidoCliente
{

    use Base\LineaDocumentoVenta;

    /**
     * ID de la linea relacionada en el presupuesto relacionado,
     * si lo hay.
     *
     * @var integer
     */
    public $idlineapresupuesto;

    /**
     * ID del pedido.
     *
     * @var integer
     */
    public $idpedido;

    /**
     * ID del presupuesto relacionado, si lo hay.
     *
     * @var integer
     */
    public $idpresupuesto;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineaspedidoscli';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearLinea();
        $this->idlineapresupuesto = null;
        $this->idpedido = null;
        $this->idpresupuesto = null;
    }
}
