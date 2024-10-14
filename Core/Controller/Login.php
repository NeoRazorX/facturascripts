<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Core\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Lib\MultiRequestProtection;
use FacturaScripts\Core\Lib\TwoFactorManager;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\User;

class Login implements ControllerInterface
{
    const INCIDENT_EXPIRATION_TIME = 600;
    const IP_LIST = 'login-ip-list';
    const MAX_INCIDENT_COUNT = 6;
    const USER_LIST = 'login-user-list';

    /** @var Empresa */
    public $empresa;

    /** @var string */
    public $title = 'Login';

    /** @var boolean */
    private $two_factor_view = false;


    public function __construct(string $className, string $url = '')
    {
    }

    public function clearIncidents(): void
    {
        Cache::delete(self::IP_LIST);
        Cache::delete(self::USER_LIST);
    }

    public function getPageData(): array
    {
        return [];
    }

    public function run(): void
    {
        $this->empresa = Empresas::default();
        $this->title = $this->empresa->nombrecorto;

        $request = Request::createFromGlobals();
        $action = $request->request->get('action', $request->query->get('action', ''));

        switch ($action) {
            case 'change-password':
                $this->changePasswordAction($request);
                break;

            case 'login':
                $this->loginAction($request);
                break;

            case 'logout':
                $this->logoutAction($request);
                break;

            case 'valid-totp':
                $this->validCodeAction($request);
                break;
        }

        if ($this->two_factor_view) {
            echo Html::render('Login/TwoFactor.html.twig', [
                'controllerName' => 'Login',
                'debugBarRender' => false,
                'fsc' => $this,
                'template' => 'Login/TwoFactor.html.twig',
            ]);
            return;
        }

        echo Html::render('Login/Login.html.twig', [
            'controllerName' => 'Login',
            'debugBarRender' => false,
            'fsc' => $this,
            'template' => 'Login/Login.html.twig',
        ]);
    }

    public function saveIncident(string $ip, string $user = '', ?int $time = null): void
    {
        // add the current IP to the list
        $ipList = $this->getIpList();
        $ipList[] = [
            'ip' => $ip,
            'time' => ($time ?? time())
        ];

        // save the list in cache
        Cache::set(self::IP_LIST, $ipList);

        // if the user is not empty, save the incident
        if (empty($user)) {
            return;
        }

        // add the current user to the list
        $userList = $this->getUserList();
        $userList[] = [
            'user' => $user,
            'time' => ($time ?? time())
        ];

        // save the list in cache
        Cache::set(self::USER_LIST, $userList);
    }

    public function userHasManyIncidents(string $ip, string $username = ''): bool
    {
        // get ip count on the list
        $ipCount = 0;
        foreach ($this->getIpList() as $item) {
            if ($item['ip'] === $ip) {
                $ipCount++;
            }
        }
        if ($ipCount >= self::MAX_INCIDENT_COUNT) {
            return true;
        }

        // get user count on the list
        $userCount = 0;
        foreach ($this->getUserList() as $item) {
            if ($item['user'] === $username) {
                $userCount++;
            }
        }
        return $userCount >= self::MAX_INCIDENT_COUNT;
    }

    private function changePasswordAction(Request $request): void
    {
        if (false === $this->validateFormToken($request)) {
            return;
        }

        $username = $request->request->get('fsNewUserPasswd');
        if ($this->userHasManyIncidents(Session::getClientIp(), $username)) {
            Tools::log()->warning('ip-banned');
            return;
        }

        $dbPassword = $request->request->get('fsDbPasswd');
        if ($dbPassword !== Tools::config('db_pass')) {
            Tools::log()->warning('login-invalid-db-password');
            $this->saveIncident(Session::getClientIp(), $username);
            return;
        }

        $password = $request->request->get('fsNewPasswd');
        $password2 = $request->request->get('fsNewPasswd2');
        if (empty($username) || empty($password) || empty($password2)) {
            Tools::log()->warning('login-empty-fields');
            return;
        }

        if ($password !== $password2) {
            Tools::log()->warning('different-passwords', ['%userNick%' => $username]);
            return;
        }

        $user = new User();
        if (false === $user->loadFromCode($username)) {
            Tools::log()->warning('login-user-not-found');
            $this->saveIncident(Session::getClientIp(), $username);
            return;
        }

        if (false === $user->enabled) {
            Tools::log()->warning('login-user-disabled');
            return;
        }

        $user->setPassword($password);
        if (false === $user->save()) {
            Tools::log()->warning('login-user-not-saved');
            $this->saveIncident(Session::getClientIp(), $username);
            return;
        }

        Tools::log()->notice('login-password-changed');
    }

    private function validateFormToken(Request $request): bool
    {
        $multiRequestProtection = new MultiRequestProtection();

        // si el usuario está autenticado, añadimos su nick a la semilla
        $cookieNick = $request->cookies->get('fsNick', '');
        if ($cookieNick) {
            $multiRequestProtection->addSeed($cookieNick);
        }

        // comprobamos el token
        $urlToken = $request->query->get('multireqtoken', '');
        $token = $request->request->get('multireqtoken', $urlToken);
        if (empty($token) || false === $multiRequestProtection->validate($token)) {
            Tools::log()->warning('invalid-request');
            return false;
        }

        // comprobamos que el token no se haya usado antes
        if ($multiRequestProtection->tokenExist($token)) {
            Tools::log()->warning('duplicated-request');
            return false;
        }

        return true;
    }

    private function getIpList(): array
    {
        $ipList = Cache::get(self::IP_LIST);
        if (false === is_array($ipList)) {
            return [];
        }

        // remove expired items
        $newList = [];
        foreach ($ipList as $item) {
            if (time() - $item['time'] < self::INCIDENT_EXPIRATION_TIME) {
                $newList[] = $item;
            }
        }
        return $newList;
    }

    private function getUserList(): array
    {
        $userList = Cache::get(self::USER_LIST);
        if (false === is_array($userList)) {
            return [];
        }

        // remove expired items
        $newList = [];
        foreach ($userList as $item) {
            if (time() - $item['time'] < self::INCIDENT_EXPIRATION_TIME) {
                $newList[] = $item;
            }
        }
        return $newList;
    }

    private function loginAction(Request $request): void
    {
        if (false === $this->validateFormToken($request)) {
            return;
        }

        $userName = $request->request->get('fsNick');
        $password = $request->request->get('fsPassword');
        if (empty($userName) || empty($password)) {
            Tools::log()->warning('login-error-empty-fields');
            return;
        }

        // check if the user is in the incident list
        if ($this->userHasManyIncidents(Session::getClientIp(), $userName)) {
            Tools::log()->warning('ip-banned');
            return;
        }

        $user = new User();
        if (false === $user->loadFromCode($userName)) {
            Tools::log()->warning('login-user-not-found', ['%nick%' => htmlspecialchars($userName)]);
            $this->saveIncident(Session::getClientIp());
            return;
        }

        if (false === $user->enabled) {
            Tools::log()->warning('login-user-disabled');
            return;
        }

        if (false === $user->verifyPassword($password)) {
            Tools::log()->warning('login-password-fail');
            $this->saveIncident(Session::getClientIp(), $userName);
            return;
        }

        if($user->two_factor_enabled){
            $this->two_factor_view = true;
            $this->user = $user;
            return;
        }

        $this->updateUserAndRedirect($user, Session::getClientIp(), $request->headers->get('User-Agent'));
    }

    private function validCodeAction(Request $request): void
    {
        $user = new User();
        $user->loadFromCode($request->request->get('fsNick'));

        if(!TwoFactorManager::verifyCode($user->two_factor_secret_key, $request->request->get('fsCode'))){
            Tools::log()->warning('login-2fa-fail');
            return;
        }

        $this->updateUserAndRedirect($user, Session::getClientIp(), $request->headers->get('User-Agent'));
    }

    private function updateUserAndRedirect(User $user, string $ip, string $browser): void
    {
        // update user data
        Session::set('user', $user);
        $user->newLogkey($ip, $browser);
        if (false === $user->save()) {
            Tools::log()->warning('login-user-not-saved');
            return;
        }

        // save cookies
        $expiration = time() + (int)Tools::config('cookies_expire', 31536000);
        setcookie('fsNick', $user->nick, $expiration, Tools::config('route', '/'));
        setcookie('fsLogkey', $user->logkey, $expiration, Tools::config('route', '/'));
        setcookie('fsLang', $user->langcode, $expiration, Tools::config('route', '/'));

        // redirect to the user's main page
        if (empty($user->homepage)) {
            $user->homepage = Tools::config('route') . '/';
        }
        header('Location: ' . $user->homepage);
    }

    private function logoutAction(Request $request): void
    {
        if (false === $this->validateFormToken($request)) {
            return;
        }

        // remove cookies
        setcookie('fsNick', '', time() - 3600, Tools::config('route', '/'));
        setcookie('fsLogkey', '', time() - 3600, Tools::config('route', '/'));
        setcookie('fsLang', '', time() - 3600, Tools::config('route', '/'));

        // restart token
        $multiRequestProtection = new MultiRequestProtection();
        $multiRequestProtection->clearSeed();

        Tools::log()->notice('logout-ok');
    }
}
