<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2014-2022  Carlos Garcia Gomez       <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\PedidoProveedor as DinPedidoProveedor;

/**
 * Supplier order line.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaPedidoProveedor extends Base\PurchaseDocumentLine
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

    public function getDocument(): DinPedidoProveedor
    {
        $pedido = new DinPedidoProveedor();
        $pedido->loadFromCode($this->idpedido);
        return $pedido;
    }

    public function install(): string
    {
        // needed dependency
        new PedidoProveedor();

        return parent::install();
    }

    public static function tableName(): string
    {
        return 'lineaspedidosprov';
    }

    public function url(string $type = 'auto', string $list = 'List'): string
    {
        if (null !== $this->idpedido) {
            return 'EditPedidoProveedor?code=' . $this->idpedido;
        }

        return parent::url($type, $list);
    }
}
