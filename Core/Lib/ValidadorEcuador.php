<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

class ValidadorEcuador
{
    public static function validarCedula(?string $number): bool
    {
        if (!static::validarInicial($number, 10)) {
            return false;
        }
        if (!static::validarProvincia(substr($number, 0, 2))) {
            return false;
        }
        if (!static::validarTercerDigito($number[2], 'cedula')) {
            return false;
        }
        if (!static::calcularDigito10(substr($number, 0, 9), $number[9])) {
            return false;
        }
        return true;
    }

    public static function validarRucNatural(?string $number): bool
    {
        if (!static::validarInicial($number, 13)) {
            return false;
        }
        if (!static::validarProvincia(substr($number, 0, 2))) {
            return false;
        }
        if (!static::validarTercerDigito($number[2], 'ruc_natural')) {
            return false;
        }
        if (!static::calcularDigito10(substr($number, 0, 9), $number[9])) {
            return false;
        }
        if (substr($number, 10, 3) < 1) {
            return false;
        }
        return true;
    }

    public static function validarRucPrivada(?string $number): bool
    {
        if (!static::validarInicial($number, 13)) {
            return false;
        }
        if (!static::validarProvincia(substr($number, 0, 2))) {
            return false;
        }
        if (!static::validarTercerDigito($number[2], 'ruc_privada')) {
            return false;
        }
        if (!static::calcularModulo11(substr($number, 0, 9), $number[9], 'ruc_privada')) {
            return false;
        }
        if (substr($number, 10, 3) < 1) {
            return false;
        }
        return true;
    }

    public static function validarRucPublica(?string $number): bool
    {
        if (!static::validarInicial($number, 13)) {
            return false;
        }
        if (!static::validarProvincia(substr($number, 0, 2))) {
            return false;
        }
        if (!static::validarTercerDigito($number[2], 'ruc_publica')) {
            return false;
        }
        if (!static::calcularModulo11(substr($number, 0, 8), $number[8], 'ruc_publica')) {
            return false;
        }
        if (substr($number, 9, 4) < 1) {
            return false;
        }
        return true;
    }

    public static function validarInicial(?string $number, int $characters): bool
    {
        if (empty($number) || strlen($number) !== $characters || !ctype_digit($number)) {
            return false;
        }
        return true;
    }

    public static function validarProvincia(string $number): bool
    {
        $provincia = (int)$number;
        if ($provincia < 0 || $provincia > 24) {
            return false;
        }
        return true;
    }

    protected static function validarTercerDigito(string $numero, string $tipo): bool
    {
        $digito = (int)$numero;
        switch ($tipo) {
            case 'cedula':
            case 'ruc_natural':
                if ($digito < 0 || $digito > 5) {
                    return false;
                }
                break;
            case 'ruc_privada':
                if ($digito != 9) {
                    return false;
                }
                break;

            case 'ruc_publica':
                if ($digito != 6) {
                    return false;
                }
                break;
            default:
                return false;
                break;
        }

        return true;
    }

    public static function calcularDigito10(string $number, int $digitoVerificador): bool
    {
        $arrayCoeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];

        $digitoVerificador = (int)$digitoVerificador;
        $digitosIniciales = str_split($number);

        $total = 0;
        foreach ($digitosIniciales as $key => $value) {
            $valorPosicion = ((int)$value * $arrayCoeficientes[$key]);

            if ($valorPosicion >= 10) {
                $valorPosicion = str_split($valorPosicion);
                $valorPosicion = array_sum($valorPosicion);
                $valorPosicion = (int)$valorPosicion;
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

    public static function calcularModulo11($number, $digitoVerificador, $tipo): bool
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

        $digitoVerificador = (int)$digitoVerificador;
        $digitosIniciales = str_split($number);

        $total = 0;
        foreach ($digitosIniciales as $key => $value) {
            $valorPosicion = ((int)$value * $arrayCoeficientes[$key]);
            $total = $total + $valorPosicion;
        }

        $residuo = $total % 11;

        $resultado = ($residuo == 0) ? 0 : 11 - $residuo;

        if ($resultado != $digitoVerificador) {
            return false;
        }

        return true;
    }
}
