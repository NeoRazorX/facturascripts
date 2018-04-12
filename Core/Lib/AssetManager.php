<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Lib;

/**
 * Asset Manager for easy add extra assets.
 *
 * @author Carlos García Gómez
 */
class AssetManager
{

    /**
     * Return the needed assets for this page
     *
     * @param string $name
     *
     * @return array
     */
    public static function getAssetsForPage(string $name): array
    {
        $assets = [
            'js' => [],
            'css' => [],
        ];
        $base = DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR;
        $jsFile = $base . 'JS' . DIRECTORY_SEPARATOR . $name . '.js';
        $cssFile = $base . 'CSS' . DIRECTORY_SEPARATOR . $name . '.css';
        if (file_exists(FS_FOLDER . $jsFile)) {
            $assets['js'][] = FS_ROUTE . $jsFile;
        }
        if (file_exists(FS_FOLDER . $cssFile)) {
            $assets['css'][] = FS_ROUTE . $cssFile;
        }

        return $assets;
    }

    /**
     * Combine and returns the content of the selected files.
     *
     * @param array $fileList
     *
     * @return string
     */
    public static function combine(array $fileList): string
    {
        $txt = '';
        foreach ($fileList as $file) {
            $content = file_get_contents(FS_FOLDER . DIRECTORY_SEPARATOR . $file) . "\n";
            $txt .= static::fixCombineContent($content, FS_ROUTE . DIRECTORY_SEPARATOR . $file);
        }

        return $txt;
    }

    public static function fixCombineContent(string $data, string $url): string
    {
        // Replace relative paths
        $replace = [
            'url(../' => "url(" . dirname($url, 2) . '/',
            "url('../" => "url('" . dirname($url, 2) . '/',
        ];
        $buffer = \str_replace(array_keys($replace), $replace, $data);

        // Remove comments
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        
        // Remove space after colons
        $buffer = str_replace(': ', ':', $buffer);
        
        // Remove whitespace
        return str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $buffer);
    }
}
