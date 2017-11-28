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
 * Línea de una factura de proveedor.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class LineaFacturaProveedor
{

    use Base\LineaDocumentoCompra;

    /**
     * ID de la linea del albarán relacionado, si lo hay.
     *
     * @var int
     */
    public $idlineaalbaran;

    /**
     * ID de la factura de esta línea.
     *
     * @var int
     */
    public $idfactura;

    /**
     * ID del albarán relacionado con la factura, si lo hay.
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
        return 'lineasfacturasprov';
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        $this->clearLinea();
        $this->idlineaalbaran = null;
        $this->idfactura = null;
        $this->idalbaran = null;
    }
}
