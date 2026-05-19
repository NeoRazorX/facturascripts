<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
    const TWO_FACTOR_PENDING_TTL = 300;
    const USER_LIST = 'login-user-list';

    // Hash bcrypt fijo para igualar tiempos cuando el usuario no existe.
    // Nunca podrá verificarse contra ninguna contraseña real.
    const DUMMY_PASSWORD_HASH = '$2y$12$ye/68ONwKIM9/446.2a5G.GFcYDXB0hxLxQr2YFl1BhQ1wjoHM6Fu';

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
            Tools::log()->warning('different-passwords', ['%userNick%' => htmlspecialchars($username)]);
            return;
        }

        $user = new User();
        if (false === $user->load($username) || false === $user->enabled) {
            Tools::log()->warning('login-password-fail');
            $this->saveIncident(Session::getClientIp(), $username);
            return;
        }

        if (false === $user->setPassword($password)) {
            Tools::log()->warning('weak-password', ['%userNick%' => htmlspecialchars($username)]);
            return;
        }

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
            // ejecutamos un password_verify falso para igualar tiempos
            // y evitar enumeración de usuarios por timing
            password_verify($password, self::DUMMY_PASSWORD_HASH);
            Tools::log()->warning('login-password-fail');
            $this->saveIncident(Session::getClientIp(), $userName);
            return;
        }

        if (false === $user->enabled || false === $user->verifyPassword($password)) {
            Tools::log()->warning('login-password-fail');
            $this->saveIncident(Session::getClientIp(), $userName);
            return;
        }

        if ($user->two_factor_enabled) {
            Cache::set($this->twoFactorPendingKey(Session::getClientIp(), $user->nick), time());
            $this->two_factor_user = $user->nick;
            $this->template = 'Login/TwoFactor.html.twig';
            return;
        }

        $this->updateUserAndRedirect($user, Session::getClientIp(), $request);
    }

    protected function twoFactorPendingKey(string $ip, string $userName): string
    {
        return 'login-2fa-pending-' . sha1($ip . '|' . $userName);
    }

    protected function twoFactorValidationAction(Request $request): void
    {
        if (false === $this->validateFormToken($request)) {
            return;
        }

        $userName = $request->input('fsNick');
        $ip = Session::getClientIp();

        // rate-limit antes de hacer cualquier trabajo
        if ($this->userHasManyIncidents($ip, $userName)) {
            Tools::log()->warning('ip-banned');
            return;
        }

        // exigimos que loginAction se haya completado con éxito recientemente
        $pendingKey = $this->twoFactorPendingKey($ip, $userName);
        $pendingAt = Cache::get($pendingKey);
        if (!is_int($pendingAt) || time() - $pendingAt > self::TWO_FACTOR_PENDING_TTL) {
            Tools::log()->warning('login-password-fail');
            $this->saveIncident($ip, $userName);
            return;
        }

        $user = new User();
        if (!$user->load($userName) || false === $user->enabled
            || !$user->verifyTwoFactorCode($request->input('fsTwoFactorCode'))) {
            Tools::log()->warning('two-factor-code-invalid');
            $this->saveIncident($ip, $userName);
            return;
        }

        // consumimos el nonce: un único intento exitoso por password-step
        Cache::delete($pendingKey);

        $this->updateUserAndRedirect($user, $ip, $request);
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

        // redirect to the user's main page; homepageUrl() devuelve un nombre de controlador seguro
        header('Location: ' . $user->homepageUrl());
    }

    protected function logoutAction(Request $request): void
    {
        if (false === $this->validateFormToken($request)) {
            return;
        }

        // invalidamos la logkey en el servidor para que la cookie robada no siga siendo válida
        $cookieNick = $request->cookie('fsNick', '');
        $cookieLogkey = $request->cookie('fsLogkey', '');
        if ($cookieNick && $cookieLogkey) {
            $user = new User();
            if ($user->load($cookieNick) && $user->verifyLogkey($cookieLogkey)) {
                $user->newLogkey(Session::getClientIp(), $request->userAgent());
                $user->save();
            }
        }

        // limpiamos la sesión del lado servidor
        Session::set('user', null);

        // remove cookies (mismos atributos que en saveCookies para que el navegador acepte el borrado)
        $path = Tools::config('route', '/');
        $secure = $request->isSecure();
        $options = [
            'expires' => time() - 3600,
            'path' => $path,
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        setcookie('fsNick', '', $options);
        setcookie('fsLogkey', '', $options);
        setcookie('fsLang', '', $options);

        // restart token
        $multiRequestProtection = new MultiRequestProtection();
        $multiRequestProtection->clearSeed();

        Tools::log()->notice('logout-ok');
    }

    protected function saveCookies(User $user, Request $request): void
    {
        $options = [
            'expires' => time() + (int)Tools::config('cookies_expire', 31536000),
            'path' => Tools::config('route', '/'),
            'domain' => '',
            'secure' => $request->isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        setcookie('fsNick', $user->nick, $options);
        setcookie('fsLogkey', $user->logkey, $options);
        setcookie('fsLang', $user->langcode, $options);
    }
}
