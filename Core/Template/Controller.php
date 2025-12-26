<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2023-2025 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Template;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Lib\ControllerPermissions;
use FacturaScripts\Core\Lib\MenuManager;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\MultiRequestProtection;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\User;

abstract class Controller implements ControllerInterface
{
    /** @var string */
    private $className;

    /** @var DataBase */
    private $dataBase;

    /** @var Empresa */
    public $empresa;

    /** @var MultiRequestProtection */
    private $multiRequestProtection;

    /** @var ControllerPermissions */
    public $permissions;

    /** @var Request */
    private $request;

    /** @var bool */
    protected $requiresAuth = true;

    /** @var Response */
    private $response;

    /** @var string */
    public $title;

    /** @var string */
    public $url;

    /** @var ?User */
    public $user;

    public function __construct(string $className, string $url = '')
    {
        $this->className = $className;
        $this->url = $url;

        $pageData = $this->getPageData();
        $this->title = empty($pageData) ? $className : Tools::trans($pageData['title']);
    }

    public static function addExtension($extension, int $priority = 100): void
    {
        Tools::log()->error('no-extension-support', ['%className%' => static::class]);
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function getPageData(): array
    {
        return [
            'name' => $this->className,
            'title' => $this->className,
            'icon' => 'fa-solid fa-circle',
            'menu' => 'new',
            'submenu' => null,
            'showonmenu' => true,
            'ordernum' => 100
        ];
    }

    public function pipe(string $name, ...$arguments)
    {
        Tools::log()->error('no-extension-support', ['%className%' => static::class]);

        return null;
    }

    public function pipeFalse(string $name, ...$arguments): bool
    {
        Tools::log()->error('no-extension-support', ['%className%' => static::class]);

        return true;
    }

    public function request(): Request
    {
        if (null === $this->request) {
            $this->request = Request::createFromGlobals();
        }

        return $this->request;
    }

    public function run(): void
    {
        Session::set('controllerName', $this->className);
        Session::set('pageName', $this->className);
        Session::set('uri', $this->url);

        // Intentar autenticar al usuario siempre
        $authenticated = $this->auth();

        // Si el controlador requiere autenticación y no está autenticado, lanzar excepción
        if ($this->requiresAuth && !$authenticated) {
            throw new KernelException('AuthenticationRequired', 'authentication-required');
        }

        // Cargamos y comprobamos los permisos del usuario (usamos get() en lugar de user() para obtener null si no lo hay)
        $this->permissions = new ControllerPermissions(Session::get('user'), $this->className);
        if ($this->requiresAuth && !$this->permissions->allowAccess) {
            // Si el usuario no tiene acceso, lanzar excepción
            throw new KernelException('AccessDenied', 'access-denied');
        }

        $this->empresa = Empresas::default();

        AssetManager::clear();
        AssetManager::setAssetsForPage($this->className);

        $this->checkPhpVersion(8.0);
    }

    public function url(): string
    {
        return $this->className;
    }

    protected function auth(): bool
    {
        // Obtener el nick del usuario de la cookie
        $cookieNick = $this->request()->cookie('fsNick', '');
        if (empty($cookieNick)) {
            // Si no hay nick en la cookie, no se puede autenticar
            return false;
        }

        // Cargar el usuario desde la base de datos usando el nick
        $user = new User();
        if (false === $user->load($cookieNick)) {
            // Si el usuario no se encuentra, registrar advertencia y fallar autenticación
            Tools::log()->warning('login-user-not-found', ['%nick%' => $cookieNick]);
            return false;
        }

        // Verificar si el usuario está activado
        $cookiesExpire = time() + Tools::config('cookies_expire');
        if (false === $user->enabled) {
            // Si el usuario está desactivado, registrar advertencia, eliminar cookie y fallar autenticación
            Tools::log()->warning('login-user-disabled', ['%nick%' => $cookieNick]);
            $this->response()->cookie('fsNick', '', $cookiesExpire);
            return false;
        }

        // Verificar la logkey del usuario desde la cookie
        $logKey = $this->request()->cookie('fsLogkey', '') ?? '';
        if (false === $user->verifyLogkey($logKey)) {
            // Si la logkey no es válida, registrar advertencia, eliminar cookie y fallar autenticación
            Tools::log()->warning('login-cookie-fail');
            $this->response()->cookie('fsLogkey', $logKey);
            return false;
        }

        // Actualizar la última actividad del usuario si ha pasado el período definido
        if (time() - strtotime($user->lastactivity) > User::UPDATE_ACTIVITY_PERIOD) {
            $ip = $this->request()->ip();
            $browser = $this->request()->browser();
            $user->updateActivity($ip, $browser);
            $user->save();
        }

        // Establecer el usuario en la sesión actual
        Session::set('user', $user);
        $this->user = $user;

        return true;
    }

    protected function db(): DataBase
    {
        if (null === $this->dataBase) {
            $this->dataBase = new DataBase();
            $this->dataBase->connect();
        }

        return $this->dataBase;
    }

    protected function checkPhpVersion(float $min): void
    {
        $current = (float)substr(phpversion(), 0, 3);
        if ($current < $min) {
            Tools::log()->warning('php-support-end', ['%current%' => $current, '%min%' => $min]);
        }
    }


    protected function response(): Response
    {
        if (null === $this->response) {
            $this->response = new Response();
        }

        return $this->response;
    }

    protected function validateFormToken(): bool
    {
        if (null === $this->multiRequestProtection) {
            $this->multiRequestProtection = new MultiRequestProtection();
        }

        // valid request?
        $token = $this->request()->inputOrQuery('multireqtoken', '');
        if (empty($token) || false === $this->multiRequestProtection->validate($token)) {
            Tools::log()->warning('invalid-request');
            return false;
        }

        // duplicated request?
        if ($this->multiRequestProtection->tokenExist($token)) {
            Tools::log()->warning('duplicated-request');
            return false;
        }

        return true;
    }

    protected function view(string $view, array $data = []): void
    {
        $data['controllerName'] = $this->className;
        $data['fsc'] = $this;
        $data['menuManager'] = MenuManager::init()->selectPage($this->getPageData());

        $this->response()->view($view, $data);
    }
}
