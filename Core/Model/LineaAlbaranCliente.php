<?php
/**
 * This file is part of facturacion_base
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * Línea de un albarán de cliente.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaAlbaranCliente
{

    use Base\LineaDocumentoVenta;

    /**
     * ID de la línea del pedido relacionado, si es que lo hay.
     *
     * @var int
     */
    public $idlineapedido;

    /**
     * ID del albaran de esta línea.
     *
     * @var int
     */
    public $idalbaran;

    /**
     * ID del pedido relacionado con el albarán relacionado.
     *
     * @var int
     */
    public $idpedido;

    /**
     * Devuelve el nombre de la tabla que usa este modelo.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineasalbaranescli';
    }

    /**
     * Resetea los valores de todas las propiedades modelo.
     */
    public function clear()
    {
        $this->clearLinea();
        $this->idlineapedido = null;
        $this->idalbaran = null;
        $this->idpedido = null;
    }
}
