<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Dinamic\Model\IdentificadorFiscal;
use Skilla\ValidatorCifNifNie\Generator;
use Skilla\ValidatorCifNifNie\Validator;
use Tavo\ValidadorEc;

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
        /// does this fiscal identifier need validation?
        $fiscalId = new IdentificadorFiscal();
        if (empty($type) || false === $fiscalId->loadFromCode($type) || false === $fiscalId->validar) {
            return true;
        }

        $upperNumber = \strtoupper($number);
        $validator = new Validator(new Generator());
        $validatorEC = new ValidadorEc();

        switch (\strtolower($type)) {
            case 'ci':
                return $validatorEC->validarCedula($upperNumber);

            case 'cif':
                return $validator->isValidCIF($upperNumber);

            case 'dni':
                return $validator->isValidDNI($upperNumber);

            case 'nie':
                return $validator->isValidNIE($upperNumber);

            case 'nif':
                return $validator->isValidNIF($upperNumber) || $validator->isValidDNI($upperNumber);

            case 'rfc':
                return static::isValidRFC($upperNumber);

            case 'rnc':
                return static::isValidRNC($upperNumber);

            case 'ruc':
                return $validatorEC->validarRucPersonaNatural($upperNumber) ||
                    $validatorEC->validarRucSociedadPrivada($upperNumber) ||
                    $validatorEC->validarRucSociedadPublica($upperNumber);
        }

        return true;
    }

    /**
     * 
     * @param string $number
     *
     * @return bool
     */
    protected static function isValidRFC($number)
    {
        $pattern = "/^[A-Z]{3,4}([0-9]{2})(1[0-2]|0[1-9])([0-3][0-9])([A-Z0-9]{3})$/";
        return 1 === preg_match($pattern, $number);
    }

    /**
     * Validate RNC Rep. Dominicana 
     * Accept two format : only number: "000000000" or official format: "000-00000-0"
     * 
     * @param string $number
     *
     * @return bool
     */
    protected static function isValidRNC($number)
    {
        $pattern = '/^[0-9]{3}-[0-9]{5}-[0-9]{1}+$/';
        $pattern2 = '/^[0-9]+$/';

        if (1 !== \preg_match($pattern2, $number) && \preg_match($pattern, $number)) {
            $number = \str_replace('-', '', $number);
        }

        if (\strlen($number) != 9) {
            return false;
        }

        $seed = ['7', '9', '8', '6', '5', '4', '3', '2'];
        $validate = \str_split($number);
        $step = 0;
        foreach ($seed as $key => $value) {
            $step += $value * $validate[$key];
        }
        $rest = $step % 11;
        $crc = 0;

        if ($rest == 0) {
            $crc = 2;
        } elseif ($rest == 1) {
            $crc = 1;
        } else {
            $crc = 11 - $rest;
        }

        return $crc == $validate[8];
    }
}
