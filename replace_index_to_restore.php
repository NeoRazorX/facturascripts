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

// si este archivo no es index.php, entonces mostramos un error
if (basename(__FILE__) !== 'index.php') {
    echo 'Remove index.php and rename this file to index.php';
    exit(1);
}

// si la versión de PHP es inferior a 7.3, entonces mostramos un error
if (version_compare(PHP_VERSION, '7.3.0') < 0) {
    echo 'FacturaScripts requires PHP 7.3.0 or newer.';
    exit(1);
}

// si no tenemos el archivo CORE.zip, lo descargamos
if (!file_exists(__DIR__ . '/CORE.zip')) {
    $file = file_get_contents('https://facturascripts.com/DownloadBuild/1/stable');
    if ($file === false) {
        echo 'Error downloading CORE.zip';
        exit(1);
    }

    file_put_contents(__DIR__ . '/CORE.zip', $file);

    // mostramos mensaje para que el usuario sepa que la descarga ha finalizado
    echo 'FacturaScripts downloaded successfully. <a href="">Click here to continue</a>.';
    exit;
}

// si no tenemos la carpeta facturascripts, descomprimimos el archivo CORE.zip
if (!file_exists(__DIR__ . '/facturascripts')) {
    $zip = new ZipArchive();
    if ($zip->open(__DIR__ . '/CORE.zip') !== true) {
        echo 'Error opening CORE.zip';
        exit(1);
    }

    if (!$zip->extractTo(__DIR__ . '/')) {
        echo 'Error extracting CORE.zip';
        exit(1);
    }

    $zip->close();

    // mostramos mensaje para que el usuario sepa que la descompresión ha finalizado
    echo 'FacturaScripts extracted successfully. <a href="">Click here to continue</a>.';
    exit;
}

function delete_folder($dir)
{
    if (!file_exists($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        if (is_dir("$dir/$file")) {
            delete_folder("$dir/$file");
        } else {
            unlink("$dir/$file");
        }
    }

    rmdir($dir);
}

function copy_folder($source, $dest)
{
    if (!file_exists($dest)) {
        mkdir($dest, 0777, true);
    }

    $dir = opendir($source);
    while (false !== ($file = readdir($dir))) {
        if (($file !== '.') && ($file !== '..')) {
            if (is_dir($source . '/' . $file)) {
                copy_folder($source . '/' . $file, $dest . '/' . $file);
            } else {
                copy($source . '/' . $file, $dest . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// para las carpetas Core, node_modules y vendor, eliminamos la carpeta existente y copiamos la nueva
delete_folder(__DIR__ . '/Core');
copy_folder(__DIR__ . '/facturascripts/Core', __DIR__ . '/Core');

delete_folder(__DIR__ . '/node_modules');
copy_folder(__DIR__ . '/facturascripts/node_modules', __DIR__ . '/node_modules');

delete_folder(__DIR__ . '/vendor');
copy_folder(__DIR__ . '/facturascripts/vendor', __DIR__ . '/vendor');

// reemplazamos el index.php por el nuevo
unlink(__DIR__ . '/index.php');
copy(__DIR__ . '/facturascripts/index.php', __DIR__ . '/index.php');

// eliminamos el archivo CORE.zip y la carpeta facturascripts
unlink(__DIR__ . '/CORE.zip');
delete_folder(__DIR__ . '/facturascripts');

// mostramos mensaje para que el usuario sepa que la restauración ha finalizado
echo 'FacturaScripts restored successfully. <a href="">Click here to continue</a>.';
exit(0);
