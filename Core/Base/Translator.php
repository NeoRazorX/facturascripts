<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2023 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Tools;

/**
 * The Translator class manage all translations methods required for internationalization.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 * @deprecated since FacturaScripts 2023.06. Use FacturaScripts\Core\Translator instead.
 */
class Translator
{
    const FALLBACK_LANG = 'es_ES';

    /**
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
     * @var mixed
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
    }

    /**
     * Translate the text into the selected language.
     *
     * @param string $langCode
     * @param string $txt
     * @param array $parameters
     *
     * @return string
     */
    public function customTrans(string $langCode, string $txt, array $parameters = []): string
    {
        return $txt;
    }

    /**
     * Returns an array with the languages with available translations.
     *
     * @return array
     */
    public function getAvailableLanguages(): array
    {
        return [];
    }

    /**
     * @return string
     */
    private function getDefaultLang(): string
    {
        return self::$defaultLang ?? Tools::config('lang', self::FALLBACK_LANG);
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

    public static function reload(): void
    {
    }

    /**
     * Translate the text into the default language.
     *
     * @param ?string $txt
     * @param array $parameters
     *
     * @return string
     */
    public function trans(?string $txt, array $parameters = []): string
    {
        return empty($txt) ? '' : $this->customTrans($this->currentLang, $txt, $parameters);
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
}
