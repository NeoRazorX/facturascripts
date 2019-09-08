<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    private $fileList = [];

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
        $fileManager = $this->toolBox()->files();
        $folders = ['Assets', 'Controller', 'Data', 'Lib', 'Model', 'Table', 'View', 'XMLView'];
        foreach ($folders as $folder) {
            if ($clean) {
                $fileManager->delTree(\FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder);
            }

            $this->createFolder(\FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder);

            /// examine the plugins
            foreach (array_reverse($enabledPlugins) as $pluginName) {
                if (file_exists($pluginPath . $pluginName . DIRECTORY_SEPARATOR . $folder)) {
                    $this->linkFiles($folder, 'Plugins', $pluginName);
                }
            }

            /// examine the core
            if (file_exists(\FS_FOLDER . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . $folder)) {
                $this->linkFiles($folder);
            }
        }
    }

    /**
     * Initialize the controllers dynamically.
     */
    public function initControllers()
    {
        $menuManager = new MenuManager();
        $menuManager->init();
        $pageNames = [];

        $files = $this->toolBox()->files()->scanFolder(\FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Controller', false);
        foreach ($files as $fileName) {
            if (substr($fileName, -4) !== '.php') {
                continue;
            }

            $controllerName = substr($fileName, 0, -4);
            $controllerNamespace = '\\FacturaScripts\\Dinamic\\Controller\\' . $controllerName;

            if (!class_exists($controllerNamespace)) {
                /// we force the loading of the file because at this point the autoloader will not find it
                require \FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . $controllerName . '.php';
            }

            try {
                $controller = new $controllerNamespace($controllerName);
                $menuManager->selectPage($controller->getPageData());
                $pageNames[] = $controllerName;
            } catch (Exception $exc) {
                $this->toolBox()->i18nLog()->critical('cant-load-controller', ['%controllerName%' => $controllerName]);
            }
        }

        $menuManager->removeOld($pageNames);
        $menuManager->reload();

        /// checks app homepage
        $appSettings = $this->toolBox()->appSettings();
        if (!in_array($appSettings->get('default', 'homepage', ''), $pageNames)) {
            $appSettings->set('default', 'homepage', 'AdminPlugins');
            $appSettings->save();
        }
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
        if ($this->toolBox()->files()->createFolder($folder, true)) {
            return true;
        }

        $this->toolBox()->i18nLog()->critical('cant-create-folder', ['%folderName%' => $folder]);
        return false;
    }

    /**
     * 
     * @param string $namespace
     *
     * @return bool
     */
    private function extensionSupport(string $namespace)
    {
        switch ($namespace) {
            case 'FacturaScripts\Dinamic\Controller':
            case 'FacturaScripts\Dinamic\Model';
                return true;

            default:
                return false;
        }
    }

    /**
     * 
     * @param string $fileName
     * @param string $folder
     * @param string $place
     * @param string $pluginName
     *
     * @return string
     */
    private function getClassType(string $fileName, string $folder, string $place, string $pluginName): string
    {
        $path = \FS_FOLDER . DIRECTORY_SEPARATOR . $place;
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
        $path = \FS_FOLDER . DIRECTORY_SEPARATOR . $place;
        $path .= empty($pluginName) ? DIRECTORY_SEPARATOR . $folder : DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . $folder;

        foreach ($this->toolBox()->files()->scanFolder($path, true) as $fileName) {
            $infoFile = pathinfo($fileName);
            if (is_dir($path . DIRECTORY_SEPARATOR . $fileName)) {
                $this->createFolder(\FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName);
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
            . ' * @author FacturaScripts <carlos@facturascripts.com>' . "\n"
            . ' */' . "\n"
            . $this->getClassType($fileName, $folder, $place, $pluginName) . ' ' . $className . ' extends \\' . $namespace . '\\' . $className;

        $txt .= $this->extensionSupport($newNamespace) ? "\n{\n\tuse \FacturaScripts\Core\Base\ExtensionsTrait;\n}\n" : "\n{\n}\n";

        file_put_contents(\FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName, $txt);
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

        $path = \FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName;
        copy($filePath, $path);
        $this->fileList[$folder][$fileName] = $fileName;
    }

    /**
     * 
     * @return ToolBox
     */
    private function toolBox()
    {
        return new ToolBox();
    }
}
