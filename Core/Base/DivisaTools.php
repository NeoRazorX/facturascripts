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
 * DivisaTools give us some basic and common methods for currency numbers.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class DivisaTools
{

    /**
     * Returns the value of the formatted currency.
     * 
     * @param float $number
     * @param int|string $decimals
     * @param bool $addSymbol
     * 
     * @return string
     */
    public function format($number, $decimals = FS_NF0, $addSymbol = true)
    {
        $txt = number_format($number, $decimals, FS_NF1, FS_NF2);
        if (!$addSymbol) {
            return $txt;
        }

        $symbol = '€';
        if (FS_POS_DIVISA === 'right') {
            return $txt . ' ' . $symbol;
        }

        return $symbol . ' ' . $txt;
    }
}
