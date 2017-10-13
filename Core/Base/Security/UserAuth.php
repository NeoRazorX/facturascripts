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

use FacturaScripts\Core\Base;
use FacturaScripts\Core\Model\User;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
/**
 * Description of UserAuth
 *
 * @author Joe Nilson <joenilson at gmail.com>
 */
class UserAuth {
    public $ipFilter;
    public $miniLog;
    public function __construct() {
        $this->ipFilter = new Base\IPFilter(dirname(dirname(dirname(dirname(__FILE__)))));
        $this->miniLog = new Base\MiniLog();
    }
    /**
     * TODO
     *
     * @return User|false
     */
    public function userLogin(Request $request, Response $response)
    {
        $user0 = new User();
        $nick = $request->get('fsNick');
        
        if ($nick !== '') {
            $user = $user0->get($nick);
            if(!$this->userLoged($user, $request, $response)) {
                $this->verifyPassword($user, $request, $response);
                $this->verifyCookie($request);
                $this->verifyToken($user, $request);
            }
            return $user;
        }
        return false;
    }
    
    private function userLoged($user, Request $request, Response $response)
    {
        $ok = false;
        if($this->verifyCookie($request, $response)){
            $ok = true;
        }
        
        if($this->verifyToken($user, $request)){
            $ok = true;
        }
        return $ok;
    }
    
    private function verifyCookie(Request $request)
    {
        
        $cookieNick = $request->cookies->get('fsNick', '');
        if ($cookieNick !== '') {
            if(!$this->verifyCookieUser($cookieNick, $request)) {
                $this->miniLog->alert('login-user-not-found');
                return false;
            }
            return true;
        }
    }
    
    private function verifyCookieUser($cookieNick, $request)
    {
        $user_cookie = new User();
        $cookieUser = $user_cookie->get($cookieNick);
        if ($cookieUser) {
            if ($cookieUser->verifyLogkey($request->cookies->get('fsLogkey'))) {
                $this->miniLog->debug('Login OK (cookie). User: ' . $cookieNick);
                return $cookieUser;
            }
            $this->miniLog->alert('login-cookie-fail');
            return false;
        }
    }
    
    private function verifyPassword($user, Request $request, Response $response)
    {
        if($user){
            if ($user->verifyPassword($request->get('fsPassword'))) {
                $logKey = $user->newLogkey($request->getClientIp());
                $user->save();
                $response->headers->setCookie(new Cookie('fsNick', $user->nick, time() + FS_COOKIES_EXPIRE));
                $response->headers->setCookie(new Cookie('fsLogkey', $logKey, time() + FS_COOKIES_EXPIRE));
                $this->miniLog->debug('Login OK. User: ' . $user->nick);
            }
            $this->ipFilter->setAttempt($request->getClientIp());
            $this->miniLog->alert('login-password-fail');
        }
        $this->ipFilter->setAttempt($request->getClientIp());
        $this->miniLog->alert('login-user-not-found');
        return false;
    }
    
    private function verifyToken($userToken, Request $request)
    {
        $token = $request->headers->get('X-AUTH-TOKEN');
        if($token === null){
            return false;
        }else{
            return $this->verifyUserToken($userToken,$token);
        }
    }
    
    private function verifyUserToken($user,$token)
    {
        if($user) {
            return ($user->getApiKey() === $token);
        }
    }

    /**
     * TODO
     */
    public function userLogout(Response $response)
    {
        $response->headers->clearCookie('fsNick');
        $response->headers->clearCookie('fsLogkey');
        $this->miniLog->debug('Logout OK.');
    }
}
