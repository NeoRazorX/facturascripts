<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
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

/// cargamos las constantes de configuración
require_once 'config.php';
require_once 'base/config2.php';

if(strtolower(FS_DB_TYPE) == 'mysql')
{
   require_once 'base/fs_mysql.php';
   $db = new fs_mysql();
}
else
{
   require_once 'base/fs_postgresql.php';
   $db = new fs_postgresql();
}

require_once 'base/fs_model.php';
require_model('caja.php');

if( $db->connect() )
{
   if( isset($_GET['remote-printer']) )
   {
      if(FS_PRINTER == 'remote-printer')
      {
         /**
          * Añadimos un poquito de seguridad.
          * Comprobamos que la IP desde la que se quiere imprimir corresponda
          * con la del usuario que ha abierto la caja.
          */
         $caja = new caja();
         $caja0 = $caja->get_last_from_this_server();
         if( $caja0 AND isset($_SERVER['REMOTE_ADDR']) )
         {
            if( $caja0->ip == $_SERVER['REMOTE_ADDR'] OR is_null($caja0->ip) )
            {
               if( file_exists('tmp/'.FS_TMP_NAME.'remote-printer.txt') )
               {
                  echo file_get_contents('tmp/'.FS_TMP_NAME.'remote-printer.txt');
                  unlink('tmp/'.FS_TMP_NAME.'remote-printer.txt');
               }
            }
            else
               echo 'ERROR 3';
         }
      }
      else
         echo 'ERROR 2';
   }
   else
      echo 'ERROR 1';
}
else
   echo 'ERROR 0';
