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
 * Utils give us some basic and common methods.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
trait Utils
{
    /**
     * Convert a variable with binary content to text.
     * It does it in base64.
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
     * Convert a text to binary.
     * It does with base64.
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
     * Returns the integer value of the variable $ s,
     * or null if it is null. The intval() function of the php returns 0 if it is null.
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
     * Compare two floating point numbers with an accuracy of $precision,
     * returns True if they are equal, False otherwise.
     *
     * @param double $f1
     * @param double $f2
     * @param int $precision
     * @param bool $round
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
     * Returns an array with all dates between $first and $last.
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
        $start = strtotime($first);
        $end = strtotime($last);

        while ($start <= $end) {
            $dates[] = date($format, $start);
            $start = strtotime($step, $start);
        }

        return $dates;
    }

    /**
     * Returns a random text string of length $length.
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
     * This function converts:
     * < to &lt;
     * > to &gt;
     * " to &quot;
     * ' to &#39;
     *
     * Do not be tempted to substitute by htmlentities or htmlspecialshars
     * because you will find many unpleasant surprises.
     *
     * @param string $txt
     *
     * @return string
     */
    public static function noHtml($txt)
    {
        $newt = str_replace(
            ['<', '>', '"', "'"],
            ['&lt;', '&gt;', '&quot;', '&#39;'],
            $txt
        );

        return trim($newt);
    }

    /**
     * Make corrections in the HTML code
     *
     * @param string $txt
     *
     * @return string
     */
    public function fixHtml($txt)
    {
        if($txt === null) {
            return null;
        }
        
        $original = ['&lt;', '&gt;', '&quot;', '&#39;'];
        $final = ['<', '>', "'", "'"];
        return trim(str_replace($original, $final, $txt));
    }

    /**
     * PostgreSQL saves the True values as 't', MySQL as 1.
     * This function returns True if the value corresponds to
     * any of the above.
     *
     * @param string $val
     *
     * @return bool
     */
    public function str2bool($val)
    {
        return in_array(strtolower($val), ['true', 't', '1'], false);
    }

    /**
     * Convert a boolean to text.
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
