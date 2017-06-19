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

namespace FacturaScripts\Core\Base;

/**
 * Gestor de plugins de FacturaScripts.
 *
 * @author Carlos García Gómez
 */
class PluginManager {

    /**
     * Lista de plugins activos.
     * @var array 
     */
    private static $enabledPlugins;

    /**
     * Carpeta de trabajo de FacturaScripts.
     * @var string 
     */
    private static $folder;

    public function __construct($folder = '') {
        if (!isset(self::$folder)) {
            self::$folder = $folder;

            self::$enabledPlugins = [];
            if (file_exists(self::$folder . '/plugin.list')) {
                $list = explode(',', file_get_contents(self::$folder . '/plugin.list'));
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
        if (file_exists(self::$folder . '/plugins/' . $pluginName)) {
            self::$enabledPlugins[] = $pluginName;
            file_put_contents(self::$folder . '/plugin.list', join(',', self::$enabledPlugins));
        }
    }

    /**
     * Desactiva el plugin indicado.
     * @param string $pluginName
     */
    public function disable($pluginName) {
        foreach (self::$enabledPlugins as $i => $value) {
            if ($value == $pluginName) {
                unset(self::$enabledPlugins[$i]);
                file_put_contents(self::$folder . '/plugin.list', join(',', self::$enabledPlugins));
                break;
            }
        }
    }

    /**
     * Despliega todos los archivos necesarios en la carpeta Dinamic para poder
     * usar controladores y modelos de plugins con el autoloader, pero siguiendo
     * el sistema de prioridades de FacturaScripts.
     */
    public function deploy() {
        $folders = ['Controller', 'Model'];
        foreach ($folders as $folder) {
            /// ¿Existe la carpeta?
            if (!file_exists(self::$folder . '/Dinamic/' . $folder)) {
                mkdir(self::$folder . '/Dinamic/' . $folder, 0755, TRUE);
            }

            /// examinamos los plugins
            foreach (self::$enabledPlugins as $pluginName) {
                if (file_exists(self::$folder . '/Plugins/' . $pluginName . '/' . $folder)) {
                    foreach (scandir(self::$folder . '/Plugins/' . $pluginName . '/' . $folder) as $fileName) {
                        if ($fileName != '.' && $fileName != '..' && !is_dir($f) && strlen($fileName) > 4 && substr($fileName, -4) == '.php') {
                            $this->linkPluginFile($fileName, $pluginName, $folder);
                        }
                    }
                }
            }

            /// examinamos el core
            foreach (scandir(self::$folder . '/Core/' . $folder) as $fileName) {
                if ($fileName != '.' && $fileName != '..' && !is_dir($f) && strlen($fileName) > 4 && substr($fileName, -4) == '.php') {
                    $this->linkCoreFile($fileName, $folder);
                }
            }
        }
    }

    private function linkPluginFile($fileName, $pluginName, $folder) {
        if (!file_exists(self::$folder . '/Dinamic/' . $folder . '/' . $fileName)) {
            $className = substr($fileName, 0, -4);
            $txt = "<?php namespace FacturaScripts\Dinamic\\" . $folder . ";\n\nclass " . $className
                    . " extends \FacturaScripts\Plugins\\" . $pluginName . "\\" . $folder . "\\" . $className . " {\n\n}";

            file_put_contents(self::$folder . '/Dinamic/' . $folder . '/' . $fileName, $txt);
        }
    }

    private function linkCoreFile($fileName, $folder) {
        if (!file_exists(self::$folder . '/Dinamic/' . $folder . '/' . $fileName)) {
            $className = substr($fileName, 0, -4);
            $txt = "<?php namespace FacturaScripts\Dinamic\\" . $folder . ";\n\nclass " . $className
                    . " extends \FacturaScripts\Core\\" . $folder . "\\" . $className . " {\n\n}";

            file_put_contents(self::$folder . '/Dinamic/' . $folder . '/' . $fileName, $txt);
        }
    }

}
