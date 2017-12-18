<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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

use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Translator as symfonyTranslator;

/**
 * The Translator class manage all translations methods required for internationalization.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Translator
{
    const DEFAULT_LANG = 'en_EN';

    /**
     * Language by default.
     *
     * @var string
     */
    private static $lang;

    /**
     * The Symfony translator.
     *
     * @var symfonyTranslator
     */
    private static $translator;

    /**
     * List of strings used.
     *
     * @var array
     */
    private static $usedStrings;

    /**
     * Translator's constructor.
     * By default it will be used and it will define en_EN if it is not defined in config.php.
     *
     * @param string $lang
     */
    public function __construct($lang = FS_LANG)
    {
        if (self::$translator === null) {
            self::$lang = $lang;
            self::$usedStrings = [];
            self::$translator = new symfonyTranslator(self::$lang);

            self::$translator->addLoader('json', new JsonFileLoader());
            $this->locateFiles();
        }
    }

    /**
     * Translate the text into the default language.
     *
     * @param string $txt
     * @param array $parameters
     *
     * @return string
     */
    public function trans($txt, array $parameters = [])
    {
        $catalogue = self::$translator->getCatalogue(self::$lang);
        self::$usedStrings[$txt] = $catalogue->get($txt);

        return self::$translator->trans($txt, $parameters);
    }

    /**
     * Load the translation files following the priority system of FacturaScripts.
     * In this case, the translator must be provided with the routes in reverse order.
     * Load also fallback locales to languages different than default, to avoid empty strings.
     */
    private function locateFiles()
    {
        $fileFallback = FS_FOLDER . '/Core/Translation/' . self::DEFAULT_LANG . '.json';
        $this->addResourceFallbackLang($fileFallback);
        $file = FS_FOLDER . '/Core/Translation/' . self::$lang . '.json';
        self::$translator->addResource('json', $file, self::$lang);

        $pluginManager = new PluginManager();
        foreach ($pluginManager->enabledPlugins() as $pluginName) {
            $fileFallback = FS_FOLDER . '/Plugins/' . $pluginName . '/Translation/' . self::DEFAULT_LANG . '.json';
            $this->addResourceFallbackLang($fileFallback);
            $file = FS_FOLDER . '/Plugins/' . $pluginName . '/Translation/' . self::$lang . '.json';
            if (file_exists($file)) {
                self::$translator->addResource('json', $file, self::$lang);
            }
        }
    }

    /**
     * Add core/plugin language fallback if needed (when different than default lang).
     * Combine user lang and default lang, to add all missing translations strings with default lang.
     * If lang file exists on Core, can be setted as fallback locales.
     */
    private function addResourceFallbackLang($fileFallback)
    {
        if (self::$lang !== self::DEFAULT_LANG) {
            if (strpos($fileFallback, FS_FOLDER . '/Core/Translation/') === 0) {
                self::$translator->setFallbackLocales([self::DEFAULT_LANG]);
            }
            self::$translator->addResource('json', $fileFallback, self::DEFAULT_LANG);
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
        $dir = FS_FOLDER . '/Core/Translation';
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
        return self::$lang;
    }

    /**
     * Returns the strings used.
     *
     * @return array
     */
    public function getUsedStrings()
    {
        ksort(self::$usedStrings);
        return self::$usedStrings;
    }

    /**
     * Returns the full list of messages for the language
     *
     * @param string $lang
     *
     * @return array
     */
    public function getMessages($lang = FS_LANG)
    {
        $catalogue = self::$translator->getCatalogue($lang);
        $messages = $catalogue->all();
        while ($catalogue = $catalogue->getFallbackCatalogue()) {
            $messages = array_replace_recursive($catalogue->all(), $messages);
        }
        ksort($messages['messages']);
        return $messages['messages'];
    }

    /**
     * Returns the full list of messages for the language
     *
     * @param string $lang
     *
     * @return array
     */
    public function getOriginalMessages($lang = FS_LANG)
    {
        $catalogue = self::$translator->getCatalogue($lang);
        $messages = $catalogue->all();
        ksort($messages['messages']);
        return $messages['messages'];
    }

    /**
     * Returns the full list of missing messages for the language.
     * If is the default language, return an empty array.
     *
     * @param string $lang
     *
     * @return array
     */
    public function getMissingMessages($lang)
    {
        if ($lang === self::DEFAULT_LANG) {
            return [];
        }
        $userMessages = $this->getOriginalMessages($lang);

        $systemCatalogue = self::$translator->getCatalogue(self::DEFAULT_LANG);
        $systemMessages = $systemCatalogue->all();

        $result = [];
        foreach ($systemMessages['messages'] as $pos => $msg) {
            if (!isset($userMessages[$pos])) {
                $result[$pos] = $msg;
            }
        }

        ksort($result);
        return $result;
    }
}
