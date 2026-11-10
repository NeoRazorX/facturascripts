<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2026 Carlos Garcia Gomez <carlos@facturascripts.com>
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
use FacturaScripts\Core\CrashReport;
use FacturaScripts\Core\Kernel;
use FacturaScripts\Core\Plugins;
use FacturaScripts\Core\Telemetry;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\WorkQueue;

// modo mantenimiento (ver Core/Maintenance.php): lo comprobamos antes de cargar nada, con PHP puro,
// porque durante una actualización las carpetas Core y vendor pueden estar a medio sustituir
(static function (): void {
    $file = __DIR__ . '/MyFiles/maintenance';
    if (false === file_exists($file)) {
        return;
    }

    // un archivo vacío o ilegible es un mantenimiento activado a mano: sin caducidad ni token
    $data = json_decode((string)@file_get_contents($file), true);
    $expires = is_array($data) ? (int)($data['expires'] ?? 0) : 0;
    $token = is_array($data) ? (string)($data['token'] ?? '') : '';
    if ($expires > 0 && $expires <= time()) {
        return;
    }

    // la petición que continúa la actualización trae el token en la url
    $given = $_GET['maintenance'] ?? '';
    if ('' !== $token && is_string($given) && hash_equals($token, $given)) {
        return;
    }

    $spanish = 'es' === substr((string)($_COOKIE['fsLang'] ?? ''), 0, 2);
    $title = $spanish ? 'Actualización en curso' : 'Update in progress';
    $message = $spanish ?
        'Estamos instalando una actualización. Vuelva a intentarlo en unos segundos.' :
        'An update is being installed. Please try again in a few seconds.';

    // desde la línea de comandos (cron) solo avisamos
    if (PHP_SAPI === 'cli') {
        echo $message . PHP_EOL;
        exit(0);
    }

    http_response_code(503);
    header('Retry-After: 30');
    header('Cache-Control: no-store');

    $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (1 === preg_match('#/api(/|$)#', $path)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit(0);
    }

    if ('/cron' === substr($path, -5)) {
        header('Content-Type: text/plain; charset=UTF-8');
        echo $message . PHP_EOL;
        exit(0);
    }

    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="' . ($spanish ? 'es' : 'en') . '"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta http-equiv="refresh" content="15"><title>' . $title . '</title></head>'
        . '<body style="font-family: sans-serif; text-align: center; margin-top: 15vh;">'
        . '<h1>' . $title . '</h1><p>' . $message . '</p></body></html>';
    exit(0);
})();

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

// iniciamos el kernel
Kernel::init();

// obtenemos la url y ejecutamos el controlador
// si se le pasa el parámetro cron, entonces ejecutamos la url /cron
$url = isset($argv[1]) && $argv[1] === '-cron' ?
    '/cron' :
    parse_url($_SERVER["REQUEST_URI"] ?? '', PHP_URL_PATH) ?? '';

// iniciamos los plugins, a menos que la ruta sea /deploy o falte la configuración de la base de datos,
// porque entonces se muestra el instalador y los plugins no pueden usar la base de datos
if ($url !== '/deploy' && '' !== Tools::config('db_name', '')) {
    Plugins::init();
}

// ejecutamos el controlador
Kernel::run($url);

// si la petición ha sustituido archivos (actualización del núcleo o de un plugin), no ejecutamos nada más:
// las clases nuevas del disco se mezclarían con las antiguas que ya están cargadas en memoria
if (Kernel::codeReplaced()) {
    return;
}

$db = new DataBase();
if ($db->connected()) {
    // ejecutamos la cola de trabajos
    WorkQueue::run();

    // actualizamos la telemetría
    Telemetry::init()->update();

    // guardamos los logs y cerramos la conexión a la base de datos
    MiniLog::save();
    $db->close();
}
