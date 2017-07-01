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

/// comprobaciones previas
if (!file_exists(__DIR__ . '/config.php')) {
    /**
     * Si no hay fichero de configuración significa que no se ha instalado,
     * así que redirigimos al instalador.
     */
    header('Location: install.php');
    die('');
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

/// iniciamos la aplicación
$app = new FacturaScripts\Core\App\AppController(__DIR__);

/// conectamos a la base de datos, cache, etc
$app->connect();

/// ejecutamos el controlador que toque
$app->run();
$app->render();

/// desconectamos de todo
$app->close();
