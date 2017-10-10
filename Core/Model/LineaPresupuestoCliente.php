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
 * Línea de presupuesto de cliente.
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class LineaPresupuestoCliente
{
    use Base\LineaDocumentoVenta;

    /**
     * ID del presupuesto.
     *
     * @var integer
     */
    public $idpresupuesto;

    public function tableName()
    {
        return 'lineaspresupuestoscli';
    }

    public function clear()
    {
        $this->clearLinea();
        $this->idpresupuesto = NULL;
    }
}
