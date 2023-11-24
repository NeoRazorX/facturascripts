<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2019-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core;

class Validator
{
    /**
     * Devuelve true si $text contiene solamente números, letras y los caracteres $extra.
     * También comprueba si la longitud de $text está entre $min y $max.
     */
    public static function alphaNumeric(string $text, string $extra = '', int $min = 1, int $max = 99): bool
    {
        $replace = [
            '[' => '\[',
            ']' => '\]',
            '^' => '\^',
            '$' => '\$',
            '.' => '\.',
            '|' => '\|',
            '?' => '\?',
            '*' => '\*',
            '+' => '\+',
            '(' => '\(',
            ')' => '\)',
            '/' => '\/',
            '\\' => '\\\\'
        ];
        $extra = strtr($extra, $replace);
        $pattern = '/^[a-zA-Z0-9' . $extra . ']{' . $min . ',' . $max . '}$/';
        return preg_match($pattern, $text) === 1;
    }

    /**
     * Devuelve true si la fecha es válida. Los formatos válidos son: yyyy-mm-dd y dd-mm-yyyy
     */
    public static function date(string $date): bool
    {
        // si no tiene 2 / o 2 - entonces no es una fecha válida
        if (substr_count($date, '/') !== 2 && substr_count($date, '-') !== 2) {
            return false;
        }

        // separamos la fecha en partes
        $separator = substr_count($date, '/') === 2 ? '/' : '-';
        $parts = explode($separator, $date);

        // si el primero es de 4 dígitos, entonces es yyyy-mm-dd
        if (strlen($parts[0]) === 4) {
            return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
        }

        // si el último es de 4 dígitos, entonces es dd-mm-yyyy
        if (strlen($parts[2]) === 4) {
            return checkdate((int)$parts[1], (int)$parts[0], (int)$parts[2]);
        }

        return false;
    }

    /**
     * Devuelve true si la fecha y hora es válida. Los formatos válidos son: yyyy-mm-dd hh:mm:ss y dd-mm-yyyy hh:mm:ss
     */
    public static function dateTime(string $date): bool
    {
        // si no tiene un espacio, entonces no es una fecha válida
        if (substr_count($date, ' ') !== 1) {
            return false;
        }

        // separamos la fecha en partes
        $parts = explode(' ', $date);

        // comprobamos la fecha
        return self::date($parts[0]) && self::hour($parts[1]);
    }

    /**
     * Devuelve true si el email es válido
     */
    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Devuelve true si la hora es válida. Los formatos válidos son: hh:mm:ss y hh:mm
     */
    public static function hour(string $time): bool
    {
        // si no tiene ningún : entonces no es una hora válida
        if (substr_count($time, ':') < 1) {
            return false;
        }

        // separamos la hora en partes
        $parts = explode(':', $time);

        // comprobamos la hora si tiene 2 partes
        if (count($parts) == 2) {
            return $parts[0] >= 0 && $parts[0] <= 23 &&
                $parts[1] >= 0 && $parts[1] <= 59;
        }

        // comprobamos la hora si tiene 3 partes
        return $parts[0] >= 0 && $parts[0] <= 23 &&
            $parts[1] >= 0 && $parts[1] <= 59 &&
            $parts[2] >= 0 && $parts[2] <= 59;
    }

    /**
     * Devuelve true si la cadena tiene entre $min y $max caracteres
     */
    public static function string(string $text, int $min = 1, int $max = 99): bool
    {
        return strlen($text) >= $min && strlen($text) <= $max;
    }

    /**
     * Devuelve true si la $url es válida.
     * Si $strict es true, entonces la url debe comenzar por http, https o cualquier otro protocolo válido.
     */
    public static function url(string $url, bool $strict = false): bool
    {
        // si la url está vacía o comienza por javascript: entonces no es una url válida
        if (empty($url) || stripos($url, 'javascript:') === 0) {
            return false;
        }

        // si la url comienza por www, entonces se añade https://
        if (false === $strict && stripos($url, 'www.') === 0) {
            $url = 'https://' . $url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}
