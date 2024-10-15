<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\Html;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\KernelException;
use FacturaScripts\Core\Model\User;
use FacturaScripts\Core\Request;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\MultiRequestProtection;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\User as DinUser;

/**
 * Class from which all FacturaScripts controllers must inherit.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class Controller implements ControllerInterface
{
    /**
     * Name of the class of the controller (although its in inheritance from this class,
     * the name of the final class we will have here)
     *
     * @var string __CLASS__
     */
    private $className;

    /**
     * It provides direct access to the database.
     *
     * @var DataBase
     */
    protected $dataBase;

    /**
     * Selected company.
     *
     * @var Empresa
     */
    public $empresa;

    /**
     * @var MultiRequestProtection
     */
    public $multiRequestProtection;

    /**
     * User permissions on this controller.
     *
     * @var ControllerPermissions
     */
    public $permissions;

    /**
     * Request on which we can get data.
     *
     * @var Request
     */
    public $request;

    /**
     * HTTP Response object.
     *
     * @var Response
     */
    protected $response;

    /**
     * Name of the file for the template.
     *
     * @var string|false nombre_archivo.html.twig
     */
    private $template;

    /**
     * Title of the page.
     *
     * @var string título de la página.
     */
    public $title;

    /**
     * Given uri, default is empty.
     *
     * @var string
     */
    public $uri;

    /**
     * User logged in.
     *
     * @var User|false
     */
    public $user = false;

    /**
     * Initialize all objects and properties.
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
        $this->empresa = new Empresa();
        $this->multiRequestProtection = new MultiRequestProtection();
        $this->request = Request::createFromGlobals();
        $this->template = $this->className . '.html.twig';
        $this->uri = $uri;

        $pageData = $this->getPageData();
        $this->title = empty($pageData) ? $this->className : Tools::lang()->trans($pageData['title']);

        AssetManager::clear();
        AssetManager::setAssetsForPage($className);

        $this->checkPhpVersion(7.4);
    }

    /**
     * @param mixed $extension
     */
    public static function addExtension($extension)
    {
        Tools::log()->error('no-extension-support', ['%className%' => static::class]);
    }

    /**
     * Return the basic data for this page.
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
     * Return the template to use for this controller.
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
     * Runs the controller's private logic.
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

        // Select the default company for the user
        $this->empresa = Empresas::get($this->user->idempresa);

        // add the user to the token generation seed
        $this->multiRequestProtection->addSeed($user->nick);

        // Have this user a default page?
        $defaultPage = $this->request->query->get('defaultPage', '');
        if ($defaultPage === 'TRUE') {
            $this->user->homepage = $this->className;
            $this->response->cookie('fsHomepage', $this->user->homepage, time() + FS_COOKIES_EXPIRE);
            $this->user->save();
        } elseif ($defaultPage === 'FALSE') {
            $this->user->homepage = null;
            $this->response->cookie('fsHomepage', $this->user->homepage, time() - FS_COOKIES_EXPIRE);
            $this->user->save();
        }
    }

    /**
     * Execute the public part of the controller.
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
     * Redirect to an url or controller.
     *
     * @param string $url
     * @param int $delay
     */
    public function redirect(string $url, int $delay = 0)
    {
        $this->response->headers->set('Refresh', $delay . '; ' . $url);
        if ($delay === 0) {
            $this->setTemplate(false);
        }
    }

    public function run(): void
    {
        // creamos la respuesta
        $response = new Response();
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000');

        // ejecutamos la parte privada o pública del controlador
        if ($this->auth()) {
            $permissions = new ControllerPermissions(Session::user(), $this->className);
            $this->privateCore($response, Session::user(), $permissions);
        } else {
            $this->publicCore($response);
        }

        // carga el menú
        $menu = new MenuManager();
        $menu->setUser(Session::user());
        $menu->selectPage($this->getPageData());

        // renderizamos la plantilla
        if ($this->template) {
            Kernel::startTimer('Controller::html-render');
            $response->setContent(Html::render($this->template, [
                'controllerName' => $this->className,
                'fsc' => $this,
                'menuManager' => $menu,
                'template' => $this->template,
            ]));
            Kernel::stopTimer('Controller::html-render');
        }
        $response->send();
    }

    /**
     * Set the template to use for this controller.
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
     * @return ToolBox
     * @deprecated since version 2023.1
     */
    public static function toolBox(): ToolBox
    {
        return new ToolBox();
    }

    /**
     * Return the URL of the actual controller.
     *
     * @return string
     */
    public function url(): string
    {
        return $this->className;
    }

    private function auth(): bool
    {
        $cookieNick = $this->request->cookies->get('fsNick', '');
        if (empty($cookieNick)) {
            return false;
        }

        $user = new DinUser();
        if (false === $user->loadFromCode($cookieNick) && $user->enabled) {
            Tools::log()->warning('login-user-not-found', ['%nick%' => $cookieNick]);
            return false;
        }

        $logKey = $this->request->cookies->get('fsLogkey', '') ?? '';
        if (false === $user->verifyLogkey($logKey)) {
            Tools::log()->warning('login-cookie-fail');
            // eliminamos la cookie
            setcookie('fsNick', '', time() - FS_COOKIES_EXPIRE, '/');
            return false;
        }

        // actualizamos la actividad del usuario
        if (time() - strtotime($user->lastactivity) > User::UPDATE_ACTIVITY_PERIOD) {
            $ip = Session::getClientIp();
            $browser = $this->request->headers->get('User-Agent');
            $user->updateActivity($ip, $browser);
            $user->save();
        }

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

    /**
     * Return the name of the controller.
     *
     * @return string
     */
    protected function getClassName(): string
    {
        return $this->className;
    }

    /**
     * Check request token. Returns an error if:
     *   - the token does not exist
     *   - the token is invalid
     *   - the token is duplicated
     *
     * @return bool
     */
    protected function validateFormToken(): bool
    {
        // valid request?
        $urlToken = $this->request->query->get('multireqtoken', '');
        $token = $this->request->request->get('multireqtoken', $urlToken);
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
}
