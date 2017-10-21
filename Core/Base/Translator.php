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

use DebugBar\StandardDebugBar;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Translator as symfonyTranslator;

/**
 * Description of Translator
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Translator
{
    /**
     * Carpeta de trabajo de FacturaScripts.
     *
     * @var string
     */
    private static $folder;

    /**
     * Idioma por defecto.
     *
     * @var string
     */
    private static $lang;

    /**
     * El traductor de symfony.
     *
     * @var symfonyTranslator
     */
    private static $translator;

    /**
     * La barra de depuración.
     *
     * @var StandardDebugBar
     */
    private static $debugBar;

    /**
     * Gestor de log de la app.
     *
     * @var MiniLog
     */
    private static $miniLog;

    /**
     * Constructor del traductor
     * Por defecto se usará y definirá en_EN si no está definido en config.php.
     *
     * @param string $folder
     * @param string $lang
     */
    public function __construct($folder = '', $lang = 'en_EN')
    {
        if (self::$folder === null) {
            self::$folder = $folder;
            if (!\array_key_exists('FS_LANG', \get_defined_constants())) {
                self::$lang = $lang;
                define('FS_LANG', self::$lang);
            } else {
                self::$lang = FS_LANG;
            }

            self::$translator = new symfonyTranslator(self::$lang);
            self::$translator->addLoader('json', new JsonFileLoader());
            $this->locateFiles();
        }
        if (self::$miniLog === null) {
            self::$miniLog = new MiniLog();
        }
    }

    /**
     * Traduce el texto al idioma predeterminado.
     *
     * @param string $txt
     * @param array $parameters
     *
     * @return string
     */
    public function trans($txt, array $parameters = [])
    {
        if (self::$debugBar !== null) {
            $catalogue = self::$translator->getCatalogue(self::$lang);
            self::$debugBar['translations']->addTranslation($txt, $catalogue->get($txt, 'messages'));
        }
        if (self::$debugBar === null) {
            self::$miniLog->debug('La string \'' . $txt . '\' no se ha registrado en las traducciones en uso.');
            //self::$miniLog->debug($this->trans('translation-not-registered', [$txt]));
        }
        return self::$translator->trans($txt, $parameters);
    }

    /**
     * Carga los archivos de traducción siguiendo el sistema de prioridades
     * de FacturaScripts. En este caso hay que proporcionar al traductor las rutas
     * en orden inverso.
     */
    private function locateFiles()
    {
        $file = self::$folder . '/Core/Translation/' . self::$lang . '.json';
        self::$translator->addResource('json', $file, self::$lang);

        $pluginManager = new PluginManager(self::$folder);
        foreach ($pluginManager->enabledPlugins() as $pluginName) {
            $file = self::$folder . '/Plugins/' . $pluginName . '/Translation/' . self::$lang . '.json';
            if (file_exists($file)) {
                self::$translator->addResource('json', $file, self::$lang);
            }
        }
    }

    /**
     * Devuelve el código de idioma en uso
     *
     * @return string
     */
    public function getLangCode()
    {
        return self::$lang;
    }

    /**
     * Asigna la debugBar
     *
     * @param StandardDebugBar $debugBar
     */
    public function setDebugBar(&$debugBar)
    {
        if (self::$debugBar === null) {
            self::$debugBar = $debugBar;
        }
    }
}
