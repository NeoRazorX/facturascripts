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
    public static function getAssetsForPage($name)
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
}
