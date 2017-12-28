<?php
/**
 * This file is part of FacturaScripts
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
 * Line of a customer's delivery note.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaAlbaranCliente
{

    use Base\LineaDocumentoVenta;

    /**
     * ID of the related order line, if there is one.
     *
     * @var int
     */
    public $idlineapedido;

    /**
     * Delivery note ID of this line.
     *
     * @var int
     */
    public $idalbaran;

    /**
     * Order ID of the related to the delivery note.
     *
     * @var int
     */
    public $idpedido;

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineasalbaranescli';
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
        new AlbaranCliente();
        
        return '';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->clearLinea();
        $this->idlineapedido = null;
        $this->idalbaran = null;
        $this->idpedido = null;
    }
}
