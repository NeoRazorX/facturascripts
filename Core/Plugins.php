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

namespace FacturaScripts\Core;

use DirectoryIterator;
use FacturaScripts\Core\Base\PluginDeploy;
use FacturaScripts\Core\Internal\Plugin;
use ZipArchive;

/**
 * Permite gestionar los plugins de FacturaScripts: añadir, eliminar, activar, desactivar, etc.
 */
final class Plugins
{
    const FILE_NAME = 'plugins.json';

    /** @var Plugin[] */
    private static $plugins;

    public static function add(string $zipPath, string $zipName = 'plugin.zip', bool $force = false): bool
    {
        if (Tools::config('disable_add_plugins', false) && false === $force) {
            Tools::log()->warning('plugin-installation-disabled');
            return false;
        }

        // comprobamos el zip
        $zipFile = new ZipArchive();
        if (false === self::testZipFile($zipFile, $zipPath, $zipName)) {
            return false;
        }

        // comprobamos el facturascripts.ini del zip
        $plugin = Plugin::getFromZip($zipPath);
        if (null === $plugin) {
            Tools::log()->error('plugin-ini-file-not-found', ['%pluginName%' => $zipName]);
            return false;
        }
        if (false === $plugin->compatible) {
            Tools::log()->error($plugin->compatibilityDescription());
            return false;
        }

        // eliminamos la versión anterior
        if (false === $plugin->delete()) {
            Tools::log()->error('plugin-delete-error', ['%pluginName%' => $plugin->name]);
            return false;
        }

        // descomprimimos el zip
        if (false === $zipFile->extractTo(self::folder())) {
            Tools::log()->error('ZIP EXTRACT ERROR: ' . $zipName);
            $zipFile->close();
            return false;
        }
        $zipFile->close();

        // renombramos la carpeta
        if ($plugin->folder && $plugin->folder !== $plugin->name) {
            $from = self::folder() . DIRECTORY_SEPARATOR . $plugin->folder;
            $to = self::folder() . DIRECTORY_SEPARATOR . $plugin->name;
            if (false === rename($from, $to)) {
                Tools::log()->error('PLUGIN FOLDER RENAME ERROR: ' . $plugin->folder . ' -> ' . $plugin->name);
                return false;
            }
            $plugin->folder = $plugin->name;
        }

        // si el plugin no estaba en la lista, lo añadimos
        if (false === $plugin->installed) {
            // añadimos el plugin
            self::load();
            $plugin->installed = true;
            self::$plugins[] = $plugin;
        }

        // si el plugin estaba activado, marcamos el post_enable
        $plugin = self::get($plugin->name);
        if ($plugin->enabled) {
            $plugin->post_enable = true;
            $plugin->post_disable = false;
        }

        self::save();

        // si el plugin está activado, desplegamos los cambios
        if ($plugin->enabled) {
            self::deploy(true, true);
        }

        Tools::log()->notice('plugin-installed', ['%pluginName%' => $plugin->name]);
        Tools::log('core')->notice('plugin-installed', ['%pluginName%' => $plugin->name]);
        return true;
    }

    public static function deploy(bool $clean = true, bool $initControllers = false): void
    {
        $pluginDeploy = new PluginDeploy();
        $pluginDeploy->deploy(
            self::folder() . DIRECTORY_SEPARATOR,
            self::enabled(),
            $clean
        );

        Kernel::rebuildRoutes();
        Kernel::saveRoutes();

        DbUpdater::rebuild();

        Tools::folderDelete(Tools::folder('MyFiles', 'Cache'));

        if ($initControllers) {
            $pluginDeploy->initControllers();
        }
    }

    public static function disable(string $pluginName): bool
    {
        // si el plugin no existe o ya está desactivado, no hacemos nada
        $plugin = self::get($pluginName);
        if (null === $plugin) {
            return false;
        } elseif ($plugin->disabled()) {
            return true;
        }

        // desactivamos el plugin
        foreach (self::$plugins as $key => $value) {
            if ($value->name === $pluginName) {
                self::$plugins[$key]->enabled = false;
                self::$plugins[$key]->post_enable = false;
                self::$plugins[$key]->post_disable = true;
                break;
            }
        }
        self::save();

        // desplegamos los cambios
        self::deploy(true, true);

        Tools::log()->notice('plugin-disabled', ['%pluginName%' => $pluginName]);
        Tools::log('core')->notice('plugin-disabled', ['%pluginName%' => $pluginName]);
        return true;
    }

    public static function enable(string $pluginName): bool
    {
        // si el plugin no existe o ya está activado, no hacemos nada
        $plugin = self::get($pluginName);
        if (null === $plugin) {
            return false;
        } elseif ($plugin->enabled) {
            return true;
        }

        // si la carpeta del plugin no es igual al nombre del plugin, no podemos activarlo
        if ($plugin->folder !== $plugin->name) {
            Tools::log()->error('plugin-folder-not-equal-name', ['%pluginName%' => $pluginName]);
            return false;
        }

        // si no se cumplen las dependencias, no se activa
        if (false === $plugin->dependenciesOk(self::enabled(), true)) {
            return false;
        }

        // añadimos el plugin a la lista de activados
        foreach (self::$plugins as $key => $value) {
            if ($value->name === $pluginName) {
                self::$plugins[$key]->enabled = true;
                self::$plugins[$key]->order = self::maxOrder() + 1;
                self::$plugins[$key]->post_enable = true;
                self::$plugins[$key]->post_disable = false;
                break;
            }
        }
        self::save();

        // desplegamos los cambios
        self::deploy(false, true);

        Tools::log()->notice('plugin-enabled', ['%pluginName%' => $pluginName]);
        Tools::log('core')->notice('plugin-enabled', ['%pluginName%' => $pluginName]);
        return true;
    }

    public static function enabled(): array
    {
        $enabled = [];

        self::load();
        foreach (self::$plugins as $plugin) {
            if ($plugin->enabled) {
                $enabled[$plugin->name] = $plugin->order;
            }
        }

        // ordenamos
        asort($enabled);
        return array_keys($enabled);
    }

    public static function folder(): string
    {
        return Tools::folder('Plugins');
    }

    public static function get(string $pluginName): ?Plugin
    {
        self::load();
        foreach (self::$plugins as $plugin) {
            if ($plugin->name === $pluginName) {
                return $plugin;
            }
        }

        return null;
    }

    public static function init(): void
    {
        Kernel::startTimer('plugins::init');
        $save = false;

        // ejecutamos los procesos init de los plugins
        foreach (self::list(true, 'order') as $plugin) {
            if ($plugin->init()) {
                $save = true;
            }
        }

        if ($save) {
            self::save();
        }

        Kernel::stopTimer('plugins::init');
    }

    public static function isEnabled(string $pluginName): bool
    {
        return in_array($pluginName, self::enabled());
    }

    public static function isInstalled(string $pluginName): bool
    {
        $plugin = self::get($pluginName);
        return empty($plugin) ? false : $plugin->installed;
    }

    /**
     * @param bool $hidden
     * @param string $orderBy
     * @return Plugin[]
     */
    public static function list(bool $hidden = false, string $orderBy = 'name'): array
    {
        $list = [];

        self::load();
        foreach (self::$plugins as $plugin) {
            if ($hidden || false === $plugin->hidden) {
                $list[] = $plugin;
            }
        }

        // ordenamos
        switch ($orderBy) {
            default:
                // ordenamos por nombre
                usort($list, function ($a, $b) {
                    return strcasecmp($a->name, $b->name);
                });
                break;

            case 'order':
                // ordenamos por orden
                usort($list, function ($a, $b) {
                    return $a->order - $b->order;
                });
                break;
        }

        return $list;
    }

    public static function load(): void
    {
        if (null === self::$plugins) {
            self::$plugins = [];
            self::loadFromFile();
            self::loadFromFolder();
        }
    }

    public static function remove(string $pluginName): bool
    {
        if (Tools::config('disable_rm_plugins', false)) {
            return false;
        }

        // si el plugin no existe o está activado, no se puede eliminar
        $plugin = self::get($pluginName);
        if (null === $plugin || $plugin->enabled) {
            return false;
        }

        // eliminamos el directorio
        if (false === $plugin->delete()) {
            return false;
        }

        // eliminamos el plugin de la lista
        foreach (self::$plugins as $i => $plugin) {
            if ($plugin->name === $pluginName) {
                unset(self::$plugins[$i]);
                break;
            }
        }
        self::save();

        Tools::log()->notice('plugin-deleted', ['%pluginName%' => $pluginName]);
        Tools::log('core')->notice('plugin-deleted', ['%pluginName%' => $pluginName]);
        return true;
    }

    private static function loadFromFile(): void
    {
        $filePath = Tools::folder('MyFiles', self::FILE_NAME);
        if (false === file_exists($filePath)) {
            return;
        }

        // leemos el fichero y añadimos los plugins
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);
        if (empty($data)) {
            return;
        }
        foreach ($data as $item) {
            // comprobamos si el plugin ya está en la lista
            $plugin = self::get($item['name']);
            if ($plugin) {
                continue;
            }

            $plugin = new Plugin($item);
            if ($plugin->exists()) {
                self::$plugins[] = $plugin;
            }
        }
    }

    private static function loadFromFolder(): void
    {
        if (false === file_exists(self::folder())) {
            return;
        }

        // revisamos la carpeta de plugins para añadir los que no estén en el fichero
        $dir = new DirectoryIterator(self::folder());
        foreach ($dir as $file) {
            if (false === $file->isDir() || $file->isDot()) {
                continue;
            }

            // comprobamos si el plugin ya está en la lista
            $pluginName = $file->getFilename();
            $plugin = self::get($pluginName);
            if ($plugin) {
                continue;
            }

            // no está en la lista, lo añadimos
            self::$plugins[] = new Plugin(['name' => $pluginName, 'folder' => $pluginName]);
        }
    }

    private static function maxOrder(): int
    {
        $max = 0;
        foreach (self::$plugins as $plugin) {
            if ($plugin->order > $max) {
                $max = $plugin->order;
            }
        }

        return $max;
    }

    private static function save(): void
    {
        // repasamos todos los plugins activos para asegurarnos de que cumplen las dependencias
        while (true) {
            foreach (self::$plugins as $key => $plugin) {
                if ($plugin->enabled && false === $plugin->dependenciesOk(self::enabled())) {
                    self::$plugins[$key]->enabled = false;
                    continue 2;
                }
            }
            break;
        }

        // si la carpeta MyFiles no existe, la creamos
        Tools::folderCheckOrCreate(Tools::folder('MyFiles'));

        $json = json_encode(self::$plugins, JSON_PRETTY_PRINT);
        file_put_contents(Tools::folder('MyFiles', self::FILE_NAME), $json);
    }

    private static function testZipFile(ZipArchive &$zipFile, string $zipPath, string $zipName): bool
    {
        $result = $zipFile->open($zipPath, ZipArchive::CHECKCONS);
        if (true !== $result) {
            Tools::log()->error('ZIP error: ' . $result);
            return false;
        }

        // comprobamos que el plugin tiene un fichero facturascripts.ini
        $zipIndex = $zipFile->locateName('facturascripts.ini', ZipArchive::FL_NODIR);
        if (false === $zipIndex) {
            Tools::log()->error(
                'plugin-not-compatible',
                ['%pluginName%' => $zipName, '%version%' => Kernel::version()]
            );
            return false;
        }

        // y que el archivo está en el directorio raíz
        $pathIni = $zipFile->getNameIndex($zipIndex);
        if (count(explode('/', $pathIni)) !== 2) {
            Tools::log()->error('zip-error-wrong-structure');
            return false;
        }

        // obtenemos la lista de directorios
        $folders = [];
        for ($index = 0; $index < $zipFile->numFiles; $index++) {
            $data = $zipFile->statIndex($index);
            $path = explode('/', $data['name']);
            if (count($path) > 1) {
                $folders[$path[0]] = $path[0];
            }
        }

        // si hay más de uno, devolvemos false
        if (count($folders) != 1) {
            Tools::log()->error('zip-error-wrong-structure');
            return false;
        }

        return true;
    }
}
