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

namespace FacturaScripts\Base;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\JsonFileLoader;

/**
 * Description of fs_i18n
 *
 * @author Carlos García Gómez
 */
class fs_i18n {

    /**
     * Carpeta de trabajo de FacturaScripts.
     * @var string 
     */
    private static $fsFolder;
    
    /**
     * Idioma por defecto.
     * @var string 
     */
    private static $fsLang;
    
    /**
     * El traductor de symfony.
     * @var Translator 
     */
    private static $translator;

    public function __construct($folder = '', $lang = 'es_ES') {
        if (!isset(self::$fsFolder)) {
            self::$fsFolder = $folder;
            self::$fsLang = $lang;

            self::$translator = new Translator(self::$fsLang);
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
     * de FacturaScripts.
     */
    private function locateFiles() {
        $pluginManager = new fs_plugin_manager();
        foreach ($pluginManager->enabledPlugins() as $pName) {
            if (file_exists(self::$fsFolder . '/plugins/' . $pName . '/i18n/' . self::$fsLang . '.json')) {
                self::$translator->addResource('json', self::$fsFolder . '/plugins/' . $pName . '/i18n/' . self::$fsLang . '.json', self::$fsLang);
            }
        }
        
        self::$translator->addResource('json', self::$fsFolder . '/i18n/' . self::$fsLang . '.json', self::$fsLang);
    }

}
