<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Model\Base;

/**
 * Línea de documento de venta.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class SalesDocumentLine extends BusinessDocumentLine
{
    /**
     * Importe del coste de la línea.
     *
     * @var float
     */
    public $coste;

    /**
     * False -> no se muestra la columna de cantidad al imprimir.
     *
     * @var bool
     */
    public $mostrar_cantidad;

    /**
     * False -> no se muestran las columnas de precio, descuento, impuesto y total al imprimir.
     *
     * @var bool
     */
    public $mostrar_precio;

    /**
     * Salto de página en el pdf si es TRUE.
     *
     * @var bool
     */
    public $salto_pagina;

    /**
     * Restablece los valores de todas las propiedades del modelo.
     */
    public function clear(): void
    {
        parent::clear();

        $this->coste = 0.00;
        $this->mostrar_cantidad = true;
        $this->mostrar_precio = true;
        $this->salto_pagina = false;
    }
}
