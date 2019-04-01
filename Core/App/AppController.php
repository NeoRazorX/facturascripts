<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use DebugBar\StandardDebugBar;
use Exception;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\DebugBar\DataBaseCollector;
use FacturaScripts\Core\Base\DebugBar\PHPCollector;
use FacturaScripts\Core\Base\DebugBar\TranslationCollector;
use FacturaScripts\Core\Base\EventManager;
use FacturaScripts\Core\Base\MenuManager;
use FacturaScripts\Core\Lib\AssetManager;
use FacturaScripts\Core\Model\User;
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
     * PHDebugBar.
     *
     * @var StandardDebugBar
     */
    private $debugBar;

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
     *
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
        $this->debugBar = new StandardDebugBar();
        if (FS_DEBUG) {
            $this->debugBar['time']->startMeasure('init', 'AppController::__construct()');
            $this->debugBar->addCollector(new DataBaseCollector($this->miniLog));
            $this->debugBar->addCollector(new TranslationCollector($this->i18n));
            $this->debugBar->addCollector(new PHPCollector());
        }

        $this->menuManager = new MenuManager();
        $this->pageName = $pageName;
    }

    /**
     *
     * @param string $nick
     */
    public function close(string $nick = '')
    {
        $selectedNick = (false !== $this->user) ? $this->user->nick : '';
        parent::close($selectedNick);
    }

    /**
     * Select and run the corresponding controller.
     *
     * @return bool
     */
    public function run()
    {
        if (!$this->dataBase->connected()) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->renderHtml('Error/DbError.html.twig');
        } elseif ($this->isIPBanned()) {
            $this->response->setStatusCode(Response::HTTP_FORBIDDEN);
            $this->response->setContent('IP-BANNED');
        } elseif ($this->request->query->get('logout')) {
            $this->userLogout();
            $this->renderHtml('Login/Login.html.twig');
            $route = empty(\FS_ROUTE) ? 'index.php' : \FS_ROUTE;
            $this->response->headers->set('Refresh', '0; ' . $route);
        } else {
            $this->user = $this->userAuth();

            /// returns the name of the controller to load
            $pageName = $this->getPageName();
            $this->loadController($pageName);

            /// returns true for testing purpose
            return true;
        }

        return false;
    }

    /**
     * Returns the controllers full name
     *
     * @param string $pageName
     *
     * @return string
     */
    private function getControllerFullName(string $pageName)
    {
        $controllerName = "FacturaScripts\\Dinamic\\Controller\\{$pageName}";
        if (!class_exists($controllerName)) {
            $controllerName = "FacturaScripts\\Core\\Controller\\{$pageName}";

            /// This is important in development and unattended installations
            if (FS_DEBUG || !file_exists(FS_FOLDER . DIRECTORY_SEPARATOR . 'Dinamic')) {
                $this->pluginManager->deploy();
            }
        }

        return $controllerName;
    }

    /**
     * Returns the name of the default controller for the current user or for all users.
     *
     * @return string
     */
    private function getPageName()
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

        return $this->settings->get('default', 'homepage', 'Wizard');
    }

    /**
     * Load and process the $pageName controller.
     *
     * @param string $pageName
     */
    private function loadController(string $pageName)
    {
        if (FS_DEBUG) {
            $this->debugBar['time']->stopMeasure('init');
            $this->debugBar['time']->startMeasure('loadController', 'AppController::loadController()');
        }

        $controllerName = $this->getControllerFullName($pageName);
        $template = 'Error/ControllerNotFound.html.twig';
        $httpStatus = Response::HTTP_NOT_FOUND;

        /// If we found a controller, load it
        if (class_exists($controllerName)) {
            $this->miniLog->debug($this->i18n->trans('loading-controller', ['%controllerName%' => $controllerName]));
            $this->menuManager->setUser($this->user);
            $permissions = new ControllerPermissions($this->user, $pageName);

            try {
                $this->controller = new $controllerName($this->cache, $this->i18n, $this->miniLog, $pageName, $this->uri);
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

                $httpStatus = Response::HTTP_OK;
            } catch (Exception $exc) {
                $this->miniLog->critical($exc->getMessage());
                $this->debugBar['exceptions']->addException($exc);
                $httpStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
                $template = 'Error/ControllerError.html.twig';
            }
        } else {
            $this->miniLog->critical($this->i18n->trans('controller-not-found'));
        }

        $this->response->setStatusCode($httpStatus);
        if ($template) {
            if (FS_DEBUG) {
                $this->debugBar['time']->stopMeasure('loadController');
                $this->debugBar['time']->startMeasure('renderHtml', 'AppController::renderHtml()');
            }

            $this->renderHtml($template, $controllerName);
        }
    }

    /**
     * Creates HTML with the selected template. The data will not be inserted in it
     * until render() is executed
     *
     * @param string $template       html file to use
     * @param string $controllerName
     */
    private function renderHtml(string $template, string $controllerName = '')
    {
        /// HTML template variables
        $templateVars = [
            'appSettings' => $this->settings,
            'assetManager' => new AssetManager(),
            'controllerName' => $controllerName,
            'debugBarRender' => false,
            'fsc' => $this->controller,
            'menuManager' => $this->menuManager,
            'template' => $template,
        ];

        $webRender = new WebRender();
        $webRender->loadPluginFolders();

        if (FS_DEBUG) {
            $baseUrl = FS_ROUTE . '/vendor/maximebf/debugbar/src/DebugBar/Resources/';
            $templateVars['debugBarRender'] = $this->debugBar->getJavascriptRenderer($baseUrl);

            /// add log data to the debugBar
            foreach ($this->miniLog->read(['debug']) as $msg) {
                $this->debugBar['messages']->info($msg['message']);
            }
            $this->debugBar['messages']->info('END');
        }

        try {
            $this->response->setContent($webRender->render($template, $templateVars));
        } catch (Exception $exc) {
            $this->miniLog->critical($exc->getMessage());
            $this->debugBar['exceptions']->addException($exc);
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
                EventManager::trigger('App:User:Login', $user);
                $this->updateCookies($user, true);
                $this->ipFilter->clear();
                $this->miniLog->debug($this->i18n->trans('login-ok', ['%nick%' => $nick]));
                return $user;
            }

            $this->ipFilter->setAttempt($this->request->getClientIp());
            $this->miniLog->warning($this->i18n->trans('login-password-fail'));
            return false;
        }

        $this->ipFilter->setAttempt($this->request->getClientIp());
        $this->miniLog->alert($this->i18n->trans('login-user-not-found', ['%nick%' => $nick]));
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
                $this->miniLog->debug($this->i18n->trans('login-ok', ['%nick%' => $cookieNick]));
                return $user;
            }

            $this->miniLog->warning($this->i18n->trans('login-cookie-fail'));
            $this->response->headers->clearCookie('fsNick');
            return false;
        }

        $this->miniLog->alert($this->i18n->trans('login-user-not-found', ['%nick%' => $cookieNick]));
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
        if ($force || \time() - \strtotime($user->lastactivity) > self::USER_UPDATE_ACTIVITY_PERIOD) {
            $user->newLogkey($this->request->getClientIp());
            $user->save();

            $expire = time() + FS_COOKIES_EXPIRE;
            $this->response->headers->setCookie(new Cookie('fsNick', $user->nick, $expire));
            $this->response->headers->setCookie(new Cookie('fsLogkey', $user->logkey, $expire));
            $this->response->headers->setCookie(new Cookie('fsLang', $user->langcode, $expire));
            $this->response->headers->setCookie(new Cookie('fsCompany', $user->idempresa, $expire));
        }
    }

    /**
     * Log out the user.
     */
    private function userLogout()
    {
        $this->response->headers->clearCookie('fsNick');
        $this->response->headers->clearCookie('fsLogkey');
        $this->miniLog->debug($this->i18n->trans('logout-ok'));
    }
}
