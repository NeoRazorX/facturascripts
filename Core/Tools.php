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

use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\Translator;
use FacturaScripts\Core\DataSrc\Divisas;
use FacturaScripts\Core\Model\Settings;

class Tools
{
    const ASCII = [
        'Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z', 'Č' => 'C', 'č' => 'c', 'Ć' => 'C',
        'ć' => 'c', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',
        'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i',
        'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r'
    ];
    const DATE_STYLE = 'd-m-Y';
    const DATETIME_STYLE = 'd-m-Y H:i:s';
    const HOUR_STYLE = 'H:i:s';
    const HTML_CHARS = ['<', '>', '"', "'"];
    const HTML_REPLACEMENTS = ['&lt;', '&gt;', '&quot;', '&#39;'];

    /** @var array */
    private static $settings = [];

    public static function ascii(string $text): string
    {
        return strtr($text, self::ASCII);
    }

    public static function config(string $key, $default = null)
    {
        $constants = [$key, strtoupper($key), 'FS_' . strtoupper($key)];
        foreach ($constants as $constant) {
            if (defined($constant)) {
                return constant($constant);
            }
        }

        return $default;
    }

    public static function date(?string $date = null): string
    {
        return empty($date) ? date(self::DATE_STYLE) : date(self::DATE_STYLE, strtotime($date));
    }

    public static function dateTime(?string $date = null): string
    {
        return empty($date) ? date(self::DATETIME_STYLE) : date(self::DATETIME_STYLE, strtotime($date));
    }

    public static function fixHtml(?string $text = null): ?string
    {
        return $text === null ?
            null :
            str_replace(self::HTML_REPLACEMENTS, self::HTML_CHARS, trim($text));
    }

    public static function folder(...$folders): string
    {
        if (empty($folders)) {
            return self::config('folder') ?? '';
        }

        array_unshift($folders, self::config('folder'));
        return implode(DIRECTORY_SEPARATOR, $folders);
    }

    public static function folderCheckOrCreate(string $folder): bool
    {
        return is_dir($folder) || mkdir($folder, 0777, true);
    }

    public static function folderCopy(string $src, string $dst): bool
    {
        static::folderCheckOrCreate($dst);

        $folder = opendir($src);
        while (false !== ($file = readdir($folder))) {
            if ($file === '.' || $file === '..') {
                continue;
            } elseif (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                static::folderCopy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
            } else {
                copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
            }
        }

        closedir($folder);
        return true;
    }

    public static function folderDelete(string $folder): bool
    {
        if (is_dir($folder) && false === is_link($folder)) {
            $files = array_diff(scandir($folder), ['.', '..']);
            foreach ($files as $file) {
                self::folderDelete($folder . DIRECTORY_SEPARATOR . $file);
            }

            return rmdir($folder);
        }

        return unlink($folder);
    }

    public static function folderScan(string $folder, bool $recursive = false, array $exclude = ['.DS_Store', '.well-known']): array
    {
        $scan = scandir($folder, SCANDIR_SORT_ASCENDING);
        if (false === is_array($scan)) {
            return [];
        }

        $exclude[] = '.';
        $exclude[] = '..';
        $rootFolder = array_diff($scan, $exclude);
        if (false === $recursive) {
            return $rootFolder;
        }

        $result = [];
        foreach ($rootFolder as $item) {
            $newItem = $folder . DIRECTORY_SEPARATOR . $item;
            $result[] = $item;
            if (is_dir($newItem)) {
                foreach (static::folderScan($newItem, true, $exclude) as $item2) {
                    $result[] = $item . DIRECTORY_SEPARATOR . $item2;
                }
            }
        }

        return $result;
    }

    public static function hour(?string $date = null): string
    {
        return empty($date) ? date(self::HOUR_STYLE) : date(self::HOUR_STYLE, strtotime($date));
    }

    public static function lang(string $lang = ''): Translator
    {
        return new Translator($lang);
    }

    public static function log(string $channel = ''): MiniLog
    {
        $translator = new Translator();
        return new MiniLog($channel, $translator);
    }

    public static function money(float $number, string $coddivisa = ''): string
    {
        if (empty($coddivisa)) {
            $coddivisa = self::settings('default', 'coddivisa');
        }

        $symbol = Divisas::get($coddivisa)->simbolo;
        $currencyPosition = self::settings('default', 'currency_position');
        return $currencyPosition === 'right' ?
            self::number($number) . ' ' . $symbol :
            $symbol . ' ' . self::number($number);
    }

    public static function noHtml(?string $text): ?string
    {
        return $text === null ?
            null :
            str_replace(self::HTML_CHARS, self::HTML_REPLACEMENTS, trim($text));
    }

    public static function number(float $number, ?int $decimals = null): string
    {
        if ($decimals === null) {
            $decimals = self::settings('default', 'decimals');
        }

        // cargamos la configuración
        $decimalSeparator = self::settings('default', 'decimal_separator');
        $thousandsSeparator = self::settings('default', 'thousands_separator');

        return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    public static function password(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.+-*¿?¡!#$%&/()=;:_,<>@';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function settings(string $group, string $key, $default = null)
    {
        // cargamos las opciones si no están cargadas
        if (empty(self::$settings)) {
            $settingsModel = new Settings();
            foreach ($settingsModel->all([], [], 0, 0) as $item) {
                self::$settings[$item->name] = $item->properties;
            }
        }

        // si no tenemos la clave, añadimos el valor predeterminado
        if (!isset(self::$settings[$group][$key])) {
            self::$settings[$group][$key] = $default;
        }

        return self::$settings[$group][$key];
    }

    public static function settingsSave(): bool
    {
        if (empty(self::$settings)) {
            return true;
        }

        $settingsModel = new Settings();
        foreach ($settingsModel->all([], [], 0, 0) as $item) {
            if (!isset(self::$settings[$item->name])) {
                continue;
            }

            $item->properties = self::$settings[$item->name];
            if (false === $item->save()) {
                return false;
            }
        }

        return true;
    }

    public static function settingsSet(string $group, string $key, $value): void
    {
        // cargamos las opciones si no están cargadas
        if (empty(self::$settings)) {
            $settingsModel = new Settings();
            foreach ($settingsModel->all([], [], 0, 0) as $item) {
                self::$settings[$item->name] = $item->properties;
            }
        }

        // asignamos el valor
        self::$settings[$group][$key] = $value;
    }

    public static function slug(string $text, string $separator = '-', int $maxLength = 0): string
    {
        $text = self::ascii($text);
        $text = preg_replace('/[^A-Za-z0-9]+/', $separator, $text);
        $text = preg_replace('/' . $separator . '{2,}/', $separator, $text);
        $text = trim($text, $separator);
        $text = strtolower($text);
        return $maxLength > 0 ?
            substr($text, 0, $maxLength) :
            $text;
    }

    public static function randomString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function textBreak(string $text, int $length = 50, string $break = '...'): string
    {
        if (strlen($text) <= $length) {
            return trim($text);
        }

        // separamos el texto en palabras
        $words = explode(' ', trim($text));
        $result = '';
        foreach ($words as $word) {
            if (strlen($result . ' ' . $word . $break) <= $length) {
                $result .= $result === '' ? $word : ' ' . $word;
                continue;
            }

            $result .= $break;
            break;
        }

        return $result;
    }

    public static function timeToDate(int $time): string
    {
        return date(self::DATE_STYLE, $time);
    }

    public static function timeToDateTime(int $time): string
    {
        return date(self::DATETIME_STYLE, $time);
    }

    public static function bytes($size, int $decimals = 2): string
    {
        if ($size >= 1073741824) {
            $size = number_format($size / 1073741824, $decimals) . ' GB';
        } elseif ($size >= 1048576) {
            $size = number_format($size / 1048576, $decimals) . ' MB';
        } elseif ($size >= 1024) {
            $size = number_format($size / 1024, $decimals) . ' KB';
        } elseif ($size > 1) {
            $size = number_format($size, $decimals) . ' bytes';
        } elseif ($size == 1) {
            $size = number_format(1, $decimals) . ' byte';
        } else {
            $size = number_format(0, $decimals) . ' bytes';
        }

        return $size;
    }
}
