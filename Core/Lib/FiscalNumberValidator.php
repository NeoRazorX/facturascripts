<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Verify numbers of fiscal identity
 *
 * @author Cristo M. Estévez Hernández  <cristom.estevez@gmail.com>
 * @author Carlos García Gómez          <carlos@facturascripts.com>
 */
class FiscalNumberValidator
{
    /**
     * @param ?string $number
     *
     * @return bool
     */
    public static function isValidRFC(?string $number): bool
    {
        $pattern = "/^[A-Z]{3,4}([0-9]{2})(1[0-2]|0[1-9])([0-3][0-9])([A-Z0-9]{3})$/";
        return !empty($number) && 1 === preg_match($pattern, $number);
    }

    /**
     * Validate RNC Rep. Dominicana
     * Accept two format : only number: "000000000" or official format: "000-00000-0"
     *
     * @param ?string $number
     *
     * @return bool
     */
    public static function isValidRNC(?string $number): bool
    {
        if (empty($number)) {
            return false;
        }

        $pattern = '/^[0-9]{3}-[0-9]{5}-[0-9]{1}+$/';
        $pattern2 = '/^[0-9]+$/';

        if (1 !== preg_match($pattern2, $number) && preg_match($pattern, $number)) {
            $number = str_replace('-', '', $number);
        }

        if (mb_strlen($number) != 9) {
            return false;
        }

        $seed = ['7', '9', '8', '6', '5', '4', '3', '2'];
        $validate = str_split($number);
        $step = 0;
        foreach ($seed as $key => $value) {
            $step += $value * $validate[$key];
        }
        $rest = $step % 11;

        if ($rest == 0) {
            $crc = 2;
        } elseif ($rest == 1) {
            $crc = 1;
        } else {
            $crc = 11 - $rest;
        }

        return $crc == $validate[8];
    }

    public static function isValidSpainCIF(?string $cif): bool
    {
        if (empty($cif) || mb_strlen($cif) !== 9 || false === ctype_digit(mb_substr($cif, 1, 7))) {
            return false;
        }

        $first = mb_substr($cif, 0, 1);
        $prefix = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'N', 'P', 'Q', 'R', 'S', 'U', 'V', 'W'];
        if (false === in_array($first, $prefix)) {
            return false;
        }

        $sumA = intval(mb_substr($cif, 2, 1)) + intval(mb_substr($cif, 4, 1)) + intval(mb_substr($cif, 6, 1));
        $sumB = static::sumDigits(intval(mb_substr($cif, 1, 1)) * 2) +
            static::sumDigits(intval(mb_substr($cif, 3, 1)) * 2) +
            static::sumDigits(intval(mb_substr($cif, 5, 1)) * 2) +
            static::sumDigits(intval(mb_substr($cif, 7, 1)) * 2);
        $sumC = $sumA + $sumB;
        $digE = intval(mb_substr((string)$sumC, -1));
        $dc = empty($digE) ? 0 : 10 - $digE;

        if (mb_substr($cif, -1) === (string)$dc) {
            return true;
        }

        return mb_substr($cif, -1) === mb_substr('JABCDEFGHI', $dc, 1);
    }

    public static function isValidSpainDNI(?string $dni): bool
    {
        if (empty($dni)) {
            return false;
        }

        $len = mb_strlen($dni);
        if ($len !== 9 && !($len === 8 && is_numeric($dni))) {
            return false;
        }

        if (false === ctype_digit(mb_substr($dni, 1, 7))) {
            return false;
        }

        if (is_numeric($dni)) {
            $mod = intval(intval($dni) % 23);
            $dni .= mb_substr('TRWAGMYFPDXBNJZSQVHLCKE', $mod, 1);
        }

        $first = mb_substr($dni, 0, 1);

        // primer carácter: dígito (DNI) o X/Y/Z (NIE)
        if (false === ctype_digit($first) && false === in_array($first, ['X', 'Y', 'Z'], true)) {
            return false;
        }

        $prefix = ['X' => '0', 'Y' => '1', 'Z' => '2'];
        $number = (ctype_digit($first) ? $first : $prefix[$first]) . mb_substr($dni, 1, 7);

        $mod = intval(intval($number) % 23);
        $letter = mb_substr('TRWAGMYFPDXBNJZSQVHLCKE', $mod, 1);
        return mb_substr($dni, -1) === $letter;
    }

    /**
     * Check the number depend on type and return true if the number if valid.
     *
     * @param ?string $type
     * @param ?string $number
     * @param bool $force
     * @return bool
     */
    public static function validate(?string $type, ?string $number, bool $force = false): bool
    {
        // does this fiscal identifier need validation?
        $fiscalId = new IdentificadorFiscal();
        if (empty($type) || false === $fiscalId->load($type)) {
            return true;
        }

        if (false === $fiscalId->validar && false === $force) {
            return true;
        }

        $upperNumber = strtoupper($number);

        switch (strtolower($type)) {
            case 'ci':
                return ValidadorEcuador::validarCedula($upperNumber);

            case 'cif':
                return static::isValidSpainCIF($upperNumber);

            case 'dni':
            case 'nie':
                return static::isValidSpainDNI($upperNumber);

            case 'nif':
                // desde el RD 1065/2007 el NIF engloba DNI/NIE y el antiguo CIF
                return static::isValidSpainDNI($upperNumber)
                    || static::isValidSpainCIF($upperNumber);

            case 'rfc':
                return static::isValidRFC($upperNumber);

            case 'rnc':
                return static::isValidRNC($upperNumber);

            case 'ruc':
                return ValidadorEcuador::validarRucNatural($upperNumber)
                    || ValidadorEcuador::validarRucPrivada($upperNumber)
                    || ValidadorEcuador::validarRucPublica($upperNumber);
        }

        return true;
    }


    private static function sumDigits(int $num): int
    {
        $str = (string)$num;
        if (mb_strlen($str) === 1) {
            return intval($str);
        }

        return intval(mb_substr($str, 0, 1)) + intval(mb_substr($str, 1, 1));
    }
}
