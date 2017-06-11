<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

namespace FacturaScripts\Base;

/**
 * Gestor de plugins de FacturaScripts.
 *
 * @author Carlos García Gómez
 */
class fs_plugin_manager {

    /**
     * Lista de plugins activos.
     * @var array 
     */
    private static $enabledPlugins;

    /**
     * Carpeta de trabajo de FacturaScripts.
     * @var string 
     */
    private static $fsFolder;

    public function __construct($folder = '') {
        if (!isset(self::$fsFolder)) {
            self::$fsFolder = $folder;

            self::$enabledPlugins = [];
            if (file_exists(self::$fsFolder . '/plugin.list')) {
                $list = explode(',', file_get_contents(self::$fsFolder . '/plugin.list'));
                if (!empty($list)) {
                    foreach ($list as $pName) {
                        self::$enabledPlugins[] = $pName;
                    }
                }
            }
        }
    }

    /**
     * Devuelve la lista de plugins activos.
     * @return array
     */
    public function enabledPlugins() {
        return self::$enabledPlugins;
    }
    
    /**
     * Activa el plugin indicado.
     * @param string $pluginName
     */
    public function enable($pluginName) {
        if(file_exists(self::$fsFolder.'/plugins/'.$pluginName) ) {
            self::$enabledPlugins[] = $pluginName;
            file_put_contents(self::$fsFolder.'/plugin.list', join(',', self::$enabledPlugins));
        }
    }
    
    /**
     * Desactiva el plugin indicado.
     * @param string $pluginName
     */
    public function disable($pluginName) {
        foreach(self::$enabledPlugins as $i => $value) {
            if($value == $pluginName) {
                unset(self::$enabledPlugins[$i]);
                file_put_contents(self::$fsFolder.'/plugin.list', join(',', self::$enabledPlugins));
                break;
            }
        }
    }

}
