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

use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Translator as SymfonyTranslator;

/**
 * The Translator class manage all translations methods required for internationalization.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Translator
{

    const FALLBACK_LANG = 'es_ES';

    /**
     *
     * @var string
     */
    private $currentLang;

    /**
     * Default language.
     *
     * @var string
     */
    private static $defaultLang;

    /**
     * Loaded languages.
     *
     * @var array
     */
    private static $languages = [];

    /**
     * List of strings without translation.
     *
     * @var array
     */
    private static $missingStrings = [];

    /**
     * The Symfony translator.
     *
     * @var SymfonyTranslator
     */
    private static $translator;

    /**
     * List of used strings.
     *
     * @var array
     */
    private static $usedStrings = [];

    /**
     * Translator's constructor.
     *
     * @param string $langCode
     */
    public function __construct(string $langCode = '')
    {
        $this->currentLang = empty($langCode) ? $this->getDefaultLang() : $langCode;
        if (self::$translator === null) {
            self::$translator = new symfonyTranslator($this->currentLang);
            self::$translator->addLoader('json', new JsonFileLoader());
        }
    }

    /**
     * Translate the text into the default language.
     *
     * @param string $txt
     * @param array  $parameters
     *
     * @return string
     */
    public function trans($txt, $parameters = []): string
    {
        return empty($txt) ? '' : $this->customTrans($this->currentLang, $txt, $parameters);
    }

    /**
     * Translate the text into the selected language.
     *
     * @param string $langCode
     * @param string $txt
     * @param array  $parameters
     *
     * @return string
     */
    public function customTrans(string $langCode, string $txt, array $parameters = []): string
    {
        if (!in_array($langCode, self::$languages)) {
            $this->locateFiles($langCode);
        }

        $transKey = $this->getTransKey($txt);
        $catalogue = self::$translator->getCatalogue($langCode);
        if ($catalogue->has($transKey)) {
            self::$usedStrings[$transKey] = $catalogue->get($transKey);
            return self::$translator->trans($transKey, $parameters, null, $langCode);
        }

        self::$missingStrings[$transKey] = $transKey;
        if ($langCode === self::FALLBACK_LANG) {
            return $transKey;
        }

        return $this->customTrans(self::FALLBACK_LANG, $transKey, $parameters);
    }

    /**
     * Returns an array with the languages with available translations.
     *
     * @return array
     */
    public function getAvailableLanguages(): array
    {
        $languages = [];
        $dir = \FS_FOLDER . '/Core/Translation';
        foreach (scandir($dir, SCANDIR_SORT_ASCENDING) as $fileName) {
            if ($fileName !== '.' && $fileName !== '..' && !is_dir($fileName) && substr($fileName, -5) === '.json') {
                $key = substr($fileName, 0, -5);
                $languages[$key] = $this->trans('languages-' . substr($fileName, 0, -5));
            }
        }

        return $languages;
    }

    /**
     * 
     * @return string
     */
    private function getDefaultLang(): string
    {
        return isset(self::$defaultLang) ? self::$defaultLang : \FS_LANG;
    }

    /**
     * Returns the language code in use.
     *
     * @return string
     */
    public function getLang(): string
    {
        return $this->currentLang;
    }

    /**
     * Returns the missing strings.
     *
     * @return array
     */
    public function getMissingStrings(): array
    {
        return self::$missingStrings;
    }

    /**
     * 
     * @param string $txt
     *
     * @return string
     */
    private function getTransKey(string $txt): string
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

        return isset($specialKeys[$txt]) ? $specialKeys[$txt] : $txt;
    }

    /**
     * Returns the strings used.
     *
     * @return array
     */
    public function getUsedStrings(): array
    {
        return self::$usedStrings;
    }

    /**
     * 
     * @param string $langCode
     */
    public function setDefaultLang(string $langCode)
    {
        self::$defaultLang = $this->findLang($langCode);
    }

    /**
     * Sets the language code in use.
     * 
     * @param string $langCode
     */
    public function setLang(string $langCode)
    {
        $this->currentLang = $this->findLang($langCode);
    }

    /**
     * 
     * @param string $langCode
     *
     * @return string
     */
    private function findLang(string $langCode): string
    {
        // First match is with default lang? (Avoid match with variants)
        if (0 === strpos($this->getDefaultLang(), $langCode)) {
            return $this->getDefaultLang();
        }

        // If not, check with all available languages
        $finalKey = null;
        foreach (array_keys($this->getAvailableLanguages()) as $key) {
            if ($key === $langCode) {
                return $key;
            }

            if ($finalKey === null && 0 === strpos($key, $langCode)) {
                $finalKey = $key;
            }
        }

        return $finalKey ?? $this->getDefaultLang();
    }

    /**
     * Load the translation files following the priority system of FacturaScripts.
     * In this case, the translator must be provided with the routes in reverse order.
     *
     * @param string $langCode
     */
    private function locateFiles(string $langCode)
    {
        self::$languages[] = $langCode;

        $file = \FS_FOLDER . '/Core/Translation/' . $langCode . '.json';
        if (file_exists($file)) {
            self::$translator->addResource('json', $file, $langCode);
        }

        $pluginManager = new PluginManager();
        foreach ($pluginManager->enabledPlugins() as $pluginName) {
            $file2 = \FS_FOLDER . '/Plugins/' . $pluginName . '/Translation/' . $langCode . '.json';
            if (file_exists($file2)) {
                self::$translator->addResource('json', $file2, $langCode);
            }
        }
    }
}
