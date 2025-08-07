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
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\User;

abstract class Controller implements ControllerInterface
{
    /** @var string */
    private $className;

    /** @var DataBase */
    protected $dataBase;

    /** @var Empresa */
    public $empresa;

    /** @var Request */
    public $request;

    /** @var Response */
    private $response;

    /** @var string */
    private $template;

    /** @var string */
    public $title;

    /** @var string */
    public $url;

    /** @var ?User */
    public $user;

    public function __construct(string $className, string $url = '')
    {
        Session::set('controllerName', $className);
        Session::set('pageName', $className);
        Session::set('uri', $url);

        $this->className = $className;
        $this->dataBase = new DataBase();
        $this->empresa = Empresas::default();
        $this->template = $className . '.html.twig';
        $this->url = $url;

        $pageData = $this->getPageData();
        $this->title = empty($pageData) ? $className : Tools::lang()->trans($pageData['title']);
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

    public function getTemplate(): string
    {
        return $this->template;
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

    public function run(): void
    {
        if (!$this->auth()) {
            throw new KernelException('AccessDenied', 'access-denied');
        }

        AssetManager::clear();
        AssetManager::setAssetsForPage($this->className);

        $this->checkPhpVersion(8.0);
    }

    public function url(): string
    {
        return $this->className;
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

    public function setTemplate($template): void
    {
        $this->template = empty($template) ? '' : $template . '.html.twig';
    }

    protected function auth(): bool
    {
        // Obtener el nick del usuario de la cookie
        $cookieNick = $this->request->cookies->get('fsNick', '');
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
            setcookie('fsNick', '', $cookiesExpire, '/');
            return false;
        }

        // Verificar la logkey del usuario desde la cookie
        $logKey = $this->request->cookies->get('fsLogkey', '') ?? '';
        if (false === $user->verifyLogkey($logKey)) {
            // Si la logkey no es válida, registrar advertencia, eliminar cookie y fallar autenticación
            Tools::log()->warning('login-cookie-fail');
            setcookie('fsNick', '', $cookiesExpire, '/');
            return false;
        }

        // Actualizar la última actividad del usuario si ha pasado el período definido
        if (time() - strtotime($user->lastactivity) > User::UPDATE_ACTIVITY_PERIOD) {
            $ip = Session::getClientIp();
            $browser = $this->request->headers->get('User-Agent');
            $user->updateActivity($ip, $browser);
            $user->save();
        }

        // Establecer el usuario en la sesión actual
        Session::set('user', $user);
        return true;
    }

    protected function validateFormToken(): bool
    {
        return true;
    }
}
