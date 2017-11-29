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
namespace FacturaScripts\Core\Base;

/**
 * Description of DivisaTools
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DivisaTools
{

    /**
     * Devuelve el valor de la divisa formateada
     *
     * @param float $number
     * @param int $decimals
     *
     * @return string
     */
    public function format($number, $decimals = FS_NF0)
    {
        $symbol = '€';
        $txt = number_format($number, $decimals, FS_NF1, FS_NF2);

        if (FS_POS_DIVISA == 'right') {
            return $txt . ' ' . $symbol;
        }

        return $symbol . ' ' . $txt;
    }
}
