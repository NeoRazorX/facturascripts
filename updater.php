<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2015  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function random_string($length = 10)
{
   return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
}

function guarda_config()
{
   require 'config.php';
   
   $archivo = fopen('config.php', "w");
   if($archivo)
   {
      fwrite($archivo, "<?php\n");
      fwrite($archivo, "/*\n");
      fwrite($archivo, " * Configuración de la base de datos.\n");
      fwrite($archivo, " * type: postgresql o mysql (mysql está en fase experimental).\n");
      fwrite($archivo, " * host: la ip del ordenador donde está la base de datos.\n");
      fwrite($archivo, " * port: el puerto de la base de datos.\n");
      fwrite($archivo, " * name: el nombre de la base de datos.\n");
      fwrite($archivo, " * user: el usuario para conectar a la base de datos\n");
      fwrite($archivo, " * pass: la contraseña del usuario.\n");
      fwrite($archivo, " * history: TRUE si quieres ver todas las consultas que se hacen en cada página.\n");
      fwrite($archivo, " */\n");
      fwrite($archivo, "define('FS_DB_TYPE', '".FS_DB_TYPE."'); /// MYSQL o POSTGRESQL\n");
      fwrite($archivo, "define('FS_DB_HOST', '".FS_DB_HOST."');\n");
      fwrite($archivo, "define('FS_DB_PORT', '".FS_DB_PORT."'); /// MYSQL -> 3306, POSTGRESQL -> 5432\n");
      fwrite($archivo, "define('FS_DB_NAME', '".FS_DB_NAME."');\n");
      fwrite($archivo, "define('FS_DB_USER', '".FS_DB_USER."'); /// MYSQL -> root, POSTGRESQL -> postgres\n");
      fwrite($archivo, "define('FS_DB_PASS', '".FS_DB_PASS."');\n");
      fwrite($archivo, "\n");
      fwrite($archivo, "/*\n");
      fwrite($archivo, " * Un directorio de nombre aleatorio para mejorar la seguridad del directorio temporal.\n");
      fwrite($archivo, " */\n");
      
      if( defined('FS_TMP_NAME') )
      {
         fwrite($archivo, "define('FS_TMP_NAME', '".FS_TMP_NAME."/');\n");
      }
      else
      {
         fwrite($archivo, "define('FS_TMP_NAME', '".random_string()."/');\n");
      }
      
      fwrite($archivo, "\n");
      fwrite($archivo, "/*\n");
      fwrite($archivo, " * En cada ejecución muestra todas las sentencias SQL utilizadas.\n");
      fwrite($archivo, " */\n");
      fwrite($archivo, "define('FS_DB_HISTORY', FALSE);\n");
      fwrite($archivo, "/*\n");
      fwrite($archivo, " * Habilita el modo demo, para pruebas.\n");
      fwrite($archivo, " * Este modo permite hacer login con cualquier usuario y la contraseña demo,\n");
      fwrite($archivo, " * además deshabilita el límite de una conexión por usuario.\n");
      fwrite($archivo, " */\n");
      fwrite($archivo, "define('FS_DEMO', FALSE);\n");
      fwrite($archivo, "\n");
      fwrite($archivo, "/*\n");
      fwrite($archivo, " * Configuración de memcache.\n");
      fwrite($archivo, " * Host: la ip del servidor donde está memcached.\n");
      fwrite($archivo, " * port: el puerto en el que se ejecuta memcached.\n");
      fwrite($archivo, " * prefix: prefijo para las claves, por si tienes varias instancias de\n");
      fwrite($archivo, " * FacturaScripts conectadas al mismo servidor memcache.\n");
      fwrite($archivo, " */\n");
      fwrite($archivo, "\n");
      fwrite($archivo, "define('FS_CACHE_HOST', '".FS_CACHE_HOST."');\n");
      fwrite($archivo, "define('FS_CACHE_PORT', '".FS_CACHE_PORT."');\n");
      fwrite($archivo, "define('FS_CACHE_PREFIX', '".FS_CACHE_PREFIX."');\n");
      fwrite($archivo, "\n");
      fwrite($archivo, "/// caducidad (en segundos) de todas las cookies\n");
      fwrite($archivo, "define('FS_COOKIES_EXPIRE', 315360000);\n");
      fwrite($archivo, "\n");
      fwrite($archivo, "/// el número de elementos a mostrar en pantalla\n");
      fwrite($archivo, "define('FS_ITEM_LIMIT', 50);\n");
      fwrite($archivo, "\n");
      fwrite($archivo, "/*\n");
      fwrite($archivo, " * Un número identificador para esta instancia de FacturaScripts.\n");
      fwrite($archivo, " * Necesario para identificar cada caja en el TPV.\n");
      fwrite($archivo, " */\n");
      fwrite($archivo, "define('FS_ID', 1);\n");
      fwrite($archivo, "\n");
      fwrite($archivo, "/*\n");
      fwrite($archivo, " * Nombre o dirección de la impresora de tickets.\n");
      fwrite($archivo, " * '' -> impresora predefinida.\n");
      fwrite($archivo, " * 'epson234' -> impresora con nombre epson234.\n");
      fwrite($archivo, " * '/dev/usb/lp0' -> escribir diectamente sobre ese archivo.\n");
      fwrite($archivo, " * 'remote-printer' -> permite imprimir mediante el programa fs_remote_printer.py\n");
      fwrite($archivo, " */\n");
      fwrite($archivo, "define('FS_PRINTER', 'remote-printer');\n");
      fclose($archivo);
      
      return TRUE;
   }
   else
      return FALSE;
}

if( !file_exists('config.php') )
{
   echo 'Archivo config.php no encontrado.';
}
else if( !is_writable('updater.php') OR !is_writable('config.php') )
{
   echo 'No tienes permisos para escribir en la carpeta de FacturaScripts. Si usas Linux, prueba a ejecutar: '
   . '<pre>sudo chmod -R o+w '.dirname(__FILE__).'</pre>';
}
else if( !guarda_config() )
{
   echo 'Ha habido un error al actualizar el arcivo config.php';
}
else if( @file_put_contents('updater.php', @file_get_contents('https://raw.githubusercontent.com/NeoRazorX/facturascripts_2015/master/updater.php')) )
{
   echo 'Actualizador descargado correctamente. Recarga la p&aacute;gina o pulsa F5.';
}
else
   echo 'Error al descargar el actualizador.';