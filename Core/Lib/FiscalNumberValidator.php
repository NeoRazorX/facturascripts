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
use Tavo\ValidadorEc;

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

        if (strlen($number) != 9) {
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
        if (empty($cif) || strlen($cif) !== 9 || false === is_numeric(substr($cif, 1, 7))) {
            return false;
        }

        $first = substr($cif, 0, 1);
        $prefix = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'N', 'P', 'Q', 'R', 'S', 'U', 'V', 'W'];
        if (false === in_array($first, $prefix)) {
            return false;
        }

        $sumA = intval(substr($cif, 2, 1)) + intval(substr($cif, 4, 1)) + intval(substr($cif, 6, 1));
        $sumB = static::sumDigits(intval(substr($cif, 1, 1)) * 2) +
            static::sumDigits(intval(substr($cif, 3, 1)) * 2) +
            static::sumDigits(intval(substr($cif, 5, 1)) * 2) +
            static::sumDigits(intval(substr($cif, 7, 1)) * 2);
        $sumC = $sumA + $sumB;
        $digE = intval(substr($sumC, -1));
        $dc = empty($digE) ? 0 : 10 - $digE;

        if (substr($cif, -1) === (string)$dc) {
            return true;
        }

        return substr($cif, -1) === substr('JABCDEFGHI', $dc, 1);
    }

    public static function isValidSpainDNI(?string $dni): bool
    {
        if (empty($dni) || strlen($dni) < 8 || false === is_numeric(substr($dni, 1, 7))) {
            return false;
        }

        if (is_numeric($dni)) {
            $mod = intval(intval($dni) % 23);
            $dni .= substr('TRWAGMYFPDXBNJZSQVHLCKE', $mod, 1);
        }

        $number = filter_var($dni, FILTER_SANITIZE_NUMBER_INT);
        $first = substr($dni, 0, 1);
        switch ($first) {
            case 'Y':
                $number = '1' . $number;
                break;

            case 'Z':
                $number = '2' . $number;
                break;
        }

        $mod = intval(intval($number) % 23);
        $letter = substr('TRWAGMYFPDXBNJZSQVHLCKE', $mod, 1);
        return substr($dni, -1) === $letter;
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
                $validatorEC = new ValidadorEc();
                return static::validarCedula($upperNumber);

            case 'cif':
                return static::isValidSpainCIF($upperNumber);

            case 'dni':
            case 'nie':
            case 'nif':
                return static::isValidSpainDNI($upperNumber);

            case 'rfc':
                return static::isValidRFC($upperNumber);

            case 'rnc':
                return static::isValidRNC($upperNumber);

            case 'ruc':
                $validatorEC = new ValidadorEc();
                return static::validarRucNatural($upperNumber)
                    || static::validarRucPrivada($upperNumber)
                    || static::validarRucPublica($upperNumber);
        }

        return true;
    }

    public function validarCedula(?string $number): bool
    {
        if (!$this->validarInicial($number, 10)) {
            return false;
        }
        if (!$this->validarProvincia(substr($number, 0, 2))) {
            return false;
        }
        if (!$this->validarTercerDigito($number[2], 'cedula')) {
            return false;
        }
        if (!$this->calcularDigito10(substr($number, 0, 9), $number[9])) {
            return false;
        }
        return true;
    }

    public function validarRucNatural(?string $number): bool
    {
        if (!$this->validarInicial($number, 13)) {
            return false;
        }
        if (!$this->validarProvincia(substr($number, 0, 2))) {
            return false;
        }
        if (!$this->validarTercerDigito($number[2], 'ruc_natural')) {
            return false;
        }
        if (!$this->calcularDigito10(substr($number, 0, 9), $number[9])) {
            return false;
        }
        if (substr($number, 10, 3) < 1) {
            return false;
        }
        return true;
    }

    public function validarRucPrivada(?string $number): bool
    {
        if (!$this->validarInicial($number, 13)) {
            return false;
        }
        if (!$this->validarProvincia(substr($number, 0, 2))) {
            return false;
        }
        if (!$this->validarTercerDigito($number[2], 'ruc_privada')) {
            return false;
        }
        if (!$this->calcularModulo11(substr($number, 0, 9), $number[9], 'ruc_privada')) {
            return false;
        }
        if (substr($number, 10, 3) < 1) {
            return false;
        }
        return true;
    }

    public function validarRucPublica(?string $number): bool
    {
        if (!$this->validarInicial($number, 13)) {
            return false;
        }
        if (!$this->validarProvincia(substr($number, 0, 2))) {
            return false;
        }
        if (!$this->validarTercerDigito($number[2], 'ruc_publica')) {
            return false;
        }
        if (!$this->calcularModulo11(substr($number, 0, 8), $number[8], 'ruc_publica')) {
            return false;
        }
        if (substr($number, 9, 4) < 1) {
            return false;
        }
        return true;
    }

    public function validarInicial(string $number, int $characters) : bool 
    {
        $num = (string)$number;
        if (!empty($num) && strlen($num) === $characters && ctype_digit($num)) {
            return true;
        }
        return false;
    }

    public function validarProvincia(string $number): bool
    {
        if ($number < 0 || $number > 24) {
            return false;
        }
        return true;
    }

    protected function validarTercerDigito($numero, $tipo)
    {
        switch ($tipo) {
            case 'cedula':
            case 'ruc_natural':
                if ($numero < 0 || $numero > 5) {
                    return false;
                }
                break;
            case 'ruc_privada':
                if ($numero != 9) {
                    return false;
                }
                break;

            case 'ruc_publica':
                if ($numero != 6) {
                    return false;
                }
                break;
            default:
                return false;
                break;
        }

        return true;
    }

    public function calcularDigito10(string $number, int $digitoVerificador): int
    {
        $arrayCoeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];

        $digitoVerificador = (int) $digitoVerificador;
        $digitosIniciales = str_split($number);

        $total = 0;
        foreach ($digitosIniciales as $key => $value) {
            $valorPosicion = ((int) $value * $arrayCoeficientes[$key]);

            if ($valorPosicion >= 10) {
                $valorPosicion = str_split($valorPosicion);
                $valorPosicion = array_sum($valorPosicion);
                $valorPosicion = (int) $valorPosicion;
            }

            $total = $total + $valorPosicion;
        }

        $residuo = $total % 10;

        $resultado = ($residuo == 0) ? 0 : 10 - $residuo;

        if ($resultado != $digitoVerificador) {
            return false;
        }

        return true;
    }

    public function calcularModulo11($number, $digitoVerificador, $tipo)
    {
        switch ($tipo) {
            case 'ruc_privada':
                $arrayCoeficientes = [4, 3, 2, 7, 6, 5, 4, 3, 2];
                break;
            case 'ruc_publica':
                $arrayCoeficientes = [3, 2, 7, 6, 5, 4, 3, 2];
                break;
            default:
                return false;
                break;
        }

        $digitoVerificador = (int) $digitoVerificador;
        $digitosIniciales = str_split($number);

        $total = 0;
        foreach ($digitosIniciales as $key => $value) {
            $valorPosicion = ((int) $value * $arrayCoeficientes[$key]);
            $total = $total + $valorPosicion;
        }

        $residuo = $total % 11;

        $resultado = ($residuo == 0) ? 0 : 11 - $residuo;

        if ($resultado != $digitoVerificador) {
            return false;
        }

        return true;
    }

    private static function sumDigits(int $num): int
    {
        if (strlen($num) === 1) {
            return intval($num);
        }

        return intval(substr($num, 0, 1)) + intval(substr($num, 1, 1));
    }
}
