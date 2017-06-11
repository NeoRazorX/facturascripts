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

require_once __DIR__ . '/vendor/autoload.php';

use FacturaScripts\Base\fs_i18n;
use FacturaScripts\Base\fs_plugin_manager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use DebugBar\StandardDebugBar;

/// Iniciamos debugbar
$debugbar = new StandardDebugBar();
$debugbarRenderer = $debugbar->getJavascriptRenderer('vendor/maximebf/debugbar/src/DebugBar/Resources/');

/// Obtenemos la lista de plugins activos
$pluginManager = new fs_plugin_manager(__DIR__);
$pluginList = $pluginManager->enabledPlugins();

/// Cargamos el traductor
$i18n = new fs_i18n(__DIR__, 'es_ES');

/// Obtenemos el nombre del controlador a cargar
$request = Request::createFromGlobals();
$controllerName = $request->get('page', 'admin_home');
$template = 'controller_not_found.html';
$controller = "";

/// Buscamos el controlador en los plugins
foreach ($pluginList as $pName) {
    if (class_exists("FacturaScripts\\Plugins\\{$pName}\\controller\\{$controllerName}")) {
        $controller = "FacturaScripts\\Plugins\\{$pName}\\controller\\{$controllerName}";
        break;
    }
}

/// ¿Buscamos en /controller?
if ($controller == "" && class_exists("FacturaScripts\\controller\\{$controllerName}")) {
    $controller = "FacturaScripts\\controller\\{$controllerName}";
}

/// Si hemos encontrado el controlador, lo cargamos
$fsc = FALSE;
$fscException = FALSE;
$fscHTTPstatus = Response::HTTP_OK;
if ($controller) {
    try {
        $fsc = new $controller(__DIR__, $controllerName);
        $fsc->run();
        $template = $fsc->template;
    } catch (Exception $ex) {
        $fscException = $ex;
        $fscHTTPstatus = Response::HTTP_INTERNAL_SERVER_ERROR;
    }
} else {
    $fscHTTPstatus = Response::HTTP_NOT_FOUND;
}

if (!is_writable('.')) {
    $response = new Response($i18n->trans('folder-not-writable'), Response::HTTP_INTERNAL_SERVER_ERROR);
    $response->send();
} else if ($template) {
    /// Cargamos el motor de plantillas
    $twigLoader = new Twig_Loader_Filesystem(__DIR__ . '/view');
    foreach ($pluginList as $pName) {
        if (file_exists(__DIR__ . '/plugins/' . $pName . '/view')) {
            $twigLoader->prependPath(__DIR__ . '/plugins/' . $pName . '/view');
        }
    }
    ///$twig = new Twig_Environment($twigLoader, array('cache' => 'cache/twig'));
    $twig = new Twig_Environment($twigLoader);

    /// renderizamos el html
    $templateVars = array(
        'fsc' => $fsc,
        'i18n' => $i18n,
        'template' => $template,
        'exception' => $fscException,
        'controllerName' => $controllerName,
        'debugbarRender' => $debugbarRenderer
    );
    try {
        $response = new Response($twig->render($template, $templateVars), $fscHTTPstatus);
    } catch (Exception $ex) {
        $response = new Response($twig->render('template_not_found.html', $templateVars), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
    $response->send();
}
