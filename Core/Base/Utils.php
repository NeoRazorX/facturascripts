<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
 * Description of Utils
 *
 * @author Carlos García Gómez
 */
trait Utils {

    /**
     * Convierte una variable con contenido binario a texto.
     * Lo hace en base64.
     * @param mixed $val
     * @return string
     */
    public static function bin2str($val) {
        if ($val === NULL) {
            return 'NULL';
        }

        return "'" . base64_encode($val) . "'";
    }

    /**
     * Convierte un texto a binario.
     * Lo hace con base64.
     * @param string $val
     * @return null|string
     */
    public static function str2bin($val) {
        if ($val === NULL) {
            return NULL;
        }

        return base64_decode($val);
    }

    /**
     * Devuelve el valor entero de la variable $s,
     * o NULL si es NULL. La función intval() del php devuelve 0 si es NULL.
     * @param string $str
     * @return integer
     */
    public static function intval($str) {
        if ($str === NULL) {
            return NULL;
        }

        return (int) $str;
    }

    /**
     * Compara dos números en coma flotante con una precisión de $precision,
     * devuelve TRUE si son iguales, FALSE en caso contrario.
     * @param double $f1
     * @param double $f2
     * @param integer $precision
     * @param boolean $round
     * @return boolean
     */
    public static function floatcmp($f1, $f2, $precision = 10, $round = FALSE) {
        if ($round || !function_exists('bccomp')) {
            return( abs($f1 - $f2) < 6 / pow(10, $precision + 1) );
        }

        return( bccomp((string) $f1, (string) $f2, $precision) === 0 );
    }

    /**
     * Devuelve un array con todas las fechas entre $first y $last.
     * @param string $first
     * @param string $last
     * @param string $step
     * @param string $format
     * @return mixed
     */
    public static function dateRange($first, $last, $step = '+1 day', $format = 'd-m-Y') {
        $dates = [];
        $current = strtotime($first);
        $last = strtotime($last);

        while ($current <= $last) {
            $dates[] = date($format, $current);
            $current = strtotime($step, $current);
        }

        return $dates;
    }

    /**
     * Devuelve una cadena de texto aleatorio de longitud $length
     * @param integer $length
     * @return string
     */
    public static function randomString($length = 10) {
        return mb_substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

}
