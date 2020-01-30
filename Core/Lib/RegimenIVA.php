<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib;

/**
 * This class centralizes all common method for VAT Regime.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class RegimenIVA
{

    const TAX_SYSTEM_EXEMPT = 'Exento';
    const TAX_SYSTEM_GENERAL = 'General';
    const TAX_SYSTEM_SURCHARGE = 'Recargo';

    /**
     * Returns all the available options
     *
     * @return array
     */
    public static function all()
    {
        return [
            self::TAX_SYSTEM_EXEMPT => 'Exento',
            self::TAX_SYSTEM_GENERAL => 'General',
            self::TAX_SYSTEM_SURCHARGE => 'Recargo de equivalencia'
        ];
    }

    /**
     * Returns the default value
     *
     * @return string
     */
    public static function defaultValue()
    {
        return self::TAX_SYSTEM_GENERAL;
    }
}
