<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

/**
 * Permite la traducción de cadenas de texto a diferentes idiomas. Con posibilidad de usar parámetros.
 */
final class Translator
{
    private static $defaultLang = 'es_ES';

    /** @var string */
    private $lang;

    /** @var array */
    private static $languages = [];

    /** @var array */
    private static $notFound = [];

    /** @var array */
    private static $translations = [];

    public function __construct(?string $langCode = '')
    {
        $this->setLang($langCode);
    }

    public function customTrans(?string $langCode, ?string $txt, array $parameters = []): string
    {
        $langCode = empty($langCode) ? $this->getDefaultLang() : $langCode;
        $this->load($langCode);

        $key = $this->getTransKey($txt) . '@' . $langCode;
        $translation = self::$translations[$key] ?? $txt;

        if (false === array_key_exists($key, self::$translations)) {
            self::$notFound[$key] = $txt;
        }

        $paramKeys = [];
        $paramValues = [];
        foreach ($parameters as $pkey => $value) {
            // si la key no empieza y termina por %, la ignoramos
            if (false === str_starts_with($pkey, '%') || false === str_ends_with($pkey, '%')) {
                continue;
            }

            // si el valor no es string o numérico, lo ignoramos
            if (is_string($value) || is_numeric($value)) {
                $paramKeys[] = $pkey;
                $paramValues[] = $value;
            }
        }

        // reemplazamos los parámetros en la traducción
        return str_replace($paramKeys, $paramValues, $translation);
    }

    public static function deploy(): void
    {
        Tools::folderCheckOrCreate(FS_FOLDER . '/Dinamic/Translation');

        // obtenemos las carpetas a analizar
        $folders = [FS_FOLDER . '/Core/Translation'];
        foreach (Plugins::enabled() as $name) {
            if (file_exists(FS_FOLDER . '/Plugins/' . $name . '/Translation')) {
                $folders[] = FS_FOLDER . '/Plugins/' . $name . '/Translation';
            }
        }

        // obtenemos los idiomas disponibles
        $languages = [];
        foreach ($folders as $folder) {
            foreach (scandir($folder, SCANDIR_SORT_ASCENDING) as $fileName) {
                if (str_ends_with($fileName, '.json')) {
                    $key = substr($fileName, 0, -5);
                    $languages[$key] = $key;
                }
            }
        }

        // para cada idioma, obtenemos las traducciones y las guardamos en un archivo json
        foreach ($languages as $lang) {
            $data = [];
            foreach ($folders as $folder) {
                $fileName = $folder . '/' . $lang . '.json';
                if (file_exists($fileName)) {
                    $data = array_merge($data, json_decode(file_get_contents($fileName), true));
                }
            }

            file_put_contents(
                FS_FOLDER . '/Dinamic/Translation/' . $lang . '.json',
                json_encode($data, JSON_PRETTY_PRINT)
            );
        }
    }

    public function getAvailableLanguages(): array
    {
        $languages = [];

        foreach ($this->getFolders() as $folder) {
            foreach (scandir($folder, SCANDIR_SORT_ASCENDING) as $fileName) {
                if (str_ends_with($fileName, '.json')) {
                    $key = substr($fileName, 0, -5);
                    $languages[$key] = $this->trans('languages-' . $key);
                }
            }
        }

        return $languages;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function getMissingStrings(): array
    {
        return self::$notFound;
    }

    public function getUsedStrings(): array
    {
        return self::$translations;
    }

    public function notFound(): array
    {
        return self::$notFound;
    }

    public static function reload(): void
    {
        self::$languages = [];
        self::$notFound = [];
        self::$translations = [];
    }

    public static function setDefaultLang(?string $langCode): void
    {
        self::$defaultLang = empty($langCode) ? constant('FS_LANG') : $langCode;
    }

    public function setLang(?string $langCode): void
    {
        $this->lang = empty($langCode) ? $this->getDefaultLang() : $langCode;

        $this->load($this->lang);
    }

    public function trans(?string $txt, array $parameters = []): string
    {
        return $this->customTrans($this->lang, $txt, $parameters);
    }

    private function getFolders(): array
    {
        // cargamos primero las traducciones del core
        $folders = [FS_FOLDER . '/Core/Translation'];

        // después las de dinamic
        if (file_exists(FS_FOLDER . '/Dinamic/Translation')) {
            $folders[] = FS_FOLDER . '/Dinamic/Translation';
        }

        // por último las de myfiles
        if (file_exists(FS_FOLDER . '/MyFiles/Translation')) {
            $folders[] = FS_FOLDER . '/MyFiles/Translation';
        }

        return $folders;
    }

    private function getDefaultLang(): string
    {
        return self::$defaultLang ?? constant('FS_LANG');
    }

    private function getTransKey(?string $txt): string
    {
        $specialKeys = [
            'AlbaranCliente' => 'customer-delivery-note',
            'AlbaranProveedor' => 'supplier-delivery-note',
            'FacturaCliente' => 'customer-invoice',
            'FacturaProveedor' => 'supplier-invoice',
            'PedidoCliente' => 'customer-order',
            'PedidoProveedor' => 'supplier-order',
            'PresupuestoCliente' => 'customer-estimation',
            'PresupuestoProveedor' => 'supplier-estimation',
            'AlbaranCliente-min' => 'delivery-note',
            'AlbaranProveedor-min' => 'delivery-note',
            'FacturaCliente-min' => 'invoice',
            'FacturaProveedor-min' => 'invoice',
            'PedidoCliente-min' => 'order',
            'PedidoProveedor-min' => 'order',
            'PresupuestoCliente-min' => 'estimation',
            'PresupuestoProveedor-min' => 'estimation',
        ];

        return $specialKeys[$txt] ?? $txt ?? '';
    }

    private function load(string $lang): void
    {
        if (in_array($lang, self::$languages, true)) {
            return;
        }

        // cargamos las traducciones desde los archivos json
        foreach ($this->getFolders() as $folder) {
            $fileName = $folder . '/' . $lang . '.json';
            if (false === file_exists($fileName)) {
                continue;
            }

            $data = file_get_contents($fileName);
            foreach (json_decode($data, true) as $code => $translation) {
                self::$translations[$code . '@' . $lang] = $translation;
            }
        }

        // añadimos el idioma a los cargados
        self::$languages[] = $lang;
    }
}
