<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

/**
 * Description of fs_i18n
 *
 * @author carlos
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
