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

use FacturaScripts\Core\Base\Security\Tools;

use FacturaScripts\Core\Model\ApiKey;

/**
 * Description of ApiAuth
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class ApiAuth {
    public $fsServerKey;
    public $fsToken;
    public $fsCsrf;
    public $fsAlgorithm;
    public $fsOrigin;
    public $request;
    public $response;

    public function __construct($config) {
        $this->response = $config['response'];
        $this->request = $config['request'];
    }

    public function checkCredentials($key)
    {
        // Revisamos si la apikey enviada existe
        // y si existe si esta habilitada
        // si existe y está habilitada creamos un token de autenticación
        // de la sesion
        // @todo crear un model ApiKeysLog para controlar el uso de cada ApiKey
        $apiKeys = new ApiKey();
        $apikey = $apiKeys->getAPiKey($key);
        $token = null;
        if($apikey && $apikey->enabled){
            $token = $apikey->apikey;
        }
        return $token;
    }

    /**
     * Se genera un token para agregar a la cabecera de todas las llamadas
     * @return string
     */
    private function createAuthenticatedToken()
    {
        return Tools::Token(64);
    }

    /**
     * Se Verifica siempre que haya un auth token
     *
     * @return string
     */
    public function getCredentials()
    {
        $token = $this->checkCredentials($this->request->headers->get('X-AUTH-TOKEN'));
        if(!$token){
            $token = $this->checkCredentials($this->request->get('apikey'));
        }
        return $token;
    }

    /**
     * Si existe el token se envia como respuesta al AppApi
     * @param type $token
     * @return array
     */
    public function onAuthenticationSuccess($token)
    {
        // on success, let the request continue
        $data = array(
            // you might translate this message
            'token' => $token
        );
        return ['success'=>$data,'error'=>false];
    }

    public function onAuthenticationFailure()
    {
        $data = array(
            'error' => 'AUTH-REQUIRED'
        );
        return ['success'=>false,'error'=>$data];
    }


    /**
     * Iniciamos el proceso de revisión de la apikey enviada por el usuario
     * @return array|boolean
     */
    public function startAuth()
    {
        $token = $this->getCredentials();
        if($token){
            return $this->onAuthenticationSuccess($token);
        }
        return $this->onAuthenticationFailure();
    }

}
