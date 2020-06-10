<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

/**
 * NumberTools give us some basic and common methods for numbers.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class NumberTools
{

    public function __construct()
    {
        if (false === \defined('FS_NF1')) {
            \define('FS_NF1', ',');
        }

        if (false === \defined('FS_NF2')) {
            \define('FS_NF2', ' ');
        }
    }

    /**
     * Returns the number format with the number of decimals indicated.
     *
     * @param mixed $number
     * @param mixed $decimals
     *
     * @return string
     */
    public static function format($number, $decimals = \FS_NF0)
    {
        return \number_format((float) $number, (int) $decimals, \FS_NF1, \FS_NF2);
    }
}
