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
     */
    private function locateFiles()
    {
        $file = FS_FOLDER . '/Core/Translation/' . self::$lang . '.json';
        self::$translator->addResource('json', $file, self::$lang);

        $pluginManager = new PluginManager();
        foreach ($pluginManager->enabledPlugins() as $pluginName) {
            $file = FS_FOLDER . '/Plugins/' . $pluginName . '/Translation/' . self::$lang . '.json';
            if (file_exists($file)) {
                self::$translator->addResource('json', $file, self::$lang);
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
        return self::$usedStrings;
    }
}
