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

require_once 'base/fs_db.php';

$db = new fs_db();
if( $db->connect() )
{
   if( !isset($_SERVER["argv"]) )
      echo "uso: php5 import.php ruta_del_archivo.xml tabla clave_primaria\n";
   else if( count($_SERVER["argv"]) != 4 )
      echo "uso: php5 import.php ruta_del_archivo.xml tabla clave_primaria\n";
   else if( !file_exists($_SERVER["argv"][1]) )
      echo "Archivo no encontrado.\n";
   else
   {
      echo 'Archivo: '.$_SERVER["argv"][1]."\n";
      echo 'Tabla: '.$_SERVER["argv"][2]."\n";
      echo 'Clave primaria: '.$_SERVER["argv"][3]."\n";
      
      $file = fopen($_SERVER["argv"][1], 'r');
      if($file)
      {
         $i = 0;
         while( !feof($file) )
         {
            $linea = trim( fgets($file, 1024) );
            if($i == 0)
               $columnas = explode(',', $linea);
            else
            {
               $aux = explode(',', $linea);
               
               if( count($columnas) == count($aux) )
               {
                  $d_columnas = array();
                  $j = 0;
                  while( $j < count($columnas) )
                  {
                     $d_columnas[ $columnas[$j] ] = base64_decode( $aux[$j] );
                     $j++;
                  }
                  
                  $sql_s = "SELECT * FROM ".$_SERVER["argv"][2]." WHERE ".$_SERVER["argv"][3].
                          " = ".$d_columnas[ $_SERVER["argv"][3] ].";";
                  $sql_i = "INSERT INTO ".$_SERVER["argv"][2]." (".implode(',', $columnas).
                          ") VALUES (".implode(',', $d_columnas).");";
                  
                  if( !$db->select($sql_s) )
                  {
                     if( $db->exec($sql_i) )
                        echo 'I';
                     else
                        echo "Error al ejecutar la sentencia: ".$sql_i."\n";
                  }
                  else
                     echo '.';
               }
               else
                  echo "Número de columnas incorrecto en la línea ".($i+1)."\n";
            }
            
            $i++;
         }
      }
   }
}
else
   echo "¡Imposible conectar con la base de datos.!\n";

?>