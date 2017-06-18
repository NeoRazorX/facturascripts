<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

use Symfony\Component\Translation\Translator as symfonyTranslator;
use Symfony\Component\Translation\Loader\JsonFileLoader;

/**
 * Description of Translator
 *
 * @author Carlos García Gómez
 */
class Translator {

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
     * @var Translator 
     */
    private static $translator;

    public function __construct($folder = '', $lang = 'es_ES') {
        if (!isset(self::$folder)) {
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
     * @return string
     */
    public function trans($txt) {
        return self::$translator->trans($txt);
    }

    /**
     * Carga los archivos de traducción siguiendo el sistema de prioridades
     * de FacturaScripts. En esta caso hay que proporcionar al traductor las rutas
     * en orden inverso.
     */
    private function locateFiles() {
        self::$translator->addResource('json', self::$folder . '/Core/Translation/' . self::$lang . '.json', self::$lang);
        
        $pluginManager = new PluginManager(self::$folder);
        foreach ($pluginManager->enabledPlugins() as $pluginName) {
            if (file_exists(self::$folder . '/Plugins/' . $pluginName . '/Translation/' . self::$lang . '.json')) {
                self::$translator->addResource('json', self::$folder . '/Plugins/' . $pluginName . '/Translation/' . self::$lang . '.json', self::$lang);
            }
        }
    }

}
