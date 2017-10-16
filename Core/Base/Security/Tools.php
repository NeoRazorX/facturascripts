<?php

/*
 * Copyright (C) 2017 Joe Nilson <joenilson at gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Base\Security;

/**
 * Herramientas para generar un token y un codigo salt para las claves
 * de usuario en caso estas se necesiten
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class Tools {
    
    public function __construct() {
        
    }
    
    /**
     * Función para generar un token por defecto de 32bits, se puede cambiar
     * @param int $length
     * @return integer
     */
    public static function Token($length = 32) {
        if (!isset($length) || intval($length) <= 8) {
            $length = 32;
        }
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        }
        if (function_exists('mcrypt_create_iv')) {
            return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        }
    }

    /**
     * Función para devolver un salt para utilizar para los passwords de 
     * los usuarios en caso de que se necesite reforzar la seguridad, por defecto
     * genera una cadena de 32 caracteres de longitud
     * @return string
     */
    public function Salt() {
        return substr(strtr(base64_encode(hex2bin(Token(32))), '+', '.'), 0, 44);
    }

}
