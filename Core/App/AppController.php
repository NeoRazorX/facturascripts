<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  <carlos@facturascripts.com>
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
use FacturaScripts\Core\Base\MenuManager;
use FacturaScripts\Core\Base\PluginManager;
use FacturaScripts\Core\Model\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Twig_Environment;
use Twig_Loader_Filesystem;

/**
 * Description of App
 *
 * @author Carlos García Gómez <carlos@facturascripts.com>
 */
class AppController extends App
{

    /**
     * Controlador cargado.
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
     * Para gestionar el menú del usuario
     *
     * @var MenuManager
     */
    private $menuManager;

    /**
     * AppController constructor.
     *
     * @param string $folder
     */
    public function __construct($folder = '')
    {
        parent::__construct($folder);
        $this->debugBar = new StandardDebugBar();
        $this->menuManager = new MenuManager();

        if (FS_DEBUG) {
            $this->debugBar['time']->startMeasure('init', 'AppController::__construct()');
            $this->debugBar->addCollector(new DataBaseCollector($this->miniLog));
            $this->debugBar->addCollector(new TranslationCollector($this->i18n));
        }
    }

    /**
     * Selecciona y ejecuta el controlador pertinente.
     *
     * @return boolean
     */
    public function run()
    {
        if (!$this->dataBase->connected()) {
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->renderHtml('Error/DbError.html');
        } elseif ($this->isIPBanned()) {
            $this->response->setStatusCode(Response::HTTP_FORBIDDEN);
            $this->response->setContent('IP-BANNED');
        } elseif ($this->request->query->get('logout')) {
            $this->userLogout();
            $this->renderHtml('Login/Login.html');
        } else {
            /// Obtenemos el nombre del controlador a cargar
            $pageName = $this->request->query->get('page', $this->getDefaultController());
            $this->loadController($pageName);

            /// devolvemos true, para los test
            return true;
        }

        return false;
    }
    
    private function getDefaultController()
    {
        return $this->request->cookies->get('fsHomepage', 'AdminHome');
    }

    /**
     * Carga y procesa el controlador $pageName.
     *
     * @param string $pageName nombre del controlador
     */
    private function loadController($pageName)
    {
        if (FS_DEBUG) {
            $this->debugBar['time']->stopMeasure('init');
            $this->debugBar['time']->startMeasure('loadController', 'AppController::loadController()');
        }

        $controllerName = $this->getControllerFullName($pageName);
        $template = 'Error/ControllerNotFound.html';
        $httpStatus = Response::HTTP_NOT_FOUND;

        /// Si hemos encontrado el controlador, lo cargamos
        if (class_exists($controllerName)) {
            $this->miniLog->debug($this->i18n->trans('loading-controller', [$controllerName]));
            $user = $this->userAuth();
            $this->menuManager->setUser($user);

            try {
                $this->controller = new $controllerName($this->cache, $this->i18n, $this->miniLog, $pageName);
                if ($user === false) {
                    $this->controller->publicCore($this->response);
                } else {
                    $this->menuManager->selectPage($this->controller->getPageData());
                    $this->controller->privateCore($this->response, $user);
                }
                $template = $this->controller->getTemplate();
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
     * Devuelve el nombre completo del controlador
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
            $this->deployPlugins();
        }

        return $controllerName;
    }

    /**
     * Crea el HTML con la plantilla seleccionada. Aunque los datos no se volcarán
     * hasta ejecutar render()
     *
     * @param string $template       archivo html a utilizar
     * @param string $controllerName
     */
    private function renderHtml($template, $controllerName = '')
    {
        /// cargamos el motor de plantillas
        $twigLoader = new Twig_Loader_Filesystem(FS_FOLDER . '/Core/View');
        foreach ($this->pluginManager->enabledPlugins() as $pluginName) {
            if (file_exists(FS_FOLDER . '/Plugins/' . $pluginName . '/View')) {
                $twigLoader->prependPath(FS_FOLDER . '/Plugins/' . $pluginName . '/View');
            }
        }

        /// opciones de twig
        $twigOptions = ['cache' => FS_FOLDER . '/Cache/Twig'];

        /// variables para la plantilla HTML
        $templateVars = [
            'controllerName' => $controllerName,
            'debugBarRender' => false,
            'fsc' => $this->controller,
            'i18n' => $this->i18n,
            'log' => $this->miniLog,
            'menuManager' => $this->menuManager,
            'sql' => $this->miniLog->read(['sql']),
            'template' => $template,
        ];

        if (FS_DEBUG) {
            unset($twigOptions['cache']);
            $twigOptions['debug'] = true;

            $env = new \DebugBar\Bridge\Twig\TraceableTwigEnvironment(new Twig_Environment($twigLoader));
            $this->debugBar->addCollector(new \DebugBar\Bridge\Twig\TwigCollector($env));
            $baseUrl = 'vendor/maximebf/debugbar/src/DebugBar/Resources/';
            $templateVars['debugBarRender'] = $this->debugBar->getJavascriptRenderer($baseUrl);

            /// añadimos del log a debugBar
            foreach ($this->miniLog->read(['debug']) as $msg) {
                $this->debugBar['messages']->info($msg['message']);
            }
            $this->debugBar['messages']->info('END');
        }
        $twig = new Twig_Environment($twigLoader, $twigOptions);

        try {
            $this->response->setContent($twig->render($template, $templateVars));
        } catch (Exception $exc) {
            $this->debugBar['exceptions']->addException($exc);
            $this->response->setContent($twig->render('Error/TemplateError.html', $templateVars));
            $this->response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Autentica al usuario, devuelve el usuario en caso afirmativo o false.
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
                    $this->response->headers->setCookie(new Cookie('fsNick', $user->nick, time() + FS_COOKIES_EXPIRE));
                    $this->response->headers->setCookie(new Cookie('fsLogkey', $logKey, time() + FS_COOKIES_EXPIRE));
                    $this->response->headers->setCookie(new Cookie('fsHomepage', $user->homepage, time() + FS_COOKIES_EXPIRE));
                    $this->response->headers->setCookie(new Cookie('fsLang', $user->langcode, time() + FS_COOKIES_EXPIRE));
                    $this->response->headers->setCookie(new Cookie('fsCompany', $user->idempresa, time() + FS_COOKIES_EXPIRE));
                    $this->miniLog->debug($this->i18n->trans('login-ok', [$nick]));
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
     * Autentica al usuario usando la cookie.
     * 
     * @param User $user0
     * @return boolean
     */
    private function cookieAuth(&$user0)
    {
        $cookieNick = $this->request->cookies->get('fsNick', '');
        if ($cookieNick !== '') {
            $cookieUser = $user0->get($cookieNick);
            if ($cookieUser) {
                if ($cookieUser->verifyLogkey($this->request->cookies->get('fsLogkey'))) {
                    $this->miniLog->debug($this->i18n->trans('login-ok', [$cookieNick]));
                    return $cookieUser;
                }

                $this->miniLog->alert($this->i18n->trans('login-cookie-fail'));
                return false;
            }

            $this->miniLog->alert($this->i18n->trans('login-user-not-found'));
            return false;
        }

        return false;
    }

    /**
     * Desautentica al usuario
     */
    private function userLogout()
    {
        $this->response->headers->clearCookie('fsNick');
        $this->response->headers->clearCookie('fsLogkey');
        $this->miniLog->debug($this->i18n->trans('logout-ok'));
    }

    /**
     * Carga los plugins
     */
    private function deployPlugins()
    {
        $pluginManager = new PluginManager();
        $pluginManager->deploy();
    }
}
