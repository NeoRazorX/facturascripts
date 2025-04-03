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

/**
 * Permite validar distintos tipos de datos.
 */
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
     * Devuelve true si el email es válido
     */
    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
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

    /**
     * Devuelve true si la fecha es válida en cualquier formato común (dd-mm-yyyy, mm/dd/yyyy, etc)
     */
    public static function date(string $date): bool
    {
        if (empty($date)) {
            return false;
        }

        // Intentar convertir la fecha a timestamp
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return false;
        }

        // Verificar que la fecha convertida sea válida
        return checkdate(
            (int)date('m', $timestamp),
            (int)date('d', $timestamp),
            (int)date('Y', $timestamp)
        );
    }

    /**
     * Devuelve true si la hora es válida en cualquier formato común (HH:ii:ss, HH:ii, etc)
     */
    public static function hour(string $hour): bool
    {
        if (empty($hour)) {
            return false;
        }

        // Intentar convertir la hora a timestamp
        $timestamp = strtotime($hour);
        if ($timestamp === false) {
            return false;
        }

        // Verificar que la hora sea válida (0-23 para horas, 0-59 para minutos y segundos)
        $h = (int)date('H', $timestamp);
        $i = (int)date('i', $timestamp);
        $s = (int)date('s', $timestamp);

        return $h >= 0 && $h <= 23 && 
               $i >= 0 && $i <= 59 && 
               $s >= 0 && $s <= 59;
    }

    /**
     * Devuelve true si la fecha y hora son válidas en cualquier formato común
     */
    public static function datetime(string $datetime): bool
    {
        if (empty($datetime)) {
            return false;
        }

        // Intentar convertir la fecha y hora a timestamp
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return false;
        }

        // Verificar que la fecha sea válida usando el método date()
        if (!static::date(date('Y-m-d', $timestamp))) {
            return false;
        }

        // Verificar que la hora sea válida usando el método hour()
        return static::hour(date('H:i:s', $timestamp));
    }
}
