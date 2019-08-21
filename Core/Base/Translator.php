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
use Symfony\Component\Translation\Translator as symfonyTranslator;

/**
 * The Translator class manage all translations methods required for internationalization.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Translator
{

    const FALLBACK_LANG = 'en_EN';

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
    private static $languages;

    /**
     * List of strings without translation.
     *
     * @var array
     */
    private static $missingStrings;

    /**
     * The Symfony translator.
     *
     * @var symfonyTranslator
     */
    private static $translator;

    /**
     * List of used strings.
     *
     * @var array
     */
    private static $usedStrings;

    /**
     * Translator's constructor.
     *
     * @param string $lang
     */
    public function __construct($lang = \FS_LANG)
    {
        if (self::$translator === null) {
            self::$defaultLang = $lang;
            self::$missingStrings = [];
            self::$usedStrings = [];
            self::$translator = new symfonyTranslator($lang);

            self::$translator->addLoader('json', new JsonFileLoader());
            $this->locateFiles($lang);
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
    public function trans($txt, array $parameters = [])
    {
        return empty($txt) ? '' : $this->customTrans(self::$defaultLang, $txt, $parameters);
    }

    /**
     * Translate the text into the selected language.
     *
     * @param string $lang
     * @param string $txt
     * @param array  $parameters
     *
     * @return string
     */
    public function customTrans($lang, $txt, array $parameters = [])
    {
        if (!in_array($lang, self::$languages)) {
            $this->locateFiles($lang);
        }

        $catalogue = self::$translator->getCatalogue($lang);
        if ($catalogue->has($txt)) {
            self::$usedStrings[$txt] = $catalogue->get($txt);
            return self::$translator->trans($txt, $parameters, null, $lang);
        }

        self::$missingStrings[$txt] = $txt;
        if ($lang === self::FALLBACK_LANG) {
            return $txt;
        }

        return $this->customTrans(self::FALLBACK_LANG, $txt, $parameters);
    }

    /**
     * Load the translation files following the priority system of FacturaScripts.
     * In this case, the translator must be provided with the routes in reverse order.
     *
     * @param string $lang
     */
    private function locateFiles($lang)
    {
        self::$languages[] = $lang;

        $file = \FS_FOLDER . '/Core/Translation/' . $lang . '.json';
        self::$translator->addResource('json', $file, $lang);

        $pluginManager = new PluginManager();
        foreach ($pluginManager->enabledPlugins() as $pluginName) {
            $file = \FS_FOLDER . '/Plugins/' . $pluginName . '/Translation/' . $lang . '.json';
            if (file_exists($file)) {
                self::$translator->addResource('json', $file, $lang);
            }
        }
    }

    /**
     * Returns an array with the languages with available translations.
     *
     * @return array
     */
    public function getAvailableLanguages()
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
     * Returns the language code in use.
     *
     * @return string
     */
    public function getLangCode()
    {
        return self::$defaultLang;
    }

    /**
     * Sets the language code in use.
     * 
     * @param string $lang
     */
    public function setLangCode($lang)
    {
        self::$defaultLang = $this->firstMatch($lang);
    }

    /**
     * Returns the missing strings.
     *
     * @return array
     */
    public function getMissingStrings()
    {
        return self::$missingStrings;
    }

    /**
     * Returns the strings used.
     *
     * @return array
     */
    public function getUsedStrings()
    {
        return self::$usedStrings;
    }

    /**
     * Return first exact match, or first partial match with language key identifier,
     * or it not match founded, return default language.
     *
     * @param string $langCode
     *
     * @return string
     */
    private function firstMatch(string $langCode): string
    {
        // First match is with default lang? (Avoid match with variants)
        if (0 === strpos(\FS_LANG, $langCode)) {
            return \FS_LANG;
        }

        // If not, check with all available languages
        $finalKey = null;
        foreach ($this->getAvailableLanguages() as $key => $language) {
            if ($key === $langCode) {
                return $key;
            }

            if ($finalKey === null && 0 === strpos($key, $langCode)) {
                $finalKey = $key;
            }
        }

        return $finalKey ?? \FS_LANG;
    }
}
