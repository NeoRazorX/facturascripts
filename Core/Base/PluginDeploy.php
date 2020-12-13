<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use SimpleXMLElement;

/**
 * Description of PluginDeploy
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
final class PluginDeploy
{

    /**
     *
     * @var array
     */
    private $enabledPlugins = [];

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
        $this->enabledPlugins = array_reverse($enabledPlugins);

        $fileManager = $this->toolBox()->files();
        $folders = ['Assets', 'Controller', 'Data', 'Lib', 'Model', 'Table', 'View', 'XMLView'];
        foreach ($folders as $folder) {
            if ($clean) {
                $fileManager->delTree(\FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder);
            }

            $this->createFolder(\FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder);

            /// examine the plugins
            foreach ($this->enabledPlugins as $pluginName) {
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
                $this->toolBox()->log()->critical($exc->getMessage());
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
        return $namespace === 'FacturaScripts\Dinamic\Controller';
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
        $path = \FS_FOLDER . DIRECTORY_SEPARATOR . $place . DIRECTORY_SEPARATOR;
        $path .= empty($pluginName) ? $folder : $pluginName . DIRECTORY_SEPARATOR . $folder;

        $txt = file_get_contents($path . DIRECTORY_SEPARATOR . $fileName);
        return strpos($txt, 'abstract class ') === false ? 'class' : 'abstract class';
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
        $path = \FS_FOLDER . DIRECTORY_SEPARATOR . $place . DIRECTORY_SEPARATOR;
        $path .= empty($pluginName) ? $folder : $pluginName . DIRECTORY_SEPARATOR . $folder;

        foreach ($this->toolBox()->files()->scanFolder($path, true) as $fileName) {
            if (isset($this->fileList[$folder][$fileName])) {
                continue;
            }

            $fileInfo = pathinfo($fileName);
            if (is_dir($path . DIRECTORY_SEPARATOR . $fileName)) {
                $this->createFolder(\FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName);
                continue;
            } elseif ($fileInfo['filename'] === '' || !is_file($path . DIRECTORY_SEPARATOR . $fileName)) {
                continue;
            } elseif ('Trait.php' === substr($fileName, -9)) {
                continue;
            }

            $filePath = $path . DIRECTORY_SEPARATOR . $fileName;
            $extension = $fileInfo['extension'] ?? '';
            switch ($extension) {
                case 'php':
                    $this->linkPHPFile($fileName, $folder, $place, $pluginName);
                    break;

                case 'xml':
                    $this->linkXMLFile($fileName, $folder, $filePath);
                    break;

                default:
                    $this->linkFile($fileName, $folder, $filePath);
            }
        }
    }

    /**
     * Link PHP files dinamically.
     *
     * @param string $fileName
     * @param string $folder
     * @param string $place
     * @param string $pluginName
     */
    private function linkPHPFile(string $fileName, string $folder, string $place, string $pluginName)
    {
        $auxNamespace = empty($pluginName) ? $place : 'Plugins\\' . $pluginName;
        $namespace = 'FacturaScripts\\' . $auxNamespace . '\\' . $folder;
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
        $path = \FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName;
        copy($filePath, $path);
        $this->fileList[$folder][$fileName] = $fileName;
    }

    /**
     * Link other static files.
     *
     * @param string $fileName
     * @param string $folder
     * @param string $originPath
     */
    private function linkXMLFile(string $fileName, string $folder, string $originPath)
    {
        /// Find extensions
        $extensions = [];
        foreach ($this->enabledPlugins as $pluginName) {
            $extensionPath = \FS_FOLDER . DIRECTORY_SEPARATOR . 'Plugins' . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR
                . 'Extension' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($extensionPath)) {
                $extensions[] = $extensionPath;
            }
        }

        /// Merge XML files
        $xml = simplexml_load_file($originPath);
        foreach ($extensions as $extension) {
            $xmlExtension = simplexml_load_file($extension);
            $this->mergeXMLDocs($xml, $xmlExtension);
        }

        $destinationPath = \FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $fileName;
        $xml->asXML($destinationPath);

        $this->fileList[$folder][$fileName] = $fileName;
    }

    /**
     * 
     * @param SimpleXMLElement $source
     * @param SimpleXMLElement $extension
     */
    private function mergeXMLDocs(&$source, $extension)
    {
        foreach ($extension->children() as $extChild) {
            /// we need $num to know wich dom element number to overwrite
            $num = -1;

            $found = false;
            foreach ($source->children() as $child) {
                if ($child->getName() == $extChild->getName()) {
                    $num++;
                }

                if (!$this->mergeXMLDocsCompare($child, $extChild)) {
                    continue;
                }

                /// Element found. Overwrite or append children? Only for parents example group, etc.
                $found = true;
                $extDom = dom_import_simplexml($extChild);

                switch (mb_strtolower($extDom->getAttribute('overwrite'))) {
                    case 'true':
                        $sourceDom = dom_import_simplexml($source);
                        $newElement = $sourceDom->ownerDocument->importNode($extDom, true);
                        $sourceDom->replaceChild($newElement, $sourceDom->getElementsByTagName($newElement->nodeName)->item($num));
                        break;

                    default:
                        $this->mergeXMLDocs($child, $extChild);
                }
                break;
            }

            /// Elemento not found. Append all or Replace child, Only for child example widget, etc.
            if (!$found) {
                $sourceDom = dom_import_simplexml($source);
                $extDom = dom_import_simplexml($extChild);
                $newElement = $sourceDom->ownerDocument->importNode($extDom, true);

                switch (mb_strtolower($extDom->getAttribute('overwrite'))) {
                    case 'true':
                        $sourceDom->replaceChild($newElement, $sourceDom->getElementsByTagName('*')->item($num));
                        break;

                    default:
                        $sourceDom->appendChild($newElement);
                        break;
                }
            }
        }
    }

    /**
     * 
     * @param SimpleXMLElement $source
     * @param SimpleXMLElement $extension
     *
     * @return bool
     */
    private function mergeXMLDocsCompare($source, $extension)
    {
        if ($source->getName() != $extension->getName()) {
            return false;
        }

        foreach ($extension->attributes() as $extAttr => $extAttrValue) {
            /// We use name as identifier except with row, which is identified by type
            if ($extAttr != 'name' && $extension->getName() != 'row') {
                continue;
            } elseif ($extAttr != 'type' && $extension->getName() == 'row') {
                continue;
            }

            foreach ($source->attributes() as $attr => $attrValue) {
                if ($attr == $extAttr) {
                    return (string) $extAttrValue == (string) $attrValue;
                }
            }
        }

        return in_array($extension->getName(), ['columns', 'modals', 'rows']);
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
