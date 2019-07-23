<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Asset Manager for easy add extra assets.
 *
 * @author Carlos García Gómez
 */
class AssetManager
{

    /**
     *
     * @var array
     */
    private static $list;

    /**
     * Adds and asset to the list.
     *
     * @param string $type
     * @param string $asset
     * @param int    $priority
     */
    public static function add(string $type, string $asset, int $priority = 1)
    {
        static::init();

        /// avoid duplicates
        foreach (self::$list[$type] as $item) {
            if ($item['asset'] == $asset) {
                return;
            }
        }

        /// insert
        self::$list[$type][] = [
            'asset' => $asset,
            'priority' => $priority,
        ];
    }

    /**
     * Clears all asset lists.
     */
    public static function clear()
    {
        self::$list = [
            'css' => [],
            'js' => [],
        ];
    }

    /**
     * Combine and returns the content of the selected type.
     *
     * @param string $type
     *
     * @return string
     */
    public static function combine(string $type): string
    {
        $txt = '';
        foreach (static::get($type) as $file) {
            $filePath = $file;
            if (\FS_ROUTE == substr($file, 0, strlen(\FS_ROUTE))) {
                $filePath = substr($file, strlen(\FS_ROUTE) + 1);
            }

            $content = file_get_contents(\FS_FOLDER . DIRECTORY_SEPARATOR . $filePath) . "\n";
            $txt .= static::fixCombineContent($content, \FS_ROUTE . DIRECTORY_SEPARATOR . $filePath);
        }

        return $txt;
    }

    /**
     * Gets the list of assets.
     *
     * @param string $type
     *
     * @return array
     */
    public static function get(string $type)
    {
        static::init();

        /// sort by priority
        uasort(self::$list[$type], function ($item1, $item2) {
            if ($item1['priority'] > $item2['priority']) {
                return -1;
            } elseif ($item1['priority'] < $item2['priority']) {
                return 1;
            }

            return 0;
        });

        /// extract assets
        $assets = [];
        foreach (self::$list[$type] as $item) {
            $assets[] = $item['asset'];
        }
        return $assets;
    }

    /**
     * Finds and sets the assets for this page.
     *
     * @param string $name
     */
    public static function setAssetsForPage(string $name)
    {
        $base = DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR;

        /// find js file with $name name
        $jsFile = $base . 'JS' . DIRECTORY_SEPARATOR . $name . '.js';
        if (file_exists(\FS_FOLDER . $jsFile)) {
            self::add('js', \FS_ROUTE . $jsFile, 0);
        }

        /// find css file with $name name
        $cssFile = $base . 'CSS' . DIRECTORY_SEPARATOR . $name . '.css';
        if (file_exists(\FS_FOLDER . $cssFile)) {
            self::add('css', \FS_ROUTE . $cssFile, 0);
        }
    }

    /**
     * 
     * @param string $path
     * @param int    $levels
     *
     * @return string
     */
    protected static function dirname($path, $levels = 1)
    {
        return str_replace('\\', '/', dirname($path, $levels));
    }

    /**
     *
     * @param string $data
     * @param string $url
     *
     * @return string
     */
    protected static function fixCombineContent(string $data, string $url): string
    {
        // Excluce url("data:) from replacement
        $buffer = str_replace('url("data:', '#url-data:#', $data);

        // Replace relative paths in url()
        $replace = [
            'url("' => 'url("' . self::dirname($url) . '/',
            'url(../' => "url(" . self::dirname($url, 2) . '/',
            "url('../" => "url('" . self::dirname($url, 2) . '/',
        ];
        $buffer = str_replace(array_keys($replace), $replace, $buffer);

        // fix url("data:)
        $buffer = str_replace('#url-data:#', 'url("data:', $buffer);

        // Remove comments
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);

        // Remove space after colons
        $buffer = str_replace(': ', ':', $buffer);

        // Remove whitespace
        return str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $buffer);
    }

    protected static function init()
    {
        if (!isset(self::$list)) {
            static::clear();
        }
    }
}
