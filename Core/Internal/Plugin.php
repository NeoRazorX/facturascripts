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

namespace FacturaScripts\Core\Internal;

use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Tools;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

final class Plugin
{
    /** @var bool */
    public $compatible = false;

    /** @var string */
    private $compatibilityDescription = '';

    /** @var string */
    public $description = 'unknown';

    /** @var bool */
    public $enabled = false;

    /** @var string */
    public $folder = '-';

    /** @var bool */
    public $hidden = false;

    /** @var bool */
    public $installed = false;

    /** @var float */
    public $min_version = 0;

    /** @var float */
    public $min_php = 7.4;

    /** @var string */
    public $name = '-';

    /** @var int */
    public $order = 0;

    /** @var bool */
    public $post_disable = false;

    /** @var bool */
    public $post_enable = false;

    /** @var array */
    public $require = [];

    /** @var array */
    public $require_php = [];

    /** @var float */
    public $version = 0.0;

    public function __construct(array $data = [])
    {
        $this->enabled = $data['enabled'] ?? false;
        $this->folder = $data['folder'] ?? $data['name'] ?? '-';
        $this->name = $data['name'] ?? '-';
        $this->order = intval($data['order'] ?? 0);
        $this->post_disable = $data['post_disable'] ?? false;
        $this->post_enable = $data['post_enable'] ?? false;

        $this->loadIniFile();
    }

    public function compatibilityDescription(): string
    {
        return $this->compatibilityDescription;
    }

    public function delete(): bool
    {
        // si no existe el directorio, devolvemos true
        if (!file_exists($this->folder())) {
            return true;
        }

        // eliminamos el directorio del plugin
        $dir = new RecursiveDirectoryIterator($this->folder(), FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($dir, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        return rmdir($this->folder());
    }

    public function dependenciesOk(array $enabledPlugins, bool $showErrors = false): bool
    {
        // si no es compatible, devolvemos false
        if (!$this->compatible) {
            return false;
        }

        // comprobamos que los plugins requeridos estén activados
        foreach ($this->require as $require) {
            if (in_array($require, $enabledPlugins)) {
                continue;
            }
            if ($showErrors) {
                Tools::log()->warning('plugin-needed', ['%pluginName%' => $require]);
            }
            return false;
        }

        // comprobamos que las extensiones de PHP requeridas estén activadas
        foreach ($this->require_php as $require) {
            if (extension_loaded($require)) {
                continue;
            }
            if ($showErrors) {
                Tools::log()->warning('php-extension-needed', ['%extension%' => $require]);
            }
            return false;
        }

        return true;
    }

    public function disabled(): bool
    {
        return !$this->enabled;
    }

    public function exists(): bool
    {
        return file_exists($this->folder());
    }

    public function folder(): string
    {
        return Plugins::folder() . DIRECTORY_SEPARATOR . $this->name;
    }

    public function forja(string $field, $default)
    {
        // buscamos el plugin en la lista pública de plugins
        foreach (Forja::plugins() as $item) {
            if ($item['name'] === $this->name) {
                return $item[$field] ?? $default;
            }
        }

        // no lo hemos encontrado en la lista de plugins, lo buscamos en la lista de builds
        foreach (Forja::builds() as $item) {
            if ($item['name'] === $this->name) {
                return $item[$field] ?? $default;
            }
        }

        return $default;
    }

    public static function getFromZip(string $zipPath): ?Plugin
    {
        $zip = new ZipArchive();
        if (true !== $zip->open($zipPath)) {
            return null;
        }

        // cargamos los datos del init
        $zipIndex = $zip->locateName('facturascripts.ini', ZipArchive::FL_NODIR);
        $iniData = parse_ini_string($zip->getFromIndex($zipIndex));
        $plugin = new Plugin();
        $pathIni = $zip->getNameIndex($zipIndex);
        $plugin->folder = substr($pathIni, 0, strpos($pathIni, '/'));
        $plugin->loadIniData($iniData);
        $plugin->enabled = Plugins::isEnabled($plugin->name);
        $zip->close();

        return $plugin;
    }

    public function hasUpdate(): bool
    {
        return $this->version < $this->forja('version', 0.0);
    }

    public function init(): bool
    {
        // si el plugin no está activado y no tiene post_disable, no hacemos nada
        if ($this->disabled() && !$this->post_disable) {
            return false;
        }

        // si el plugin no tiene clase Init, no hacemos nada
        $className = 'FacturaScripts\\Plugins\\' . $this->name . '\\Init';
        if (!class_exists($className)) {
            $this->post_disable = false;
            $this->post_enable = false;
            return false;
        }

        // ejecutamos los procesos de la clase Init del plugin
        $init = new $className();
        if ($this->enabled && $this->post_enable && Kernel::lock('plugin-init-update')) {
            $init->update();
            Kernel::unlock('plugin-init-update');
        }
        if ($this->disabled() && $this->post_disable && Kernel::lock('plugin-init-uninstall')) {
            $init->uninstall();
            Kernel::unlock('plugin-init-uninstall');
        }
        if ($this->enabled) {
            $init->init();
        }

        $done = $this->post_disable || $this->post_enable;

        // desactivamos los flags de post_enable y post_disable
        $this->post_disable = false;
        $this->post_enable = false;

        return $done;
    }

    private function checkCompatibility(): void
    {
        // si la versión de PHP es menor que la requerida, no es compatible
        if (version_compare(PHP_VERSION, $this->min_php, '<')) {
            $this->compatible = false;
            $this->compatibilityDescription = Tools::lang()->trans('plugin-phpversion-error', [
                '%pluginName%' => $this->name,
                '%php%' => $this->min_php
            ]);
            return;
        }

        // si la versión de FacturaScripts es menor que la requerida, no es compatible
        if (Kernel::version() < $this->min_version) {
            $this->compatible = false;
            $this->compatibilityDescription = Tools::lang()->trans('plugin-needs-fs-version', [
                '%pluginName%' => $this->name,
                '%minVersion%' => $this->min_version,
                '%version%' => Kernel::version()
            ]);
            return;
        }

        // si la versión requerida es menor que 2021, no es compatible
        if ($this->min_version < 2020) {
            $this->compatible = false;
            $this->compatibilityDescription = Tools::lang()->trans('plugin-not-compatible', [
                '%pluginName%' => $this->name,
                '%version%' => Kernel::version()
            ]);
            return;
        }

        $this->compatible = true;
    }

    private function hidden(): bool
    {
        if (defined('FS_HIDDEN_PLUGINS') && FS_HIDDEN_PLUGINS !== '') {
            return in_array($this->name, explode(',', FS_HIDDEN_PLUGINS));
        }

        return false;
    }

    private function loadIniData(array $data): void
    {
        $this->description = $data['description'] ?? $this->description;
        $this->min_version = floatval($data['min_version'] ?? 0);
        $this->min_php = floatval($data['min_php'] ?? $this->min_php);
        $this->name = $data['name'] ?? $this->name;

        $this->require = [];
        if ($data['require'] ?? '') {
            foreach (explode(',', $data['require']) as $item) {
                $this->require[] = trim($item);
            }
        }

        $this->require_php = [];
        if ($data['require_php'] ?? '') {
            foreach (explode(',', $data['require_php']) as $item) {
                $this->require_php[] = trim($item);
            }
        }

        $this->version = floatval($data['version'] ?? 0);
        $this->installed = $this->exists();

        $this->hidden = $this->hidden();
        if ($this->disabled()) {
            $this->order = 0;
        }

        $this->checkCompatibility();
    }

    private function loadIniFile(): void
    {
        $iniPath = $this->folder() . DIRECTORY_SEPARATOR . 'facturascripts.ini';
        if (!file_exists($iniPath)) {
            return;
        }

        $data = file_get_contents($iniPath);
        $iniData = parse_ini_string($data);
        if ($iniData) {
            $this->loadIniData($iniData);
        }
    }
}
