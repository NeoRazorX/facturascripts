<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * Description of SalesDocumentLine
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
abstract class SalesDocumentLine extends BusinessDocumentLine
{

    /**
     * Line cost amount.
     *
     * @var float
     */
    public $coste;

    /**
     * False -> the quantity column is not displayed when printing.
     *
     * @var bool
     */
    public $mostrar_cantidad;

    /**
     * False -> price, discount, tax and total columns are not displayed when printing.
     *
     * @var bool
     */
    public $mostrar_precio;

    /**
     * Jump to a new page in the pdf if TRUE.
     *
     * @var bool
     */
    public $salto_pagina;

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->coste = 0.00;
        $this->mostrar_cantidad = true;
        $this->mostrar_precio = true;
        $this->salto_pagina = false;
    }
}
