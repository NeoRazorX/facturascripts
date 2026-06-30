<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\Base;

use FacturaScripts\Core\Contract\ControllerInterface;
use FacturaScripts\Core\DataSrc\Empresas;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Lib\MenuManager as NewMenuManager;
use FacturaScripts\Core\Model\Empresa;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\MultiRequestProtection;
use FacturaScripts\Dinamic\Model\User as DinUser;

/**
 * Clase de la que deben heredar todos los controladores de FacturaScripts.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Controller implements ControllerInterface
{
    /**
     * Nombre de la clase del controlador (aunque herede de esta clase,
     * aquí tendremos el nombre de la clase final).
     *
     * @var string __CLASS__
     */
    private $className;

    /**
     * Proporciona acceso directo a la base de datos.
     *
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Empresa seleccionada.
     *
     * @var Empresa
     */
    public $empresa;

    /**
     * @var MultiRequestProtection
     */
    public $multiRequestProtection;

    /**
     * Permisos del usuario sobre este controlador.
     *
     * @var ControllerPermissions
     */
    public $permissions;

    /**
     * Petición de la que podemos obtener datos.
     *
     * @var Request
     */
    public $request;

    /**
     * Objeto de respuesta HTTP.
     *
     * @var Response
     */
    protected $response;

    /**
     * Nombre del archivo de la plantilla.
     *
     * @var string|false nombre_archivo.html.twig
     */
    private $template;

    /**
     * Título de la página.
     *
     * @var string título de la página.
     */
    public $title;

    /**
     * URI dada, por defecto vacía.
     *
     * @var string
     */
    public $uri;

    /**
     * Usuario que ha iniciado sesión.
     *
     * @var User|false
     */
    public $user = false;

    /**
     * Inicializa todos los objetos y propiedades.
     *
     * @param string $className
     * @param string $uri
     */
    public function __construct(string $className, string $uri = '')
    {
        $this->className = $className;

        Session::set('controllerName', $this->className);
        Session::set('pageName', $this->className);
        Session::set('uri', $uri);

        $this->dataBase = new DataBase();
        $this->empresa = Empresas::default();
        $this->multiRequestProtection = new MultiRequestProtection();
        $this->request = Request::createFromGlobals();
        $this->template = $this->className . '.html.twig';
        $this->uri = $uri;

        $pageData = $this->getPageData();
        $this->title = empty($pageData) ? $this->className : Tools::trans($pageData['title']);

        AssetManager::clear();
        AssetManager::setAssetsForPage($className);

        $this->checkPhpVersion(8.1);
    }

    /**
     * @param mixed $extension
     */
    public static function addExtension($extension)
    {
        Tools::log()->error('no-extension-support', ['%className%' => static::class]);
    }

    /**
     * Devuelve los datos básicos de esta página (nombre, título, icono, menú, etc.).
     *
     * Esta función solamente sirve para eso. No se debe añadir aquí ningún otro código,
     * ya que en este punto el usuario ni siquiera se ha logueado todavía.
     *
     * @return array
     */
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

    /**
     * Devuelve la plantilla a usar para este controlador.
     *
     * @return string|false
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public function pipe($name, ...$arguments)
    {
        Tools::log()->error('no-extension-support', ['%className%' => static::class]);
        return null;
    }

    /**
     * @param string $name
     * @param array $arguments
     *
     * @return bool
     */
    public function pipeFalse($name, ...$arguments): bool
    {
        Tools::log()->error('no-extension-support', ['%className%' => static::class]);
        return true;
    }

    /**
     * Ejecuta la lógica privada del controlador.
     *
     * @param Response $response
     * @param User $user
     * @param ControllerPermissions $permissions
     */
    public function privateCore(&$response, $user, $permissions)
    {
        $this->permissions = $permissions;
        Session::set('permissions', $this->permissions);
        $this->response = &$response;
        $this->user = $user;

        if (false === $this->permissions->allowAccess) {
            throw new KernelException('AccessDenied', Tools::lang()->trans('access-denied'));
        }

        // Si el usuario tiene asignada una empresa distinta a la predeterminada, la seleccionamos
        $this->setCompany($this->user->idempresa);

        // Añadimos el usuario a la semilla de generación del token
        $this->multiRequestProtection->addSeed($user->nick);

        // ¿Tiene este usuario una página por defecto?
        $cookiesExpire = time() + Tools::config('cookies_expire');
        $defaultPage = $this->request->query('defaultPage', '');
        if ($defaultPage === 'TRUE') {
            $this->user->homepage = $this->className;
            $this->response->cookie('fsHomepage', $this->user->homepage, $cookiesExpire);
            $this->user->save();
        } elseif ($defaultPage === 'FALSE') {
            $this->user->homepage = null;
            $this->response->cookie('fsHomepage', $this->user->homepage, $cookiesExpire);
            $this->user->save();
        }
    }

    /**
     * Selecciona la empresa indicada, salvo que esté vacía o ya sea la cargada.
     *
     * @param int|null $idempresa
     */
    protected function setCompany($idempresa): void
    {
        if (empty($idempresa) || $this->empresa->idempresa == $idempresa) {
            return;
        }

        $this->empresa = Empresas::get($idempresa);
    }

    /**
     * Ejecuta la parte pública del controlador.
     *
     * @param Response $response
     */
    public function publicCore(&$response)
    {
        $this->permissions = new ControllerPermissions();
        Session::set('permissions', $this->permissions);
        $this->response = &$response;
        $this->template = 'Login/Login.html.twig';

        $this->empresa = Empresas::default();
    }

    /**
     * Redirige a una url o controlador.
     *
     * @param string $url
     * @param int $delay
     */
    public function redirect(string $url, int $delay = 0)
    {
        $this->response->header('Refresh', $delay . '; ' . $url);
        if ($delay === 0) {
            $this->setTemplate(false);
        }
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function run(): void
    {
        // creamos la respuesta
        $response = new Response();

        // si se ha podido autenticar, ejecutamos la parte privada
        if ($this->auth()) {
            $permissions = new ControllerPermissions(Session::user(), $this->className);
            $this->privateCore($response, Session::user(), $permissions);

            // renderizamos la plantilla
            if ($this->template) {
                Kernel::startTimer('Controller::html-render');
                $response->view($this->template, [
                    'controllerName' => $this->className,
                    'fsc' => $this,
                    'menuManager' => NewMenuManager::init()->selectPage($this->getPageData()),
                    'template' => $this->template,
                ]);
                Kernel::stopTimer('Controller::html-render');
            }

            $response->send();
            return;
        }

        // si no se ha podido autenticar, ejecutamos la parte pública
        $this->publicCore($response);

        // renderizamos la plantilla
        if ($this->template) {
            Kernel::startTimer('Controller::html-render');
            $response->view($this->template, [
                'controllerName' => $this->className,
                'fsc' => $this,
                'template' => $this->template,
            ]);
            Kernel::stopTimer('Controller::html-render');
        }

        $response->send();
    }

    /**
     * Establece la plantilla a usar para este controlador.
     *
     * @param string|false $template
     *
     * @return bool
     */
    public function setTemplate($template): bool
    {
        $this->template = ($template === false) ? false : $template . '.html.twig';
        return true;
    }

    /**
     * Devuelve la URL del controlador actual.
     *
     * @return string
     */
    public function url(): string
    {
        return $this->className;
    }

    private function auth(): bool
    {
        // Obtener el nick del usuario de la cookie
        $cookieNick = $this->request->cookie('fsNick', '');
        if (empty($cookieNick)) {
            // Si no hay nick en la cookie, no se puede autenticar
            return false;
        }

        // Cargar el usuario desde la base de datos usando el nick
        $user = new DinUser();
        if (false === $user->load($cookieNick)) {
            // Si el usuario no se encuentra, registrar advertencia y fallar autenticación
            Tools::log()->warning('login-user-not-found', ['%nick%' => htmlspecialchars($cookieNick)]);
            return false;
        }

        // Verificar si el usuario está activado
        $deleteCookieOptions = [
            'expires' => time() - 3600,
            'path' => Tools::config('route', '/'),
            'domain' => '',
            'secure' => $this->request->isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        if (false === $user->enabled) {
            // Si el usuario está desactivado, registrar advertencia, eliminar cookie y fallar autenticación
            Tools::log()->warning('login-user-disabled', ['%nick%' => htmlspecialchars($cookieNick)]);
            setcookie('fsNick', '', $deleteCookieOptions);
            return false;
        }

        // Verificar la logkey del usuario desde la cookie
        $logKey = $this->request->cookie('fsLogkey', '') ?? '';
        if (false === $user->verifyLogkey($logKey)) {
            // Si la logkey no es válida, registrar advertencia, eliminar cookie y fallar autenticación
            Tools::log()->warning('login-cookie-fail');
            setcookie('fsNick', '', $deleteCookieOptions);
            return false;
        }

        // Actualizar la última actividad del usuario si ha pasado el período definido
        if (time() - strtotime($user->lastactivity) > User::UPDATE_ACTIVITY_PERIOD) {
            $ip = Session::getClientIp();
            $browser = $this->request->header('User-Agent');
            $user->updateActivity($ip, $browser);
            $user->save();
        }

        // Establecer el usuario en la sesión actual
        Session::set('user', $user);
        return true;
    }

    private function checkPhpVersion(float $min): void
    {
        $current = (float)substr(phpversion(), 0, 3);
        if ($current < $min) {
            Tools::log()->warning('php-support-end', ['%current%' => $current, '%min%' => $min]);
        }
    }

    protected function db(): DataBase
    {
        return $this->dataBase;
    }

    /**
     * Devuelve el nombre del controlador.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return $this->className;
    }

    protected function response(): Response
    {
        return $this->response;
    }

    /**
     * Comprueba el token de la petición. Devuelve un error si:
     *   - el token no existe
     *   - el token no es válido
     *   - el token está duplicado
     *
     * @return bool
     */
    protected function validateFormToken(): bool
    {
        // ¿petición válida?
        $token = $this->request->inputOrQuery('multireqtoken', '');
        if (empty($token) || false === $this->multiRequestProtection->validate($token)) {
            Tools::log()->warning('invalid-request');
            return false;
        }

        // ¿petición duplicada?
        if ($this->multiRequestProtection->tokenExist($token)) {
            Tools::log()->warning('duplicated-request');
            return false;
        }

        return true;
    }
}
