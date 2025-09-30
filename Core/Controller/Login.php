<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Cache;
use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Lib\MultiRequestProtection;
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
    private $template = 'Login/Login.html.twig';

    /** @var string */
    public $title = 'Login';

    /** @var string */
    public $two_factor_user;

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
        $action = $request->inputOrQuery('action', '');

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

            case 'two-factor-validation':
                $this->twoFactorValidationAction($request);
                break;
        }

        echo Html::render($this->template, [
            'controllerName' => 'Login',
            'debugBarRender' => false,
            'fsc' => $this,
            'template' => $this->template,
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

    protected function changePasswordAction(Request $request): void
    {
        if (false === $this->validateFormToken($request)) {
            return;
        }

        $username = $request->input('fsNewUserPasswd');
        if ($this->userHasManyIncidents(Session::getClientIp(), $username)) {
            Tools::log()->warning('ip-banned');
            return;
        }

        $dbPassword = $request->input('fsDbPasswd');
        if ($dbPassword !== Tools::config('db_pass')) {
            Tools::log()->warning('login-invalid-db-password');
            $this->saveIncident(Session::getClientIp(), $username);
            return;
        }

        $password = $request->input('fsNewPasswd');
        $password2 = $request->input('fsNewPasswd2');
        if (empty($username) || empty($password) || empty($password2)) {
            Tools::log()->warning('login-empty-fields');
            return;
        }

        if ($password !== $password2) {
            Tools::log()->warning('different-passwords', ['%userNick%' => $username]);
            return;
        }

        $user = new User();
        if (false === $user->load($username)) {
            Tools::log()->warning('login-user-not-found');
            $this->saveIncident(Session::getClientIp(), $username);
            return;
        }

        if (false === $user->enabled) {
            Tools::log()->warning('login-user-disabled');
            return;
        }

        $user->setPassword($password);

        // desactivamos el 2FA si estaba activado
        if ($user->two_factor_enabled) {
            $user->disableTwoFactor();
        }

        if (false === $user->save()) {
            Tools::log()->warning('login-user-not-saved');
            $this->saveIncident(Session::getClientIp(), $username);
            return;
        }

        Tools::log()->notice('login-password-changed');
    }

    protected function validateFormToken(Request $request): bool
    {
        $multiRequestProtection = new MultiRequestProtection();

        // si el usuario está autenticado, añadimos su nick a la semilla
        $cookieNick = $request->cookie('fsNick', '');
        if ($cookieNick) {
            $multiRequestProtection->addSeed($cookieNick);
        }

        // comprobamos el token
        $token = $request->inputOrQuery('multireqtoken', '');
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

    protected function getIpList(): array
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

    protected function getUserList(): array
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

    protected function loginAction(Request $request): void
    {
        if (false === $this->validateFormToken($request)) {
            return;
        }

        $userName = $request->input('fsNick');
        $password = $request->input('fsPassword');
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
        if (false === $user->load($userName)) {
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

        if ($user->two_factor_enabled) {
            $this->two_factor_user = $user->nick;
            $this->template = 'Login/TwoFactor.html.twig';
            return;
        }

        $this->updateUserAndRedirect($user, Session::getClientIp(), $request);
    }

    protected function twoFactorValidationAction(Request $request): void
    {
        $user = new User();
        if (!$user->load($request->input('fsNick'))) {
            Tools::log()->warning('user-not-found');
            $this->saveIncident(Session::getClientIp());
            return;
        }

        if (!$user->verifyTwoFactorCode($request->input('fsTwoFactorCode'))) {
            Tools::log()->warning('two-factor-code-invalid');
            $this->saveIncident(Session::getClientIp(), $user->nick);
            return;
        }

        $this->updateUserAndRedirect($user, Session::getClientIp(), $request);
    }

    protected function updateUserAndRedirect(User $user, string $ip, Request $request): void
    {
        // update user data
        Session::set('user', $user);
        $browser = $request->userAgent();
        $user->newLogkey($ip, $browser);
        if (false === $user->save()) {
            Tools::log()->warning('login-user-not-saved');
            return;
        }

        // save cookies
        $this->saveCookies($user, $request);

        // redirect to the user's main page
        if (empty($user->homepage)) {
            $user->homepage = Tools::config('route') . '/';
        }
        header('Location: ' . $user->homepage);
    }

    protected function logoutAction(Request $request): void
    {
        if (false === $this->validateFormToken($request)) {
            return;
        }

        // remove cookies
        $path = Tools::config('route', '/');
        setcookie('fsNick', '', time() - 3600, $path);
        setcookie('fsLogkey', '', time() - 3600, $path);
        setcookie('fsLang', '', time() - 3600, $path);

        // restart token
        $multiRequestProtection = new MultiRequestProtection();
        $multiRequestProtection->clearSeed();

        Tools::log()->notice('logout-ok');
    }

    protected function saveCookies(User $user, Request $request): void
    {
        $expiration = time() + (int)Tools::config('cookies_expire', 31536000);
        $path = Tools::config('route', '/');
        $secure = $request->isSecure();

        setcookie('fsNick', $user->nick, $expiration, $path, '', $secure, true);
        setcookie('fsLogkey', $user->logkey, $expiration, $path, '', $secure, true);
        setcookie('fsLang', $user->langcode, $expiration, $path, '', $secure, true);
    }
}
