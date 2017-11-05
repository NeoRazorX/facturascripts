<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait Utils
{
    /**
     * Convierte una variable con contenido binario a texto.
     * Lo hace en base64.
     *
     * @param mixed $val
     *
     * @return string
     */
    public static function bin2str($val)
    {
        if ($val === null) {
            return 'NULL';
        }

        return "'" . base64_encode($val) . "'";
    }

    /**
     * Convierte un texto a binario.
     * Lo hace con base64.
     *
     * @param string $val
     *
     * @return null|string
     */
    public static function str2bin($val)
    {
        if ($val === null) {
            return null;
        }

        return base64_decode($val);
    }

    /**
     * Devuelve el valor entero de la variable $s,
     * o null si es null. La función intval() del php devuelve 0 si es null.
     *
     * @param string $str
     *
     * @return integer
     */
    public static function intval($str)
    {
        if ($str === null) {
            return null;
        }

        return (int) $str;
    }

    /**
     * Compara dos números en coma flotante con una precisión de $precision,
     * devuelve True si son iguales, False en caso contrario.
     *
     * @param double $f1
     * @param double $f2
     * @param int    $precision
     * @param bool   $round
     *
     * @return bool
     */
    public static function floatcmp($f1, $f2, $precision = 10, $round = false)
    {
        if ($round || !function_exists('bccomp')) {
            return abs($f1 - $f2) < 6 / 10 ** ($precision + 1);
        }

        return bccomp((string) $f1, (string) $f2, $precision) === 0;
    }

    /**
     * Devuelve un array con todas las fechas entre $first y $last.
     *
     * @param string $first
     * @param string $last
     * @param string $step
     * @param string $format
     *
     * @return array
     */
    public static function dateRange($first, $last, $step = '+1 day', $format = 'd-m-Y')
    {
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
     *
     * @param int $length
     *
     * @return string
     */
    public static function randomString($length = 10)
    {
        return mb_substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
    }

    /**
     * Esta función convierte:
     * < en &lt;
     * > en &gt;
     * " en &quot;
     * ' en &#39;
     *
     * No tengas la tentación de sustiturla por htmlentities o htmlspecialshars
     * porque te encontrarás con muchas sorpresas desagradables.
     *
     * @param string $txt
     *
     * @return string
     */
    public static function noHtml($txt)
    {
        $newt = str_replace(
            ['<', '>', '"', "'"], ['&lt;', '&gt;', '&quot;', '&#39;'], $txt
        );

        return trim($newt);
    }

    /**
     * Realiza correcciones en el código HTML
     *
     * @param string $txt
     *
     * @return string
     */
    public function fixHtml($txt)
    {
        $original = ['&lt;', '&gt;', '&quot;', '&#39;'];
        $final = ['<', '>', "'", "'"];
        return trim(str_replace($original, $final, $txt));
    }

    /**
     * PostgreSQL guarda los valores True como 't', MySQL como 1.
     * Esta función devuelve True si el valor se corresponde con
     * alguno de los anteriores.
     *
     * @param string $val
     *
     * @return bool
     */
    public function str2bool($val)
    {
        return in_array(strtolower($val), ['true', 't', '1']);
    }

    /**
     * Convierte un boleano a a texto
     *
     * @param bool $val
     *
     * @return string
     */
    public function bool2str($val)
    {
        switch ($val) {
            case true:
                return 't';
            case false:
                return 'f';
            case 1:
                return '1';
            case 0:
                return '0';
            default:
                return 'f';
        }
    }
}
