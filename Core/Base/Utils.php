<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

/**
 * Utils give us some basic and common methods.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Utils
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
        return $val === null ? 'NULL' : "'" . \base64_encode($val) . "'";
    }

    /**
     * Convert a boolean to text.
     *
     * @param bool $val
     *
     * @return string
     */
    public static function bool2str($val)
    {
        switch ($val) {
            case true:
                return 't';

            case 1:
                return '1';

            case 0:
                return '0';
        }

        return 'f';
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
        $start = \strtotime($first);
        $end = \strtotime($last);

        while ($start <= $end) {
            $dates[] = \date($format, $start);
            $start = \strtotime($step, $start);
        }

        return $dates;
    }

    /**
     * Make corrections in the HTML code
     *
     * @param string $txt
     *
     * @return string
     */
    public static function fixHtml($txt)
    {
        $original = ['&lt;', '&gt;', '&quot;', '&#39;'];
        $final = ['<', '>', '"', "'"];

        return $txt === null ? null : \trim(\str_replace($original, $final, $txt));
    }

    /**
     * Compare two floating point numbers with an accuracy of $precision,
     * returns True if they are equal, False otherwise.
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
        if ($round || false === \function_exists('bccomp')) {
            return \abs($f1 - $f2) < 6 / 10 ** ($precision + 1);
        }

        return \bccomp((string) $f1, (string) $f2, $precision) === 0;
    }

    /**
     * Returns the integer value of the variable $ s,
     * or null if it is null. The intval() function of the php returns 0 if it is null.
     *
     * @param string $str
     *
     * @return int
     */
    public static function intval($str)
    {
        return $str === null ? null : (int) $str;
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
        $newt = \str_replace(
            ['<', '>', '"', "'"], ['&lt;', '&gt;', '&quot;', '&#39;'], $txt
        );

        return $txt === null ? null : \trim($newt);
    }

    /**
     * Normalizes a string replacing accented characters to 
     * their normalized counterparts.
     *
     * @param string $string
     * 
     * @return string
     */
    public static function normalize($string)
    {
        $table = [
            'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
            'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e',
            'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b',
            'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r'
        ];

        return \strtr($string, $table);
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
        return \mb_substr(\str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
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
        return $val === null ? null : \base64_decode($val);
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
    public static function str2bool($val)
    {
        return \in_array(\strtolower($val), ['true', 't', '1'], false);
    }

    /**
     * Breaks text at maximum width, without break words.
     *
     * @param string $desc
     * @param int    $maxWidth
     * 
     * @return string
     */
    public static function trueTextBreak($text, $maxWidth = 500)
    {
        /// remove blank lines
        $desc = \trim(\preg_replace(["/\s\s+/"], [" "], $text));
        if (\mb_strlen($desc) <= $maxWidth) {
            return $desc;
        }

        $description = '';
        foreach (\explode(' ', $desc) as $aux) {
            if (\mb_strlen($description . ' ' . $aux) >= $maxWidth - 3) {
                break;
            } elseif ($description == '') {
                $description = $aux;
            } else {
                $description .= ' ' . $aux;
            }
        }

        return $description . '...';
    }
}
