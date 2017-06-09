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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;

/// Obtenemos la lista de plugins activos
$pluginManager = new fs_plugin_manager(__DIR__);
$pluginList = $pluginManager->enabledPluggins;

/// Obtenemos el nombre del controlador a cargar
$request = Request::createFromGlobals();
$controllerName = $request->get('page', 'admin_home');

/// Buscamos el controlador en los plugins
$controllerPath = '';
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

/// Cargamos el traductor
$translator = new Translator('es_ES');
$translator->addLoader('array', new ArrayLoader());
$translator->addResource('array', array(
    'Código' => 'Codig',
    'Controlador' => 'Controlador',
    'Controlador no encontrado' => 'Controlador no encontrat',
    'Error fatal' => 'Error fatal',
    'Mensaje' => 'Mesatge'
), 'es_CA');

/// Si hemos encontrado el controlador, lo cargamos
if ($controllerPath) {
    require $controllerPath;

    try {
        $fsc = new $controllerName(__DIR__);
    } catch (Exception $ex) {
        $html = "<h1>".$translator->trans('Error fatal')."</h1>"
                . "<ul>"
                . "<li><b>".$translator->trans('Controlador').":</b> " . $controllerPath . "</li>"
                . "<li><b>".$translator->trans('Código').":</b> " . $e->getCode() . "</li>"
                . "<li><b>".$translator->trans('Mensage').":</b> " . $e->getMessage() . "</li>"
                . "</ul>";
        $response = new Response($html, Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->send();
    }
} else {
    $response = new Response('<h1>'.$translator->trans('Controlador no encontrado').' :-(</h1>', Response::HTTP_NOT_FOUND);
    $response->send();
}