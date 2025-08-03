<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Internal;

use Exception;
use FacturaScripts\Core\Base\MenuManager;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Translator;

final class PluginsDeploy
{
    /** @var array */
    private static $enabledPlugins = [];

    /** @var array */
    private static $fileList = [];

    public static function initControllers(): void
    {
        $menuManager = new MenuManager();
        $menuManager->init();
        $pageNames = [];

        $files = Tools::folderScan(Tools::folder('Dinamic', 'Controller'), false);
        foreach ($files as $fileName) {
            if (substr($fileName, -4) !== '.php') {
                continue;
            }

            // excluimos Installer y los que comienzan por Api
            $controllerName = basename($fileName, '.php');
            if ($controllerName === 'Installer' || str_starts_with($controllerName, 'Api')) {
                continue;
            }

            // validate controller name to prevent path traversal
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $controllerName)) {
                Tools::log()->warning('Invalid controller name: ' . $controllerName);
                continue;
            }

            $controllerNamespace = '\\FacturaScripts\\Dinamic\\Controller\\' . $controllerName;
            Tools::log()->debug('Loading controller: ' . $controllerName);

            if (!class_exists($controllerNamespace)) {
                // we force the loading of the file because at this point the autoloader will not find it
                require Tools::folder('Dinamic', 'Controller', $controllerName . '.php');
            }

            try {
                $controller = new $controllerNamespace($controllerName);
                $menuManager->selectPage($controller->getPageData());
                $pageNames[] = $controllerName;
            } catch (Exception $exc) {
                Tools::log()->critical('cant-load-controller', ['%controllerName%' => $controllerName]);
                Tools::log()->critical($exc->getMessage());
            }
        }

        $menuManager->removeOld($pageNames);
        $menuManager->reload();

        // checks app homepage
        $saveSettings = false;
        if (!in_array(Tools::settings('default', 'homepage', ''), $pageNames)) {
            Tools::settingsSet('default', 'homepage', 'AdminPlugins');
            $saveSettings = true;
        }
        if ($saveSettings) {
            Tools::settingsSave();
        }
    }

    public static function run(array $enabledPlugins, bool $clean = true): void
    {
        self::$enabledPlugins = array_reverse($enabledPlugins);
        self::$fileList = [];

        $folders = ['Assets', 'Controller', 'Data', 'Error', 'Lib', 'Model', 'Table', 'View', 'Worker', 'XMLView'];
        foreach ($folders as $folder) {
            if ($clean) {
                Tools::folderDelete(Tools::folder('Dinamic', $folder));
            }

            Tools::folderCheckOrCreate(Tools::folder('Dinamic', $folder));

            // examine the plugins
            foreach (self::$enabledPlugins as $pluginName) {
                // link files from the plugin, if it exists
                if (file_exists(Tools::folder('Plugins', $pluginName, $folder))) {
                    self::linkFiles($folder, 'Plugins', $pluginName);
                }
            }

            // examine the core
            if (file_exists(Tools::folder('Core', $folder))) {
                self::linkFiles($folder);
            }
        }

        // reload translations
        Translator::deploy();
        Translator::reload();
    }

    private static function extensionSupport(string $namespace): bool
    {
        return $namespace === 'FacturaScripts\Dinamic\Controller';
    }

    private static function getClassType(string $fileName, string $folder, string $place, string $pluginName): string
    {
        $path = empty($pluginName) ?
            Tools::folder($place, $folder, $fileName) :
            Tools::folder($place, $pluginName, $folder, $fileName);

        if (!file_exists($path)) {
            throw new Exception('Unable to locate plugin class: ' . $fileName . ' on ' . $path);
        }

        if (!is_file($path)) {
            throw new Exception('Path is not a file: ' . $path);
        }

        if (!is_readable($path)) {
            throw new Exception('File is not readable: ' . $path);
        }

        $content = file_get_contents($path);
        $tokens = token_get_all($content);

        $isAbstract = false;
        $foundClass = false;

        foreach ($tokens as $i => $token) {
            // Ignorar tokens que no son arrays (como ; o {)
            if (!is_array($token)) {
                continue;
            }

            // Si encontramos abstract
            if ($token[0] === T_ABSTRACT) {
                $isAbstract = true;
            }

            // Si encontramos class después de abstract (o sin abstract)
            if ($token[0] === T_CLASS) {
                $foundClass = true;
                break;
            }

            // Si encontramos otra palabra clave estructural, reset abstract
            if (in_array($token[0], [T_FUNCTION, T_INTERFACE, T_TRAIT])) {
                $isAbstract = false;
            }
        }

        if (!$foundClass) {
            return '';
        }

        return $isAbstract ? 'abstract class' : 'class';
    }

    private static function linkFile(string $fileName, string $folder, string $filePath): void
    {
        $path = Tools::folder('Dinamic', $folder, $fileName);

        if (!copy($filePath, $path)) {
            throw new Exception('Failed to copy file: ' . $filePath . ' to ' . $path);
        }

        self::$fileList[$folder][$fileName] = $fileName;
    }

    private static function linkFiles(string $folder, string $place = 'Core', string $pluginName = ''): void
    {
        $path = empty($pluginName) ?
            Tools::folder($place, $folder) :
            Tools::folder($place, $pluginName, $folder);

        foreach (Tools::folderScan($path, true) as $fileName) {
            if (isset(self::$fileList[$folder][$fileName])) {
                continue;
            }

            $fileInfo = pathinfo($fileName);
            $filePath = Tools::folder($path, $fileName);

            if (is_dir($filePath)) {
                Tools::folderCheckOrCreate(Tools::folder('Dinamic', $folder, $fileName));
                continue;
            } elseif ($fileInfo['filename'] === '' || !is_file($filePath)) {
                continue;
            }

            $extension = $fileInfo['extension'] ?? '';
            switch ($extension) {
                case 'php':
                    self::linkPHPFile($fileName, $folder, $place, $pluginName);
                    break;

                case 'xml':
                    self::linkXMLFile($fileName, $folder, $filePath);
                    break;

                default:
                    self::linkFile($fileName, $folder, $filePath);
            }
        }
    }

    private static function linkPHPFile(string $fileName, string $folder, string $place, string $pluginName): void
    {
        $auxNamespace = empty($pluginName) ? $place : 'Plugins\\' . $pluginName;
        $namespace = 'FacturaScripts\\' . $auxNamespace . '\\' . $folder;
        $newNamespace = "FacturaScripts\Dinamic\\" . $folder;

        $paths = explode(DIRECTORY_SEPARATOR, $fileName);
        for ($key = 0; $key < count($paths) - 1; ++$key) {
            $namespace .= '\\' . $paths[$key];
            $newNamespace .= '\\' . $paths[$key];
        }

        $classType = self::getClassType($fileName, $folder, $place, $pluginName);
        if (empty($classType)) {
            // No es un archivo de clase, lo ignoramos
            return;
        }

        $className = basename($fileName, '.php');
        $txt = '<?php namespace ' . $newNamespace . ";\n\n"
            . '/**' . "\n"
            . ' * Class created by Core/Internal/PluginsDeploy' . "\n"
            . ' * @author FacturaScripts <carlos@facturascripts.com>' . "\n"
            . ' */' . "\n"
            . $classType . ' ' . $className . ' extends \\' . $namespace . '\\' . $className;

        $txt .= self::extensionSupport($newNamespace) ? "\n{\n\tuse \FacturaScripts\Core\Template\ExtensionsTrait;\n}\n" : "\n{\n}\n";

        $destinationPath = Tools::folder('Dinamic', $folder, $fileName);
        if (file_put_contents($destinationPath, $txt) === false) {
            throw new Exception('Unable to write file: ' . $destinationPath);
        }

        self::$fileList[$folder][$fileName] = $fileName;
    }

    private static function linkXMLFile(string $fileName, string $folder, string $originPath): void
    {
        // Find extensions
        $extensions = [];
        foreach (self::$enabledPlugins as $pluginName) {
            $extensionPath = Tools::folder('Plugins', $pluginName, 'Extension', $folder, $fileName);
            if (file_exists($extensionPath)) {
                $extensions[] = $extensionPath;
            }
        }

        // Merge XML files
        $xml = simplexml_load_file($originPath);
        if (false === $xml) {
            $errors = libxml_get_errors();
            $errorMsg = !empty($errors) ? $errors[0]->message : 'Unknown error';
            throw new Exception('Unable to load XML file: ' . $originPath . ' - ' . $errorMsg);
        }

        foreach ($extensions as $extension) {
            $xmlExtension = simplexml_load_file($extension);
            if ($xmlExtension === false) {
                $errors = libxml_get_errors();
                $errorMsg = !empty($errors) ? $errors[0]->message : 'Unknown error';
                throw new Exception('Unable to load XML extension file: ' . $extension . ' - ' . $errorMsg);
            }

            self::mergeXMLDocs($xml, $xmlExtension);
        }

        $destinationPath = Tools::folder('Dinamic', $folder, $fileName);
        if ($xml->asXML($destinationPath) === false) {
            throw new Exception('Unable to write XML file: ' . $destinationPath);
        }

        self::$fileList[$folder][$fileName] = $fileName;
    }

    private static function mergeXMLDocs(&$source, $extension): void
    {
        foreach ($extension->children() as $extChild) {
            // we need $num to know which dom element number to overwrite
            $num = -1;

            $found = false;
            foreach ($source->children() as $child) {
                if ($child->getName() == $extChild->getName()) {
                    $num++;
                }

                if (!self::mergeXMLDocsCompare($child, $extChild)) {
                    continue;
                }

                // Element found. Overwrite or append children? Only for parents example group, etc.
                $found = true;
                $extDom = dom_import_simplexml($extChild);
                if ($extDom === false) {
                    throw new Exception('Failed to convert SimpleXML to DOM');
                }

                switch (mb_strtolower($extDom->getAttribute('overwrite'))) {
                    case 'true':
                        $sourceDom = dom_import_simplexml($source);
                        if ($sourceDom === false) {
                            throw new Exception('Failed to convert SimpleXML source to DOM');
                        }

                        $newElement = $sourceDom->ownerDocument->importNode($extDom, true);
                        $targetNode = $sourceDom->getElementsByTagName($newElement->nodeName)->item($num);
                        if ($targetNode === null) {
                            throw new Exception('Target node not found for replacement');
                        }

                        $sourceDom->replaceChild($newElement, $targetNode);
                        break;

                    default:
                        self::mergeXMLDocs($child, $extChild);
                }
                break;
            }

            // Elemento not found. Append all or Replace child, Only for child example widget, etc.
            if (!$found) {
                $sourceDom = dom_import_simplexml($source);
                if ($sourceDom === false) {
                    throw new Exception('Failed to convert SimpleXML source to DOM');
                }

                $extDom = dom_import_simplexml($extChild);
                if ($extDom === false) {
                    throw new Exception('Failed to convert SimpleXML extension to DOM');
                }

                $newElement = $sourceDom->ownerDocument->importNode($extDom, true);

                switch (mb_strtolower($extDom->getAttribute('overwrite'))) {
                    case 'true':
                        $targetNode = $sourceDom->getElementsByTagName('*')->item($num);
                        if ($targetNode === null) {
                            throw new Exception('No target node found at position ' . $num);
                        }
                        $sourceDom->replaceChild($newElement, $targetNode);
                        break;

                    default:
                        $sourceDom->appendChild($newElement);
                        break;
                }
            }
        }
    }

    private static function mergeXMLDocsCompare($source, $extension): bool
    {
        if ($source->getName() != $extension->getName()) {
            return false;
        }

        foreach ($extension->attributes() as $extAttr => $extAttrValue) {
            // We use name as identifier except with row, which is identified by type
            if ($extAttr != 'name' && $extension->getName() != 'row') {
                continue;
            } elseif ($extAttr != 'type' && $extension->getName() == 'row') {
                continue;
            }

            foreach ($source->attributes() as $attr => $attrValue) {
                if ($attr == $extAttr) {
                    return (string)$extAttrValue == (string)$attrValue;
                }
            }
        }

        return in_array($extension->getName(), ['columns', 'modals', 'rows']);
    }
}
