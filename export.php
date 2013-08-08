<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
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
if( !defined('FS_DB_TYPE') )
   define('FS_DB_TYPE', 'POSTGRESQL');

require_once 'model/empresa.php';

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
   if( !isset($_SERVER["argv"]) )
      echo "uso: php5 export.php \"query\"\n";
   else if( count($_SERVER["argv"]) != 2 )
      echo "uso: php5 export.php \"query\"\n";
   else
   {
      /// necesitamos un modelo cualquiera para poder ejecutar modelo::var2str()
      $empresa = new empresa();
      $results = $db->select( str_replace('"', '', $_SERVER["argv"][1]) );
      if($results)
      {
         $first = TRUE;
         foreach($results as $col)
         {
            if($first)
            {
               echo implode(',', array_keys($col))."\n";
               $first = FALSE;
            }
            
            $c_first = TRUE;
            foreach($col as $cell)
            {
               if( in_array($cell, array('t', 'f')) )
                  $aux_cell = ($cell == 't');
               else
                  $aux_cell = $cell;
               
               if($c_first)
               {
                  echo base64_encode( $empresa->var2str($aux_cell) );
                  $c_first = FALSE;
               }
               else
                  echo ','.base64_encode( $empresa->var2str($aux_cell) );
            }
            echo "\n";
         }
      }
   }
}
else
   echo "¡Imposible conectar con la base de datos.!\n";

?>
