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

/**
 * Description of fs_plugin_manager
 *
 * @author Carlos García Gómez
 */
class fs_plugin_manager {

    private static $_enabledPlugins;
    private static $_fsFolder;

    public function __construct($folder = '') {
        if (!isset(self::$_fsFolder)) {
            self::$_fsFolder = $folder;

            self::$_enabledPlugins = [];
            if (file_exists(self::$_fsFolder . '/plugin.list')) {
                $list = explode(',', file_get_contents(self::$_fsFolder . '/plugin.list'));
                if ($list) {
                    foreach ($list as $pName) {
                        self::$_enabledPlugins[] = $pName;
                    }
                }
            }
        }
    }
    
    public function enabledPlugins() {
        return self::$_enabledPlugins;
    }

}
