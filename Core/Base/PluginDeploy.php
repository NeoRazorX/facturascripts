<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez  <carlos@facturascripts.com>
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
namespace FacturaScripts\Core\Base;

use Exception;
use FacturaScripts\Core\App\AppSettings;

/**
 * Description of PluginDeploy
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class PluginDeploy
{

    /**
     *
     * @var array
     */
    private $fileList;

    /**
     * System translator.
     *
     * @var Translator
     */
    private $i18n;

    /**
     * Manage the log of the entire application.
     *
     * @var Minilog
     */
    private $minilog;

    /**
     * PluginDeploy constructor.
     */
    public function __construct()
    {
        $this->fileList = [];
        $this->i18n = new Translator();
        $this->minilog = new MiniLog();
    }

    /**
     * Deploy all the necessary files in the Dinamic folder to be able to use plugins
     * with the autoloader, but following the priority system of FacturaScripts.
     *
     * @param string $pluginPath
     * @param array  $enabledPlugins
     * @param bool   $clean
     */
    public function deploy(string $pluginPath, array $enabledPlugins, bool $clean = true)
    {
        $folders = ['Assets', 'Controller', 'Data', 'Lib', 'Model', 'Table', 'View', 'XMLView'];
        foreach ($folders as $folder) {
            if ($clean) {
                $this->cleanFolder(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder);
            }

            $this->createFolder(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder);

            /// examine the plugins
            foreach ($enabledPlugins as $pluginName) {
                if (file_exists($pluginPath . $pluginName . DIRECTORY_SEPARATOR . $folder)) {
                    $this->linkFiles($folder, 'Plugins', $pluginName);
                }
            }

            /// examine the core
            if (file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . $folder)) {
                $this->linkFiles($folder);
            }
        }
    }

    /**
     * Initialize the controllers dynamically.
     */
    public function initControllers()
    {
        $cache = new Cache();
        $menuManager = new MenuManager();
        $menuManager->init();
        $pageNames = [];

        $files = $this->scanFolders(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Controller', false);
        foreach ($files as $fileName) {
            if (substr($fileName, -4) !== '.php') {
                continue;
            }

            $controllerName = substr($fileName, 0, -4);
            $controllerNamespace = 'FacturaScripts\\Dinamic\\Controller\\' . $controllerName;

            if (!class_exists($controllerNamespace)) {
                /// we force the loading of the file because at this point the autoloader will not find it
                require FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . $controllerName . '.php';
            }

            try {
                $controller = new $controllerNamespace($cache, $this->i18n, $this->minilog, $controllerName);
                $menuManager->selectPage($controller->getPageData());
                $pageNames[] = $controllerName;
            } catch (Exception $exc) {
                $this->minilog->critical($this->i18n->trans('cant-load-controller', ['%controllerName%' => $controllerName]));
            }
        }

        $menuManager->removeOld($pageNames);
        $menuManager->reload();

        /// checks app homepage
        if (!in_array(AppSettings::get('default', 'homepage', ''), $pageNames)) {
            $appSettings = new AppSettings();
            $appSettings->set('default', 'homepage', 'AdminPlugins');
            $appSettings->save();
        }
    }

    /**
     * Delete the $folder and its files.
     *
     * @param string $folder
     *
     * @return bool
     */
    private function cleanFolder(string $folder): bool
    {
        $done = true;
        if (file_exists($folder)) {
            /// Comprobamos los archivos que no son '.' ni '..'
            $items = array_diff(scandir($folder, SCANDIR_SORT_ASCENDING), ['.', '..']);

            /// Ahora recorremos y eliminamos lo que encontramos
            foreach ($items as $item) {
                if (is_dir($folder . DIRECTORY_SEPARATOR . $item)) {
                    $done = $this->cleanFolder($folder . DIRECTORY_SEPARATOR . $item . DIRECTORY_SEPARATOR);
                } else {
                    $done = unlink($folder . DIRECTORY_SEPARATOR . $item);
                }
            }
        }

        return $done;
    }

    /**
     * Create the folder.
     *
     * @param string $folder
     *
     * @return bool
     */
    private function createFolder(string $folder): bool
    {
        if (!file_exists($folder) && !@mkdir($folder, 0775, true)) {
            $this->minilog->critical($this->i18n->trans('cant-create-folder', ['%folderName%' => $folder]));
            return false;
        }

        return true;
    }

    private function getClassType(string $fileName, string $folder, string $place, string $pluginName): string
    {
        $path = FS_FOLDER . DIRECTORY_SEPARATOR . $place;
        $path .= empty($pluginName) ? DIRECTORY_SEPARATOR . $folder : DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . $folder;

        $txt = file_get_contents($path . DIRECTORY_SEPARATOR . $fileName);
        if (strpos($txt, 'abstract class ') !== false) {
            return 'abstract class';
        }

        return 'class';
    }

    /**
     * Link the files.
     *
     * @param string $folder
     * @param string $place
     * @param string $pluginName
     */
    private function linkFiles(string $folder, string $place = 'Core', string $pluginName = '')
    {
        $path = FS_FOLDER . DIRECTORY_SEPARATOR . $place;
        $path .= empty($pluginName) ? DIRECTORY_SEPARATOR . $folder : DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . $folder;

        foreach ($this->scanFolders($path) as $fileName) {
            $infoFile = pathinfo($fileName);
            if (is_dir($path . DIRECTORY_SEPARATOR . $fileName)) {
                $this->createFolder(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName);
            } elseif ($infoFile['filename'] === '' || !is_file($path . DIRECTORY_SEPARATOR . $fileName)) {
                continue;
            } elseif (isset($infoFile['extension']) && $infoFile['extension'] === 'php') {
                $this->linkClassFile($fileName, $folder, $place, $pluginName);
            } else {
                $filePath = $path . DIRECTORY_SEPARATOR . $fileName;
                $this->linkFile($fileName, $folder, $filePath);
            }
        }
    }

    /**
     * Link classes dynamically.
     *
     * @param string $fileName
     * @param string $folder
     * @param string $place
     * @param string $pluginName
     */
    private function linkClassFile(string $fileName, string $folder, string $place, string $pluginName)
    {
        if (isset($this->fileList[$folder][$fileName])) {
            return;
        }

        $auxNamespace = empty($pluginName) ? $place : "Plugins\\" . $pluginName;
        $namespace = "FacturaScripts\\" . $auxNamespace . '\\' . $folder;
        $newNamespace = "FacturaScripts\Dinamic\\" . $folder;

        $paths = explode(DIRECTORY_SEPARATOR, $fileName);
        for ($key = 0; $key < count($paths) - 1; ++$key) {
            $namespace .= '\\' . $paths[$key];
            $newNamespace .= '\\' . $paths[$key];
        }

        $className = basename($fileName, '.php');
        $txt = '<?php namespace ' . $newNamespace . ";\n\n"
            . '/**' . "\n"
            . ' * Class created by Core/Base/PluginManager' . "\n"
            . ' * @package ' . $newNamespace . "\n"
            . ' * @author Carlos García Gómez <carlos@facturascripts.com>' . "\n"
            . ' */' . "\n"
            . $this->getClassType($fileName, $folder, $place, $pluginName) . ' ' . $className . ' extends \\' . $namespace . '\\' . $className . "\n{\n}\n";

        file_put_contents(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName, $txt);
        $this->fileList[$folder][$fileName] = $fileName;
    }

    /**
     * Link other static files.
     *
     * @param string $fileName
     * @param string $folder
     * @param string $filePath
     */
    private function linkFile(string $fileName, string $folder, string $filePath)
    {
        if (isset($this->fileList[$folder][$fileName])) {
            return;
        }

        $path = FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName;
        copy($filePath, $path);
        $this->fileList[$folder][$fileName] = $fileName;
    }

    /**
     * Makes a recursive scan in folders inside a root folder and extracts the list of files
     * and pass its to an array as result.
     * 
     * @param string $folder
     * @param bool   $recursive
     *
     * @return array
     */
    private function scanFolders(string $folder, bool $recursive = true): array
    {
        $scan = scandir($folder, SCANDIR_SORT_ASCENDING);
        if (!is_array($scan)) {
            return [];
        }

        $rootFolder = array_diff($scan, ['.', '..']);
        if (!$recursive) {
            return $rootFolder;
        }

        $result = [];
        foreach ($rootFolder as $item) {
            $newItem = $folder . DIRECTORY_SEPARATOR . $item;
            if (is_file($newItem)) {
                $result[] = $item;
                continue;
            }
            $result[] = $item;
            foreach ($this->scanFolders($newItem) as $item2) {
                $result[] = $item . DIRECTORY_SEPARATOR . $item2;
            }
        }

        return $result;
    }
}
