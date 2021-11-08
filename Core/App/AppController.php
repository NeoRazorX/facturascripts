<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2021 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Core\App;

use Exception;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\Debug\DumbBar;
use FacturaScripts\Core\Base\MenuManager;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Model\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class to manage selected controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AppController extends App
{

    const USER_UPDATE_ACTIVITY_PERIOD = 3600;

    /**
     * Controller loaded
     *
     * @var Controller
     */
    private $controller;

    /**
     * Load user's menu
     *
     * @var MenuManager
     */
    private $menuManager;

    /**
     * Contains the page name.
     *
     * @var string
     */
    private $pageName;

    /**
     * @var User|false
     */
    private $user = false;

    /**
     * Initializes the app.
     *
     * @param string $uri
     * @param string $pageName
     */
    public function __construct(string $uri = '/', string $pageName = '')
    {
        parent::__construct($uri);
        $this->menuManager = new MenuManager();
        $this->pageName = $pageName;
    }

    /**
     * @return DumbBar
     */
    public function debugBar()
    {
        return new DumbBar();
    }

    /**
     * Select and run the corresponding controller.
     *
     * @return bool
     */
    public function run(): bool
    {
        if (false === parent::run()) {
            return false;
        } elseif ($this->request->query->get('logout')) {
            $this->userLogout();
            $this->renderHtml('Login/Login.html.twig');
            $route = empty(FS_ROUTE) ? 'index.php' : FS_ROUTE;
            $this->response->headers->set('Refresh', '0; ' . $route);
            return false;
        } elseif ($this->request->request->get('fsNewUserPasswd')) {
            $this->newUserPassword();
        }

        $this->user = $this->userAuth();

        // returns the name of the controller to load
        $pageName = $this->getPageName();
        $this->loadController($pageName);

        // returns true for testing purpose
        return true;
    }

    /**
     * @param int $status
     * @param string $message
     */
    protected function die(int $status, string $message = '')
    {
        $content = ToolBox::i18n()->trans($message);
        foreach (ToolBox::log()::read() as $log) {
            $content .= empty($content) ? $log["message"] : "\n" . $log["message"];
        }

        $this->response->setContent(nl2br($content));
        $this->response->setStatusCode($status);
    }

    /**
     * Returns the controllers full name
     *
     * @param string $pageName
     *
     * @return string
     */
    private function getControllerFullName(string $pageName): string
    {
        $controllerName = '\\FacturaScripts\\Dinamic\\Controller\\' . $pageName;
        return class_exists($controllerName) ? $controllerName : '\\FacturaScripts\\Core\\Controller\\' . $pageName;
    }

    /**
     * Returns the name of the default controller for the current user or for all users.
     *
     * @return string
     */
    private function getPageName(): string
    {
        if ($this->pageName !== '') {
            return $this->pageName;
        }

        if ($this->getUriParam(0) !== 'index.php' && $this->getUriParam(0) !== '') {
            return $this->getUriParam(0);
        }

        if ($this->user && !empty($this->user->homepage)) {
            return $this->user->homepage;
        }

        return ToolBox::appSettings()->get('default', 'homepage', 'Wizard');
    }

    /**
     * Load and process the $pageName controller.
     *
     * @param string $pageName
     */
    protected function loadController(string $pageName)
    {
        $controllerName = $this->getControllerFullName($pageName);
        $template = 'Error/ControllerNotFound.html.twig';

        // If we found a controller, load it
        if (class_exists($controllerName)) {
            ToolBox::i18nLog()->debug('loading-controller', ['%controllerName%' => $controllerName]);
            $this->menuManager->setUser($this->user);
            $permissions = new ControllerPermissions($this->user, $pageName);

            $this->controller = new $controllerName($pageName, $this->uri);
            if ($this->user === false) {
                $this->controller->publicCore($this->response);
                $template = $this->controller->getTemplate();
            } elseif ($permissions->allowAccess) {
                $this->menuManager->selectPage($this->controller->getPageData());
                $this->controller->privateCore($this->response, $this->user, $permissions);
                $template = $this->controller->getTemplate();
            } else {
                $template = 'Error/AccessDenied.html.twig';
            }
        } else {
            ToolBox::i18nLog()->critical('controller-not-found');
            $this->response->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        if ($template) {
            $this->renderHtml($template, $controllerName);
        }
    }

    private function newUserPassword()
    {
        $user = new User();
        $nick = $this->request->request->get('fsNewUserPasswd');
        $pass = $this->request->request->get('fsNewPasswd');
        $pass2 = $this->request->request->get('fsNewPasswd2');

        if ($pass != $pass2) {
            ToolBox::i18nLog()->warning('different-passwords', ['%userNick%' => $nick]);
            return;
        } elseif ($user->loadFromCode($nick) && $this->request->request->get('fsDbPasswd') == FS_DB_PASS) {
            $user->setPassword($pass);
            $user->save();
            ToolBox::i18nLog()->notice('record-updated-correctly');
            return;
        }

        $this->ipWarning();
        ToolBox::i18nLog()->warning('login-password-fail');
    }

    /**
     * Creates HTML with the selected template. The data will not be inserted in it
     * until render() is executed
     *
     * @param string $template
     * @param string $controllerName
     */
    protected function renderHtml(string $template, string $controllerName = '')
    {
        // HTML template variables
        $templateVars = [
            'appSettings' => ToolBox::appSettings(),
            'assetManager' => new AssetManager(),
            'controllerName' => $controllerName,
            'debugBarRender' => $this->debugBar(),
            'fsc' => $this->controller,
            'menuManager' => $this->menuManager,
            'template' => $template
        ];

        $webRender = new WebRender();
        $webRender->loadPluginFolders();

        try {
            $this->response->setContent($webRender->render($template, $templateVars));
        } catch (Exception $exc) {
            ToolBox::log()->critical($exc->getMessage());
            $this->response->setContent($webRender->render('Error/TemplateError.html.twig', $templateVars));
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * User authentication, returns the user when successful, or false when not.
     *
     * @return User|false
     */
    private function userAuth()
    {
        $user = new User();
        $nick = $this->request->request->get('fsNick', '');
        if ($nick === '') {
            return $this->cookieAuth($user);
        }

        if ($user->loadFromCode($nick) && $user->enabled) {
            if ($user->verifyPassword($this->request->request->get('fsPassword'))) {
                // Execute actions from User model extensions
                $user->pipe('login');

                $this->updateCookies($user, true);
                ToolBox::ipFilter()->clear();
                ToolBox::i18nLog()->debug('login-ok', ['%nick%' => $user->nick]);
                ToolBox::log()::setContext('nick', $user->nick);
                return $user;
            }

            $this->ipWarning();
            ToolBox::i18nLog()->warning('login-password-fail');
            return false;
        }

        $this->ipWarning();
        ToolBox::i18nLog()->warning('login-user-not-found', ['%nick%' => $nick]);
        return false;
    }

    /**
     * Authenticate the user using the cookie.
     *
     * @param User $user
     *
     * @return User|bool
     */
    private function cookieAuth(User &$user)
    {
        $cookieNick = $this->request->cookies->get('fsNick', '');
        if ($cookieNick === '') {
            return false;
        }

        if ($user->loadFromCode($cookieNick) && $user->enabled) {
            if ($user->verifyLogkey($this->request->cookies->get('fsLogkey'))) {
                $this->updateCookies($user);
                ToolBox::i18nLog()->debug('login-ok', ['%nick%' => $user->nick]);
                ToolBox::log()::setContext('nick', $user->nick);
                return $user;
            }

            ToolBox::i18nLog()->warning('login-cookie-fail');
            $this->response->headers->clearCookie('fsNick');
            return false;
        }

        ToolBox::i18nLog()->warning('login-user-not-found', ['%nick%' => $cookieNick]);
        return false;
    }

    /**
     * Updates user cookies.
     *
     * @param User $user
     * @param bool $force
     */
    private function updateCookies(User &$user, bool $force = false)
    {
        if ($force || time() - strtotime($user->lastactivity) > self::USER_UPDATE_ACTIVITY_PERIOD) {
            $ipAddress = ToolBox::ipFilter()->getClientIp();
            if ($force) {
                $user->newLogkey($ipAddress);
            } else {
                $user->updateActivity($ipAddress);
            }

            $user->save();

            $expire = time() + FS_COOKIES_EXPIRE;
            $this->response->headers->setCookie(new Cookie('fsNick', $user->nick, $expire, FS_ROUTE));
            $this->response->headers->setCookie(new Cookie('fsLogkey', $user->logkey, $expire, FS_ROUTE));
            $this->response->headers->setCookie(new Cookie('fsLang', $user->langcode, $expire, FS_ROUTE));
            $this->response->headers->setCookie(new Cookie('fsCompany', $user->idempresa, $expire, FS_ROUTE));
        }
    }

    /**
     * Log out the user.
     */
    private function userLogout()
    {
        $this->response->headers->clearCookie('fsNick', FS_ROUTE);
        $this->response->headers->clearCookie('fsLogkey', FS_ROUTE);
        $this->response->headers->clearCookie('fsCompany', FS_ROUTE);
        ToolBox::i18nLog()->debug('logout-ok');
    }
}
