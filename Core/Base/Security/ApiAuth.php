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
use FacturaScripts\Core\Base\Security\UserAuth;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;


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
        extract($config);
        $this->response = $response;
        $this->request = $request;
        $this->fsServerKey = $server_key;
    }
    
    public function checkCredentials() 
    {
        // check credentials - e.g. make sure the password is valid
        // no credential check is needed in this case
        // return true to cause authentication success
        $userAuth = new UserAuth();
        $user = $userAuth->userLogin($this->request, $this->response);
        $token = null;
        if($user){
            $token = $this->createAuthenticatedToken();
            $user->setApiKey($token);
            $user->save();
        }
        return $token;
    }
    
    private function createAuthenticatedToken()
    {
        return Tools::Token();
    }    
    
    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser(). Returning null will cause this authenticator
     * to be skipped.
     */
    public function getCredentials() 
    {
        if (!$token = $this->request->headers->get('X-AUTH-TOKEN')) {
            // No token?
            $token = $this->checkCredentials();
        }
        
        // What you return here will be passed to getUser() as $credentials
        return $token;
    }

    public function getUser() 
    {
        $apikey = $this->request->headers->get('X-AUTH-TOKEN');
        if (null === $apikey) {
            return;
        }
        // if null, authentication will fail
        // if a User object, checkCredentials() is called
        return $this->request->headers->get('fsNick');
    }

    public function onAuthenticationSuccess($token) 
    {
        // on success, let the request continue
        $data = array(
            // you might translate this message
            'token' => $token
        );
        return array($data,'');
    }

    public function onAuthenticationFailure($token) 
    {
        $data = array(
            'message' => 'AUTH-REQUIRED',
            'token' => $token
        );
        return array('',$data);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null) 
    {
        $data = array(
            // you might translate this message
            'message' => 'AUTH-REQUIRED',
            'token' => $token
        );
        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }
    
    public function startAuth()
    {
        $token = $this->getCredentials();
        if($token){
            return $this->onAuthenticationSuccess($token);
        }
        return $this->onAuthenticationFailure($token);
    }

}
