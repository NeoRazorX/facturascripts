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

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\JsonFileLoader;

/**
 * Description of fs_i18n
 *
 * @author Carlos García Gómez
 */
class fs_i18n {

    private static $_fsFolder;
    private static $_fsLang;
    private static $_translator;

    public function __construct($folder = '', $lang = 'es_ES') {
        if (!isset(self::$_fsFolder)) {
            self::$_fsFolder = $folder;
            self::$_fsLang = $lang;

            self::$_translator = new Translator(self::$_fsLang);
            self::$_translator->addLoader('json', new JsonFileLoader());
            $this->locateFiles();
        }
    }

    public function trans($txt) {
        return self::$_translator->trans($txt);
    }

    public function locateFiles() {
        $pluginManager = new fs_plugin_manager();
        foreach ($pluginManager->enabledPlugins() as $pName) {
            if (file_exists(self::$_fsFolder . '/plugins/' . $pName . '/i18n/' . self::$_fsLang . '.json')) {
                self::$_translator->addResource('json', self::$_fsFolder . '/plugins/' . $pName . '/i18n/' . self::$_fsLang . '.json', self::$_fsLang);
            }
        }
        
        self::$_translator->addResource('json', self::$_fsFolder . '/i18n/' . self::$_fsLang . '.json', self::$_fsLang);
    }

}
