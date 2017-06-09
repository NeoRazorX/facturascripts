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
use Symfony\Component\Translation\Loader\ArrayLoader;

/**
 * Description of fs_i18n
 *
 * @author Carlos García Gómez
 */
class fs_i18n {

    private static $_translator;

    public function __construct() {
        if (!isset(self::$_translator)) {
            self::$_translator = new Translator('es_ES');
            self::$_translator->addLoader('array', new ArrayLoader());
            self::$_translator->addResource('array', array(
                'Código' => 'Codig',
                'Controlador' => 'Controlador',
                'Controlador no encontrado' => 'Controlador no encontrat',
                'Error fatal' => 'Error fatal',
                'Mensaje' => 'Mesatge'
                    ), 'es_CA');
        }
    }
    
    public function trans($txt) {
        return self::$_translator->trans($txt);
    }

}
