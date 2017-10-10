<?php
/*
 * This file is part of presupuestos_y_pedidos
 * Copyright (C) 2014-2017    Carlos Garcia Gomez        neorazorx@gmail.com
 * Copyright (C) 2014         Francesc Pineda Segarra    shawe.ewahs@gmail.com
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
 * @author Carlos García Gómez <neorazorx@gmail.com>
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

    public function tableName()
    {
        return 'lineaspedidoscli';
    }

    public function clear()
    {
        $this->clearLinea();
        $this->idlineapresupuesto = NULL;
        $this->idpedido = NULL;
        $this->idpresupuesto = NULL;
    }
}
