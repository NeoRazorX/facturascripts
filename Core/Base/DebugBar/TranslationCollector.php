<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Francesc Pineda Segarra  <francesc.pineda.segarra@gmail.com>
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

namespace FacturaScripts\Core\Base\DebugBar;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use FacturaScripts\Core\Base\Translator;

/**
 * This class collects the translations
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * @source Based on: https://github.com/spiroski/laravel-debugbar-translations
 */
class TranslationCollector extends DataCollector implements Renderable, AssetProvider
{
    /**
     * Array containing the translations
     *
     * @var array
     */
    protected $translations;

    /**
     * Array containing the pending translations
     *
     * @var array
     */
    protected $pendingTranslations;

    /**
     * Translation engine
     *
     * @var Translator
     */
    protected static $i18n;

    /**
     * TranslationCollector constructor.
     *
     * @param Translator $i18n
     */
    public function __construct(&$i18n)
    {
        static::$i18n = $i18n;
    }

    /**
     * Returns the unique name of the collector
     *
     * @return string
     */
    public function getName()
    {
        return 'translations';
    }

    /**
     * Returns a hash where keys are control names and their values
     * an array of options as defined in {@see DebugBar\JavascriptRenderer::addControl()}
     *
     * @return array
     */
    public function getWidgets()
    {
        return [
            'translations' => [
                'icon' => 'language',
                'tooltip' => 'Translations',
                'widget' => 'PhpDebugBar.Widgets.TranslationsWidget',
                'map' => 'translations',
                'default' => '[]',
            ],
            'translations:badge' => [
                'map' => 'translations.nb_statements',
                'default' => 0,
            ],
        ];
    }

    /**
     * Returns the needed assets
     *
     * @return array
     */
    public function getAssets()
    {
        $basePath = '../../../../../../';

        return [
            'css' => $basePath . 'Core/Assets/CSS/phpdebugbar.custom-widget.css',
            'js' => $basePath . 'Core/Assets/JS/phpdebugbar.custom-widget.js',
        ];
    }

    /**
     * Add a translation key to the collector
     */
    private function addTranslations()
    {
        foreach (static::$i18n->getUsedStrings() as $key => $value) {
            $this->translations[] = [
                'key' => $key,
                'value' => $value
            ];
        }

        $this->pendingTranslations = [];
        if (static::$i18n->getLangCode() !== static::$i18n::DEFAULT_LANG) {
            foreach (static::$i18n->getMissingMessages(static::$i18n->getLangCode()) as $key => $value) {
                $this->pendingTranslations[] = [ 'key' => $key, 'value' => $value];
            }
        }
    }

    /**
     * Called by the DebugBar when data needs to be collected
     *
     * @return array Collected data
     */
    public function collect()
    {
        $this->addTranslations();
        return [
            'nb_statements' => count($this->translations),
            'nb_failed_statements' => count($this->pendingTranslations),
            'translations' => $this->translations,
            'pending_translations' => $this->pendingTranslations,
        ];
    }
}
