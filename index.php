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
require_once __DIR__ . '/base/fs_plugin_manager.php';
require_once __DIR__ . '/base/fs_i18n.php';
require_once __DIR__ . '/base/fs_controller.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/// Obtenemos la lista de plugins activos
$pluginManager = new fs_plugin_manager(__DIR__);
$pluginList = $pluginManager->enabledPluggins;

/// Cargamos el traductor
$i18n = new fs_i18n(__DIR__, 'es_ES');

/// Obtenemos el nombre del controlador a cargar
$request = Request::createFromGlobals();
$controllerName = $request->get('page', 'admin_home');
$controllerPath = '';
$template = 'controller_not_found.html';

/// Buscamos el controlador en los plugins
foreach ($pluginList as $pName) {
    if (file_exists(__DIR__ . '/' . $pName . '/controller/' . $controllerName . '.php')) {
        $controllerPath = __DIR__ . '/' . $pName . '/controller/' . $controllerName . '.php';
        break;
    }
}

/// ¿Buscamos en controller?
if (!$controllerPath) {
    if (file_exists(__DIR__ . '/controller/' . $controllerName . '.php')) {
        $controllerPath = __DIR__ . '/controller/' . $controllerName . '.php';
    }
}

/// Si hemos encontrado el controlador, lo cargamos
$fsc = FALSE;
$fscException = FALSE;
$fscHTTPstatus = Response::HTTP_OK;
if ($controllerPath) {
    require $controllerPath;

    try {
        $fsc = new $controllerName(__DIR__);
        $template = $fsc->template;
    } catch (Exception $ex) {
        $fscException = $ex;
        $fscHTTPstatus = Response::HTTP_INTERNAL_SERVER_ERROR;
    }
} else {
    $fscHTTPstatus = Response::HTTP_NOT_FOUND;
}

if ($template) {
    /// cargamos Twig
    $twigLoader = new Twig_Loader_Filesystem(__DIR__ . '/view');
    $twig = new Twig_Environment($twigLoader);

    /// renderizamos el html
    $response = new Response($twig->render($template, array('fsc' => $fsc, 'i18n' => $i18n, 'template' => $template, 'exception' => $fscException, 'controllerName' => $controllerName)), $fscHTTPstatus);
    $response->send();
}