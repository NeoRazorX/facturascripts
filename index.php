<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2024 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\MiniLog;
use FacturaScripts\Core\Base\TelemetryManager;
use FacturaScripts\Core\CrashReport;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\WorkQueue;

require_once __DIR__ . '/vendor/autoload.php';

// cargamos la configuración
const FS_FOLDER = __DIR__;
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

// desactivamos el tiempo de ejecución y el aborto de la conexión
@set_time_limit(0);
ignore_user_abort(true);

// establecemos la zona horaria
$timeZone = Tools::config('timezone', 'Europe/Madrid');
date_default_timezone_set($timeZone);

// cargamos el gestor de errores
CrashReport::init();

// cargamos la variable APP_KEY para poder encriptar
// en entornos de desarrollo usamos el archivo .env para poner la APP_KEY en variable de entorno
// en entornos de producción se debe configurar la variable de entorno APP_KEY en el hosting
if(FS_DEBUG){
    $envFilePath = Tools::folder('.env');
    if (false === is_file($envFilePath)){
        $key = base64_encode(\FacturaScripts\Core\Lib\Encrypter::generateKey());
        file_put_contents($envFilePath, 'APP_KEY=' . $key, FILE_APPEND);
    }
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}else{
    $key = base64_encode(\FacturaScripts\Core\Lib\Encrypter::generateKey());
    throw new \FacturaScripts\Core\KernelException('DefaultError', 'Debe configurar esta variable de entorno en el hosting: APP_KEY="' . $key . '"');
}

// iniciamos el kernel
Kernel::init();

// obtenemos la url y ejecutamos el controlador
// si se le pasa el parámetro cron, entonces ejecutamos la url /cron
$url = isset($argv[1]) && $argv[1] === '-cron' ?
    '/cron' :
    parse_url($_SERVER["REQUEST_URI"] ?? '', PHP_URL_PATH) ?? '';

// iniciamos los plugins, a menos que la ruta sea /deploy
if ($url !== '/deploy') {
    Plugins::init();
}

// ejecutamos el controlador
Kernel::run($url);

$db = new DataBase();
if ($db->connected()) {
    // ejecutamos la cola de trabajos
    WorkQueue::run();

    // actualizamos la telemetría
    $telemetry = new TelemetryManager();
    $telemetry->update();

    // guardamos los logs y cerramos la conexión a la base de datos
    MiniLog::save();
    $db->close();
}
