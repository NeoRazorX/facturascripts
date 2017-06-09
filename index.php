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

/// Obtenemos la lista de plugins activos
$pluginManager = new fs_plugin_manager(__DIR__);
$pluginList = $pluginManager->enabledPluggins;

/// Obtenemos el nombre del controlador a cargar
$request = Request::createFromGlobals();
$controllerName = $request->get('page', 'admin_home');

/// Buscamos el controlador en los plugins
$found = FALSE;
foreach ($pluginList as $pName) {
    if (file_exists(__DIR__ . '/' . $pName . '/controller/' . $controllerName . '.php')) {
        require __DIR__ . '/' . $pName . '/controller/' . $controllerName . '.php';
        $found = TRUE;
        break;
    }
}

/// ¿Buscamos en controller?
if (!$found) {
    if (file_exists(__DIR__ . '/controller/' . $controllerName . '.php')) {
        require __DIR__ . '/controller/' . $controllerName . '.php';
        $found = TRUE;
    }
}

/// Si hemos encontrado el controlador, lo cargamos
if ($found) {
    try {
        $fsc = new $controllerName(__DIR__);
    } catch (Exception $ex) {
        $html = "<h1>Error fatal</h1>"
        . "<ul>"
        . "<li><b>Código:</b> " . $e->getCode() . "</li>"
        . "<li><b>Mensage:</b> " . $e->getMessage() . "</li>"
        . "</ul>";
        $response = new Response($html, Response::HTTP_INTERNAL_SERVER_ERROR);
        $response->send();
    }
} else {
    $response = new Response('Controlador '.$controllerName.' no encontrado :-(', Response::HTTP_NOT_FOUND);
    $response->send();
}