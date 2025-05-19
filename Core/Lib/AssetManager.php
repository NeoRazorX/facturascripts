<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;

/**
 * Gestiona los assets de la aplicación.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AssetManager
{
    /** @var array */
    protected static $list;

    /**
     * Añade un asset a la lista.
     *
     * @param string $type
     * @param string $asset
     * @param int $priority
     */
    public static function add(string $type, string $asset, int $priority = 1): void
    {
        static::init();

        // evitamos duplicados
        foreach (static::$list[$type] as $item) {
            if ($item['asset'] == $asset) {
                return;
            }
        }

        // añadimos el asset
        static::$list[$type][] = [
            'asset' => $asset,
            'priority' => $priority,
        ];
    }

    public static function addCss(string $asset, int $priority = 1): void
    {
        static::add('css', $asset, $priority);
    }

    public static function addJs(string $asset, int $priority = 1): void
    {
        static::add('js', $asset, $priority);
    }

    public static function addJsModule(string $asset, int $priority = 1): void
    {
        static::add('mjs', $asset, $priority);
    }

    /** Eliminamos todos los assets de la lista */
    public static function clear(): void
    {
        static::$list = [
            'css' => [],
            'js' => [],
            'mjs' => [],
        ];
    }

    /**
     * Combina los assets CSS en un único archivo y devuelve la ruta.
     *
     * @return string
     */
    public static function combinedCss(): string
    {
        $assets = static::pull('css');
        $route = Tools::config('route');

        // generamos una semilla a partir de la fecha y las rutas de los assets
        $seed = date('Y-m-d');
        foreach ($assets as $item) {
            $seed .= $item['asset'];
        }

        // generamos el nombre del archivo
        $file_name = 'combined-' . md5($seed) . '.css';
        $file_path = Tools::folder('Dinamic', 'Assets', 'CSS', $file_name);

        // si el archivo no existe, lo creamos
        if (!file_exists($file_path)) {
            $content = '';
            foreach ($assets as $item) {
                // si es una url, añadimos el include
                if (strpos($item['asset'], 'http') === 0) {
                    $content .= '@import url("' . $item['asset'] . '");';
                    continue;
                }

                $content .= static::fixCombinedCss(
                    file_get_contents($item['asset']),
                    $route . '/' . $item['asset']
                );
            }
            file_put_contents($file_path, $content);
        }

        return implode('/', [$route, 'Dinamic', 'Assets', 'CSS', $file_name]);
    }

    /**
     * Combina los assets JS en un único archivo y devuelve la ruta.
     *
     * @return string
     */
    public static function combinedJs(): string
    {
        $assets = static::pull('js');
        $route = Tools::config('route');

        // generamos una semilla a partir de la fecha y las rutas de los assets
        $seed = date('Y-m-d');
        foreach ($assets as $item) {
            $seed .= $item['asset'];
        }

        // generamos el nombre del archivo
        $file_name = 'combined-' . md5($seed) . '.js';
        $file_path = Tools::folder('Dinamic', 'Assets', 'JS', $file_name);

        // si el archivo no existe, lo creamos
        if (!file_exists($file_path)) {
            $content = '';
            foreach ($assets as $item) {
                // si es una url, añadimos el include
                if (strpos($item['asset'], 'http') === 0) {
                    $content .= 'import "' . $item['asset'] . '";';
                    continue;
                }

                $content .= file_get_contents($item['asset']);
            }
            file_put_contents($file_path, $content);
        }

        return implode('/', [$route, 'Dinamic', 'Assets', 'JS', $file_name]);
    }

    /**
     * Devuelve la lista de assets de un tipo.
     *
     * @param string $type
     *
     * @return array
     */
    public static function get(string $type): array
    {
        static::sort();

        $list = [];
        foreach (static::$list[$type] as $item) {
            $list[] = $item['asset'];
        }

        return $list;
    }

    public static function getCss(): array
    {
        return static::get('css');
    }

    public static function getJs(): array
    {
        return static::get('js');
    }

    public static function getJsModules(): array
    {
        return static::get('mjs');
    }

    /**
     * Busca los assets de una página y los añade a la lista.
     *
     * @param string $name
     */
    public static function setAssetsForPage(string $name): void
    {
        foreach (['css' => 'CSS', 'js' => 'JS', 'mjs' => 'JS'] as $ext => $folder) {
            $file_path = Tools::folder('Dinamic', 'Assets', $folder, $name . '.' . $ext);
            if (file_exists($file_path)) {
                $route = implode('/', [
                    Tools::config('route'), 'Dinamic', 'Assets', $folder, $name . '.' . $ext
                ]);
                static::add($ext, $route, 0);
            }
        }
    }

    protected static function dirname(string $path, int $levels = 1): string
    {
        return str_replace('\\', '/', dirname($path, $levels));
    }

    protected static function fixCombinedCss(string $content, string $url): string
    {
        // excluimos url("data:) del reemplazo
        $buffer = str_replace('url("data:', '#url-data:#', $content);

        // reemplazamos las rutas relativas
        $replace = [
            'url("' => 'url("' . static::dirname($url) . '/',
            'url(../' => "url(" . static::dirname($url, 2) . '/',
            "url('../" => "url('" . static::dirname($url, 2) . '/',
        ];
        $buffer = str_replace(array_keys($replace), $replace, $buffer);

        // arreglamos url("data:)
        $buffer = str_replace('#url-data:#', 'url("data:', $buffer);

        // eliminamos comentarios
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);

        // eliminamos espacios después de :
        $buffer = str_replace(': ', ':', $buffer);

        // eliminamos espacios en blanco
        return str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $buffer);
    }

    protected static function init(): void
    {
        if (!isset(static::$list)) {
            static::clear();
        }
    }

    protected static function pull(string $type): array
    {
        static::sort();

        $list = static::$list[$type];
        static::$list[$type] = [];

        return $list;
    }

    /** Ordenamos los assets por prioridad */
    protected static function sort(): void
    {
        static::init();

        foreach (static::$list as $type => $items) {
            uasort($items, function ($item1, $item2) {
                if ($item1['priority'] > $item2['priority']) {
                    return -1;
                } elseif ($item1['priority'] < $item2['priority']) {
                    return 1;
                }

                return 0;
            });
            static::$list[$type] = $items;
        }
    }
}
