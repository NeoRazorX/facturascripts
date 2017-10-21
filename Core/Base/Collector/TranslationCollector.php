<?php
/**
 * File TranslatorCollector.php | TranslatorCollector.php
 *
 * @package     facturascripts
 * @subpackage  facturascripts
 * @autor       Francesc Pineda Segarra francesc.pineda.segarra@gmail.com
 * @copyright   Copyright (c) 2017
 */

namespace FacturaScripts\Core\Base\Collector;

use DebugBar\DataCollector\AssetProvider;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;

/**
 * Clase para "recopilar" las traducciones.
 *
 * @author Francesc Pineda Segarra <francesc.pineda.segarra@gmail.com>
 * @source Based on: https://github.com/spiroski/laravel-debugbar-translations
 */
class TranslationCollector extends DataCollector implements Renderable, AssetProvider
{
    /**
     * Array con las traducciones
     *
     * @var array
     */
    protected $translations;

    /**
     * TranslationCollector constructor.
     *
     * @param $translations
     */
    public function __construct($translations)
    {
        $this->addTranslations($translations);
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
     * Devuelve los assets necesarios
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
     *
     * @param array $translations
     */
    private function addTranslations($translations)
    {
        foreach ($translations as $key => $value) {
            $this->translations[] = array(
                'key' => $key,
                'value' => $value
            );
        }
    }

    /**
     * Called by the DebugBar when data needs to be collected
     *
     * @return array Collected data
     */
    public function collect()
    {
        return [
            'nb_statements' => count($this->translations),
            'translations' => $this->translations,
        ];
    }
}
