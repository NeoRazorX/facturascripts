<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

namespace FacturaScripts\Core\Base;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DebugBar\StandardDebugBar;

/**
 * Description of App
 *
 * @author Carlos García Gómez
 */
class App {

    private $cache;
    private $connected;
    private $controller;
    private $dataBase;
    private $debugBar;
    private $folder;
    private $httpStatus;
    private $i18n;
    private $miniLog;
    private $pluginManager;

    public function __construct($foler = '') {
        $this->cache = new Cache($foler);
        $this->connected = FALSE;
        $this->controller = NULL;
        $this->dataBase = new DataBase();
        $this->debugBar = new StandardDebugBar();
        $this->folder = $foler;
        $this->httpStatus = Response::HTTP_OK;
        $this->i18n = new Translator($foler, FS_LANG);
        $this->miniLog = new MiniLog();
        $this->pluginManager = new PluginManager($foler);
    }

    public function connect() {
        $this->connected = $this->dataBase->connect();
        return $this->connected;
    }

    public function close() {
        $this->dataBase->close();
        $this->connected = FALSE;
    }
    
    public function runAPI() {
        
    }

    public function runController() {
        if ($this->connected) {
            /// Obtenemos el nombre del controlador a cargar
            $request = Request::createFromGlobals();
            $pageName = $request->get('page', 'AdminHome');
            $this->loadController($pageName);
        } else {
            $this->renderHtml('error/dbError.html');
        }
    }

    private function loadController($pageName) {
        $controllerName = "FacturaScripts\\Dinamic\\Controller\\{$pageName}";
        $template = 'error/controllerNotFound.html';
        $this->httpStatus = Response::HTTP_NOT_FOUND;

        if (!class_exists($controllerName)) {
            $controllerName = "FacturaScripts\\Core\\Controller\\{$pageName}";
        }
        
        /// Si hemos encontrado el controlador, lo cargamos
        if (class_exists($controllerName)) {
            $this->debugBar['messages']->info('Loading controller: '.$controllerName);
            
            try {
                $this->controller = new $controllerName(__DIR__);
                $this->controller->run();
                $template = $this->controller->template;
                $this->httpStatus = Response::HTTP_OK;
            } catch (\Exception $exc) {
                $this->debugBar['exceptions']->addException($exc);
                $this->httpStatus = Response::HTTP_INTERNAL_SERVER_ERROR;
            }
        }

        if ($template) {
            $this->renderHtml($template);
        }
    }

    public function runCron() {
        
    }

    private function renderHtml($template) {
        /// cargamos el motor de plantillas
        $twigLoader = new \Twig_Loader_Filesystem($this->folder . '/Core/View');
        foreach ($this->pluginManager->enabledPlugins() as $pluginName) {
            if (file_exists($this->folder . '/Plugins/' . $pluginName . '/View')) {
                $twigLoader->prependPath($this->folder . '/Plugins/' . $pluginName . '/View');
            }
        }

        /// opciones de twig
        $twigOptions = array('cache' => $this->folder . '/Cache/twig');

        /// variables para la plantilla HTML
        $templateVars = array(
            'debugBarRender' => FALSE,
            'fsc' => $this->controller,
            'i18n' => $this->i18n,
            'log' => $this->miniLog->read()
        );

        if (FS_DEBUG) {
            unset($twigOptions['cache']);
            $templateVars['debugBarRender'] = $this->debugBar->getJavascriptRenderer('vendor/maximebf/debugbar/src/DebugBar/Resources/');
        }
        $twig = new \Twig_Environment($twigLoader, $twigOptions);

        try {
            $response = new Response($twig->render($template, $templateVars), $this->httpStatus);
        } catch (\Exception $exc) {
            $this->debugBar['exceptions']->addException($exc);
            $response = new Response($twig->render('error/templateNotFound.html', $templateVars), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $response->send();
    }

}
