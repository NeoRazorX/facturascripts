<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use Skilla\ValidatorCifNifNie\Generator;
use Skilla\ValidatorCifNifNie\Validator;

/**
 * Verify numbers of fiscal identity
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class FiscalNumberValitator
{

    /**
     * Check the number depend of type and return true if the number if valid.
     *
     * @param string $type
     * @param string $number
     *
     * @return bool
     */
    public static function validate($type, $number)
    {
        $validator = new Validator(new Generator());

        switch (\strtolower($type)) {
            case 'cif':
                return $validator->isValidCIF($number);

            case 'dni':
                return $validator->isValidDNI($number);

            case 'nie':
                return $validator->isValidNIE($number);

            case 'nif':
                return $validator->isValidNIF($number);
        }
    }
}
