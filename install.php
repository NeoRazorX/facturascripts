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
if (file_exists(__DIR__ . '/config.php')) {
    /**
     * Si hay fichero de configuración significa que ya se ha instalado,
     * así que redirigimos al index.
     */
    header('Location: index.php');
    die('');
} else if (!file_exists(__DIR__ . '/vendor')) {
    die("<h1>COMPOSER ERROR</h1><p>You need to run: composer install</p>"
            . "----------------------------------------"
            . "<p>Debes ejecutar: composer install</p>");
}

require_once __DIR__ . '/vendor/autoload.php';

use FacturaScripts\Core\Base\Translator;
use Symfony\Component\HttpFoundation\Response;

function searchErrors(&$errors, &$i18n) {
    if (floatval('3,1') >= floatval('3.1')) {
        $errors[] = $i18n->trans('wrong-decimal-separator');
    } else if (!function_exists('mb_substr')) {
        $errors[] = $i18n->trans('mb-string-not-fount');
    } else if (!extension_loaded('simplexml')) {
        $errors[] = $i18n->trans('simplexml-not-found');
    } else if (!extension_loaded('openssl')) {
        $errors[] = $i18n->trans('openssl-not-found');
    } else if (!extension_loaded('zip')) {
        $errors[] = $i18n->trans('ziparchive-not-found');
    } else if (!is_writable(__DIR__)) {
        $errors[] = $i18n->trans('folder-not-writable');
    }
}

function createFolders() {
    if (mkdir('Plugins') && mkdir('Dinamic') && mkdir('Cache')) {
        return TRUE;
    }

    return FALSE;
}

function saveInstall() {
    $file = fopen(__DIR__ . '/config.php', "w");
    if ($file) {
        fwrite($file, "<?php\n");
        fwrite($file, "define('FS_COOKIES_EXPIRE', 604800);\n");
        fwrite($file, "define('FS_DEBUG', TRUE);\n");
        fwrite($file, "define('FS_LANG', '" . filter_input(INPUT_POST, 'fs_lang') . "');\n");
        fwrite($file, "define('FS_DB_TYPE', '" . filter_input(INPUT_POST, 'db_type') . "');\n");
        fwrite($file, "define('FS_DB_HOST', '" . filter_input(INPUT_POST, 'db_host') . "');\n");
        fwrite($file, "define('FS_DB_PORT', '" . filter_input(INPUT_POST, 'db_port') . "');\n");
        fwrite($file, "define('FS_DB_NAME', '" . filter_input(INPUT_POST, 'db_name') . "');\n");
        fwrite($file, "define('FS_DB_USER', '" . filter_input(INPUT_POST, 'db_user') . "');\n");
        fwrite($file, "define('FS_DB_PASS', '" . filter_input(INPUT_POST, 'db_pass') . "');\n");
        if (filter_input(INPUT_POST, 'db_type') == 'MYSQL' AND filter_input(INPUT_POST, 'mysql_socket') != '') {
            fwrite($file, "ini_set('mysqli.default_socket', '" . filter_input(INPUT_POST, 'mysql_socket') . "');\n");
        }
        fwrite($file, "\n");
        fclose($file);
        return TRUE;
    }
    
    return FALSE;
}

function renderHTML(&$templateVars) {
    /// cargamos el motor de plantillas
    $twigLoader = new Twig_Loader_Filesystem(__DIR__ . '/Core/View');
    $twig = new Twig_Environment($twigLoader);

    /// generamos y volcamos el html
    $response = new Response($twig->render('installer/install.html', $templateVars), Response::HTTP_OK);
    $response->send();
}

function installerMain() {
    $errors = [];
    $i18n = new Translator(__DIR__);
    searchErrors($errors, $i18n);

    if (empty($errors) && filter_input(INPUT_POST, 'db_type')) {
        if (createFolders() AND saveInstall()) {
            header("Location: index.php");
            return 0;
        }
    }

    /// empaquetamos las variables a pasar el motor de plantillas
    $templateVars = array(
        'i18n' => $i18n,
        'license' => file_get_contents(__DIR__ . '/COPYING'),
        'errors' => $errors
    );
    renderHTML($templateVars);
}

installerMain();
