<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  carlos@facturascripts.com
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

use Symfony\Component\Translation\Exception\InvalidArgumentException as TranslationInvalidArgumentException;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Translator as symfonyTranslator;

/**
 * Description of Translator
 *
 * @author Carlos García Gómez
 */
class Translator
{
    /**
     * Carpeta de trabajo de FacturaScripts.
     * @var string
     */
    private static $folder;

    /**
     * Idioma por defecto.
     * @var string
     */
    private static $lang;

    /**
     * El traductor de symfony.
     * @var symfonyTranslator
     */
    private static $translator;

    /**
     * Translator constructor.
     * @param string $folder
     * @param string $lang
     * @throws TranslationInvalidArgumentException
     */
    public function __construct($folder = '', $lang = 'es_ES')
    {
        if (self::$folder === null) {
            self::$folder = $folder;
            self::$lang = $lang;

            self::$translator = new symfonyTranslator(self::$lang);
            self::$translator->addLoader('json', new JsonFileLoader());
            $this->locateFiles();
        }
    }

    /**
     * Traduce el texto al idioma predeterminado.
     * @param string $txt
     * @param array $parameters
     * @return string
     * @throws TranslationInvalidArgumentException
     */
    public function trans($txt, array $parameters = [])
    {
        return self::$translator->trans($txt, $parameters);
    }

    /**
     * Carga los archivos de traducción siguiendo el sistema de prioridades
     * de FacturaScripts. En esta caso hay que proporcionar al traductor las rutas
     * en orden inverso.
     * @throws TranslationInvalidArgumentException
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
}
