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

        $folder = FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Assets' . DIRECTORY_SEPARATOR;
        $jsFile = $folder . 'JS' . DIRECTORY_SEPARATOR . $name . '.js';
        if (file_exists($jsFile)) {
            $assets['js'][] = $jsFile;
        }

        $cssFile = $folder . 'CSS' . DIRECTORY_SEPARATOR . $name . '.css';
        if (file_exists($cssFile)) {
            $assets['css'][] = $cssFile;
        }

        return $assets;
    }
}
