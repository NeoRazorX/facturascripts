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

class xml_import_export extends fs_controller
{
   public function __construct()
   {
      parent::__construct('xml_import_export', 'Importar/exportar XML', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_POST['where']) )
      {
         $this->export_xml();
      }
      else if( isset($_POST['archivo']) )
      {
         $this->import_xml();
      }
   }
   
   public function tablas()
   {
      return $this->db->list_tables();
   }
   
   private function export_xml()
   {
      /// desactivamos el motor de plantillas
      $this->template = FALSE;
      
      /// creamos el xml
      $cadena_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!--
    Document   : tabla_".$_POST['tabla'].".xml
    Description:
        Datos de la tabla ".$_POST['tabla'].".
-->

<tabla>
</tabla>\n";
      
      $archivo_xml = simplexml_load_string($cadena_xml);
      
      $data = $this->db->select("SELECT * FROM ".$_POST['tabla']." WHERE ".$_POST['where'].";");
      if($data)
      {
         $archivo_xml->addChild('nombre', $_POST['tabla']);
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
      }
      
      /// volcamos el XML
      header("content-type: application/xml; charset=UTF-8");
      header('Content-Disposition: attachment; filename="tabla_'.$_POST['tabla'].'.xml"');
      echo $archivo_xml->asXML();
   }
   
   private function import_xml()
   {
      if( is_uploaded_file($_FILES['farchivo']['tmp_name']) )
      {
         $xml = simplexml_load_file($_FILES['farchivo']['tmp_name']);
         if($xml)
         {
            $tabla = $xml->nombre;
            
            $columnas = array();
            if($xml->columnas)
            {
               foreach($xml->columnas->columna as $col)
                  $columnas[] = $col;
            }
            
            if($xml->fila)
            {
               $total = 0;
               $fail = 0;
               
               foreach($xml->fila as $f)
               {
                  $filas = array();
                  foreach($columnas as $col)
                  {
                     if( in_array($f->$col, array('NULL', 'FALSE', 'TRUE')) )
                        $filas[] = $f->$col;
                     else
                        $filas[] = $this->empresa->var2str( base64_decode($f->$col) );
                  }
                  
                  if( $this->db->exec("INSERT INTO ".$tabla." (".join(',', $columnas).") VALUES (".join(',',$filas).");") )
                     $total++;
                  else
                     $fail++;
               }
               
               $this->new_message($total.' filas insertadas. '.$fail.' errores.');
               $this->cache->clean();
            }
         }
      }
   }
}

?>