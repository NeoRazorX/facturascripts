<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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
 * @package FacturaScripts\Core\Base
 * @author Carlos García Gómez
 */
class PluginManager
{

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

    /**
     * Traductor del sistema.
     * @var Translator
     */
    private static $i18n;

    /**
     * Gestiona el log de toda la aplicación.
     * @var MiniLog
     */
    private static $minilog;

    /**
     * PluginManager constructor.
     * @param string $folder
     */
    public function __construct($folder = '')
    {
        if (self::$folder === null) {
            self::$folder = $folder;
            self::$i18n = new Translator($folder);
            self::$minilog = new MiniLog();

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
     * Devuelve la carpeta
     * @return string
     */
    public function folder()
    {
        return self::$folder;
    }

    /**
     * Devuelve la lista de plugins activos.
     * @return array
     */
    public function enabledPlugins()
    {
        return self::$enabledPlugins;
    }

    /**
     * Activa el plugin indicado.
     * @param string $pluginName
     */
    public function enable($pluginName)
    {
        if (file_exists(self::$folder . '/plugins/' . $pluginName)) {
            self::$enabledPlugins[] = $pluginName;
            file_put_contents(self::$folder . '/plugin.list', implode(',', self::$enabledPlugins));
        }
    }

    /**
     * Desactiva el plugin indicado.
     * @param string $pluginName
     */
    public function disable($pluginName)
    {
        foreach (self::$enabledPlugins as $i => $value) {
            if ($value === $pluginName) {
                unset(self::$enabledPlugins[$i]);
                file_put_contents(self::$folder . '/plugin.list', implode(',', self::$enabledPlugins));
                break;
            }
        }
    }

    /**
     * Despliega todos los archivos necesarios en la carpeta Dinamic para poder
     * usar controladores y modelos de plugins con el autoloader, pero siguiendo
     * el sistema de prioridades de FacturaScripts.
     * @param bool $clean
     */
    public function deploy($clean = true)
    {
        $folders = ['Controller', 'Model', 'Table'];
        if ($clean) {
            // Limpiamos Dinamic
            foreach ($folders as $folder) {
                /// ¿Existe la carpeta?
                $dir = self::$folder . '/Dinamic/' . $folder;
                if (!file_exists($dir)) {
                    if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
                        self::$minilog->critical(self::$i18n->trans('cant-create-folder', [$dir]));
                    }
                } else {
                    $this->cleanDinamic(self::$folder . '/Dinamic/');
                }
            }
        }

        // Creamos los nuevos Dinamic
        foreach ($folders as $folder) {
            /// examinamos los plugins
            foreach (self::$enabledPlugins as $pluginName) {
                if (file_exists(self::$folder . '/Plugins/' . $pluginName . '/' . $folder)) {
                    $this->linkFiles($folder, 'Plugins', $pluginName);
                }
            }

            /// examinamos el core
            $this->linkFiles($folder);
        }
    }

    /**
     * Eliminamos cada archivo de la carpeta Dinamic,
     * si es una carpeta, se llamará así misma
     * @param string $folder Carpeta a eliminar sus archivos
     */
    private function cleanDinamic($folder)
    {
        // Añadimos los archivos que no son '.' ni '..'
        $items = array_diff(scandir($folder, SCANDIR_SORT_ASCENDING), ['.', '..']);
        // Ahora recorremos solo archivos o carpetas
        foreach ($items as $item) {
            if (is_dir($folder . '/' . $item)) {
                $this->cleanDinamic($folder . $item . '/');
            } else {
                unlink($folder . $item);
            }
        }
    }

    /**
     * Enlazamos los archivos
     * @param $folder
     * @param string $place
     * @param string $pluginName
     */
    private function linkFiles($folder, $place = 'Core', $pluginName = '')
    {
        if (empty($pluginName)) {
            $path = self::$folder . '/' . $place . '/' . $folder;
            $namespace = "\FacturaScripts\Core\\";
        } else {
            $path = self::$folder . '/Plugins/' . $pluginName . '/' . $folder;
            $namespace = "\FacturaScripts\Plugins\\" . $pluginName . "\\";
        }

        // Añadimos los archivos que no son '.' ni '..'
        $filesPath = array_diff(scandir($path, SCANDIR_SORT_ASCENDING), ['.', '..']);
        // Ahora recorremos solo archivos o carpetas
        foreach ($filesPath as $fileName) {
            $infoFile = pathinfo($fileName);
            if (!is_dir($fileName) && $infoFile['filename'] !== '') {
                if ($infoFile['extension'] === 'php') {
                    $this->linkClassFile($fileName, $folder, $namespace);
                } elseif ($infoFile['extension'] === 'xml') {
                    $filePath = self::$folder . '/' . $place . '/' . $folder . '/' . $fileName;
                    $this->linkXmlFile($fileName, $folder, $filePath);
                }
            }
        }
    }

    /**
     * Enlaza las classes de forma dinamica
     * @param $fileName
     * @param $folder
     * @param string $namespace
     */
    private function linkClassFile($fileName, $folder, $namespace = "\FacturaScripts\Core\\")
    {
        if (!file_exists(self::$folder . '/Dinamic/' . $folder . '/' . $fileName)) {
            $className = substr($fileName, 0, -4);
            $txt = '<?php namespace FacturaScripts\Dinamic\\' . $folder . ";\n\n"
                . '/**' . "\n"
                . ' * Clase cargada dinámicamente' . "\n"
                . ' * @package FacturaScripts\\Dinamic\\Controller' . "\n"
                . ' * @author Carlos García Gómez' . "\n"
                . ' */' . "\n"
                . 'class ' . $className . ' extends ' . $namespace . $folder . '\\' . $className . "\n{\n}\n";

            file_put_contents(self::$folder . '/Dinamic/' . $folder . '/' . $fileName, $txt);
        }
    }

    /**
     * Enlaza los XML de forma dinamica
     * @param $fileName
     * @param $folder
     * @param $filePath
     */
    private function linkXmlFile($fileName, $folder, $filePath)
    {
        if (!file_exists(self::$folder . '/Dinamic/' . $folder . '/' . $fileName)) {
            copy($filePath, self::$folder . '/Dinamic/' . $folder . '/' . $fileName);
        }
    }
}
