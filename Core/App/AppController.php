<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018  Carlos Garcia Gomez  <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Core\App;

use DebugBar\StandardDebugBar;
use Exception;
use FacturaScripts\Core\Base\DebugBar\DataBaseCollector;
use FacturaScripts\Core\Base\DebugBar\TranslationCollector;
use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Base\ControllerPermissions;
use FacturaScripts\Core\Base\MenuManager;
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Twig_Function;
use Twig_Environment;
use Twig_Loader_Filesystem;

/**
 * Class to manage selected controller.
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AppController extends App
{

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
     * Langcode to use in html.
     *
     * @var string
     */
    private $langcode2;

    /**
     * Load user's menu
     *
     * @var MenuManager
     */
    private $menuManager;

    /**
     * Initializes the app.
     *
     * @param string $uri
     */
    public function __construct($uri = '/')
    {
        parent::__construct($uri);
        $this->debugBar = new StandardDebugBar();
        if (FS_DEBUG) {
            $this->debugBar['time']->startMeasure('init', 'AppController::__construct()');
            $this->debugBar->addCollector(new DataBaseCollector($this->miniLog));
            $this->debugBar->addCollector(new TranslationCollector($this->i18n));
        }

        $this->langcode2 = substr($this->request->cookies->get('fsLang', FS_LANG), 0, 2);
        $this->menuManager = new MenuManager();
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
        } else {
            $user = $this->userAuth();

            /// returns the name of the controller to load
            $pageName = $this->getPageName($user);
            $this->loadController($pageName, $user);

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
    private function getControllerFullName($pageName)
    {
        $controllerName = "FacturaScripts\\Dinamic\\Controller\\{$pageName}";
        if (!class_exists($controllerName)) {
            $controllerName = "FacturaScripts\\Core\\Controller\\{$pageName}";
            if (FS_DEBUG) {
                $this->pluginManager->deploy();
            }
        }

        return $controllerName;
    }

    /**
     * Returns the name of the default controller for the current user or for all users.
     *
     * @param User|false $user
     *
     * @return string
     */
    private function getPageName($user)
    {
        if ($this->getUriParam(0) !== 'index.php' && $this->getUriParam(0) !== '') {
            return $this->getUriParam(0);
        }

        if ($user && $user->homepage !== null && $user->homepage !== '') {
            return $user->homepage;
        }

        return $this->settings->get('default', 'homepage', 'Wizard');
    }

    /**
     * Load and process the $pageName controller.
     *
     * @param string     $pageName
     * @param User|false $user
     */
    private function loadController($pageName, $user)
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
            $this->menuManager->setUser($user);
            $permissions = new ControllerPermissions($user, $pageName);

            try {
                $this->controller = new $controllerName($this->cache, $this->i18n, $this->miniLog, $pageName);
                if ($user === false) {
                    $this->controller->publicCore($this->response);
                    $template = $this->controller->getTemplate();
                } elseif ($permissions->allowAccess) {
                    $this->menuManager->selectPage($this->controller->getPageData());
                    $this->controller->privateCore($this->response, $user, $permissions);
                    $template = $this->controller->getTemplate();
                } else {
                    $template = 'Error/AccessDenied.html.twig';
                }

                $httpStatus = Response::HTTP_OK;
            } catch (Exception $exc) {
                $this->debugBar['exceptions']->addException($exc);
                $httpStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
            }
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
    private function renderHtml($template, $controllerName = '')
    {
        /// Load the template engine
        $twigLoader = $this->loadTwigFolders();

        /// Twig options
        $twigOptions = ['cache' => FS_FOLDER . '/MyFiles/Cache/Twig'];

        /// HTML template variables
        $templateVars = [
            'appSettings' => $this->settings,
            'controllerName' => $controllerName,
            'debugBarRender' => false,
            'fsc' => $this->controller,
            'i18n' => $this->i18n,
            'langcode2' => $this->langcode2,
            'log' => $this->miniLog,
            'menuManager' => $this->menuManager,
            'sql' => $this->miniLog->read(['sql']),
            'template' => $template,
        ];

        if (FS_DEBUG) {
            unset($twigOptions['cache']);
            $twigOptions['debug'] = true;

            $baseUrl = FS_ROUTE . '/vendor/maximebf/debugbar/src/DebugBar/Resources/';
            $templateVars['debugBarRender'] = $this->debugBar->getJavascriptRenderer($baseUrl);

            /// add log data to the debugBar
            foreach ($this->miniLog->read(['debug']) as $msg) {
                $this->debugBar['messages']->info($msg['message']);
            }
            $this->debugBar['messages']->info('END');
        }
        $twig = new Twig_Environment($twigLoader, $twigOptions);
        $assetFunction = new Twig_Function('asset', function ($string) {
            return FS_ROUTE . '/' . $string;
        });
        $twig->addFunction($assetFunction);

        try {
            $this->response->setContent($twig->render($template, $templateVars));
        } catch (Exception $exc) {
            $this->debugBar['exceptions']->addException($exc);
            $this->response->setContent($twig->render('Error/TemplateError.html.twig', $templateVars));
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Returns a TwigLoader object with the folders selecteds
     *
     * @return Twig_Loader_Filesystem
     */
    private function loadTwigFolders()
    {
        /// Path for default namespace
        $path = FS_DEBUG ? FS_FOLDER . '/Core/View' : FS_FOLDER . '/Dinamic/View';
        $twigLoader = new Twig_Loader_Filesystem($path);

        /// Core namespace
        $twigLoader->addPath(FS_FOLDER . '/Core/View', 'Core');

        foreach ($this->pluginManager->enabledPlugins() as $pluginName) {
            $pluginPath = FS_FOLDER . '/Plugins/' . $pluginName . '/View';
            if (!file_exists($pluginPath)) {
                continue;
            }

            /// plugin namespace
            $twigLoader->addPath($pluginPath, 'Plugin' . $pluginName);
            if (FS_DEBUG) {
                $twigLoader->prependPath($pluginPath);
            }
        }

        return $twigLoader;
    }

    /**
     * User authentication, returns the user when successful, or false when not.
     *
     * @return User|false
     */
    private function userAuth()
    {
        $user0 = new User();
        $nick = $this->request->request->get('fsNick', '');

        if ($nick !== '') {
            $user = $user0->get($nick);
            if ($user) {
                if ($user->verifyPassword($this->request->request->get('fsPassword'))) {
                    $logKey = $user->newLogkey($this->request->getClientIp());
                    $user->save();
                    $expire = time() + FS_COOKIES_EXPIRE;
                    $this->response->headers->setCookie(new Cookie('fsNick', $user->nick, $expire));
                    $this->response->headers->setCookie(new Cookie('fsLogkey', $logKey, $expire));
                    $this->response->headers->setCookie(new Cookie('fsLang', $user->langcode, $expire));
                    $this->response->headers->setCookie(new Cookie('fsCompany', $user->idempresa, $expire));
                    $this->miniLog->debug($this->i18n->trans('login-ok', ['%nick%' => $nick]));

                    return $user;
                }

                $this->ipFilter->setAttempt($this->request->getClientIp());
                $this->miniLog->alert($this->i18n->trans('login-password-fail'));

                return false;
            }

            $this->ipFilter->setAttempt($this->request->getClientIp());
            $this->miniLog->alert($this->i18n->trans('login-user-not-found'));

            return false;
        }

        return $this->cookieAuth($user0);
    }

    /**
     * Authenticate the user using the cookie.
     *
     * @param User $user0
     *
     * @return User|false
     */
    private function cookieAuth(&$user0)
    {
        $cookieNick = $this->request->cookies->get('fsNick', '');
        if ($cookieNick !== '') {
            $cookieUser = $user0->get($cookieNick);
            if ($cookieUser) {
                if ($cookieUser->verifyLogkey($this->request->cookies->get('fsLogkey'))) {
                    $this->miniLog->debug($this->i18n->trans('login-ok', ['%nick%' => $cookieNick]));

                    return $cookieUser;
                }

                $this->miniLog->alert($this->i18n->trans('login-cookie-fail'));
                $this->response->headers->clearCookie('fsNick');

                return false;
            }

            $this->miniLog->alert($this->i18n->trans('login-user-not-found'));
        }

        return false;
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
