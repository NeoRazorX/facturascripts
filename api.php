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

date_default_timezone_set('Europe/Madrid');

/// cargamos las constantes de configuración
require_once 'config.php';

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

if( $db->connect() )
{
   if( isset($_GET['remote-printer']) )
   {
      if(FS_PRINTER == 'remote-printer')
      {
         if( file_exists('tmp/remote-printer.txt') )
         {
            echo file_get_contents('tmp/remote-printer.txt');
            unlink('tmp/remote-printer.txt');
         }
      }
      else
         echo 'ERROR';
   }
   else
      echo 'ERROR';
}
else
   echo 'ERROR';

?>