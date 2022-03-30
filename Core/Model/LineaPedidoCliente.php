<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2022    Carlos Garcia Gomez        <carlos@facturascripts.com>
 * Copyright (C) 2014         Francesc Pineda Segarra    <shawe.ewahs@gmail.com>
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

use FacturaScripts\Dinamic\Model\PedidoCliente as DinPedidoCliente;

/**
 * Customer order line
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaPedidoCliente extends Base\SalesDocumentLine
{

    use Base\ModelTrait;

    /**
     * Order ID.
     *
     * @var integer
     */
    public $idpedido;

    public function documentColumn(): string
    {
        return 'idpedido';
    }

    public function getDocument(): DinPedidoCliente
    {
        $pedido = new DinPedidoCliente();
        $pedido->loadFromCode($this->idpedido);
        return $pedido;
    }

    public function install(): string
    {
        // needed dependency
        new PedidoCliente();

        return parent::install();
    }

    public static function tableName(): string
    {
        return 'lineaspedidoscli';
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        if (null !== $this->idpedido) {
            return 'EditPedidoCliente?code=' . $this->idpedido;
        }

        return parent::url($type, $list);
    }
}
