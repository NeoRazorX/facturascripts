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

function export_xml(&$db, $tabla)
{
   $offset = 0;
   $continuar = TRUE;
   
   while($continuar)
   {
      echo '.';
      
      $data = $db->select_limit("SELECT * FROM ".$tabla, 500, $offset);
      if( $data AND !file_exists('tmp/'.FS_TMP_NAME.'export/tabla_'.$tabla.'_'.$offset.'.xml') )
      {
         /// creamos el xml
         $cadena_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!--
    Document   : tabla_".$tabla.".xml
    Description:
        Datos de la tabla ".$tabla.".
-->

<tabla>
</tabla>\n";
         
         $archivo_xml = simplexml_load_string($cadena_xml);
         $archivo_xml->addChild('nombre', $tabla);
         $columnas = TRUE;
         
         foreach($data as $d)
         {
            if($columnas)
            {
               $columns = array_keys($d);
               $aux0 = $archivo_xml->addChild('columnas');
               foreach($columns as $c)
                  $aux0->addChild('columna', $c);
               
               $columnas = FALSE;
            }
            
            $aux1 = $archivo_xml->addChild('fila');
            foreach($d as $i => $value)
            {
               if( is_null($value) )
                  $aux1->addChild($i, 'NULL');
               else if($value == 't')
                  $aux1->addChild($i, 'TRUE');
               else if($value == 'f')
                  $aux1->addChild($i, 'FALSE');
               else
                  $aux1->addChild($i, base64_encode($value));
            }
         }
         
         /// guardamos el XML
         $archivo_xml->saveXML('tmp/'.FS_TMP_NAME.'export/tabla_'.$tabla.'_'.$offset.'.xml');
      }
      else
         $continuar = FALSE;
      
      $offset += 500;
   }
}

/*
echo "\nExportando los datos de las tablas...";

if( !file_exists('tmp/'.FS_TMP_NAME.'export') )
   mkdir('tmp/'.FS_TMP_NAME.'export');

foreach($db->list_tables() as $table)
   export_xml($db, $table['name']);
 */

?>