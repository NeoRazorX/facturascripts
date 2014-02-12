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

class admin_table2xml extends fs_controller
{
   private $cadena_xml;
   private $archivo_xml;
   
   public function __construct()
   {
      parent::__construct('admin_table2xml', 'Estructura a XML', 'admin', TRUE, FALSE);
   }
   
   protected function process()
   {
      if( isset($_GET['table']) )
      {
         $this->generate_xml($_GET['table']);
      }
      else
      {
         $this->ppage = $this->page->get('xml_import_export');
      }
   }
   
   public function all()
   {
      return $this->db->list_tables();
   }
   
   public function generate_xml($table)
   {
      /// desactivamos la renderización del template
      $this->template = FALSE;
      
      $this->cadena_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!--
    Document   : " . $table . ".xml
    Description:
        Estructura de la tabla " . $table . ".
-->

<tabla>
</tabla>\n";

      /// creamos el xml
      $this->archivo_xml = simplexml_load_string($this->cadena_xml);
      $columnas = Array();
      $restricciones = Array();
      if( $this->db->table_exists($table) )
      {
         $columnas = $this->db->get_columns($table);
         $restricciones = $this->db->get_constraints($table);
         
         if($columnas)
         {
            foreach($columnas as $col)
            {
               $aux = $this->archivo_xml->addChild('columna');
               $aux->addChild('nombre', $col['column_name']);
               
               /// comprobamos si es tipo serial
               if($col['data_type'] == 'integer' AND $col['column_default'] == "nextval('".$table.'_'.$col['column_name']."_seq'::regclass)")
               {
                  $aux->addChild('tipo', 'serial');
                  
                  if( $col['is_nullable'] == 'YES')
                     $aux->addChild('nulo', 'YES');
                  else
                     $aux->addChild('nulo', 'NO');
                  
                  $aux->addChild('defecto', $col['column_default']);
               }
               else
               {
                  if( isset($col['character_maximum_length']) )
                     $aux->addChild('tipo', $col['data_type'] . '(' . $col['character_maximum_length'] . ')');
                  else
                     $aux->addChild('tipo', $col['data_type']);
                  
                  if( $col['is_nullable'] == 'YES')
                     $aux->addChild('nulo', 'YES');
                  else
                     $aux->addChild('nulo', 'NO');
                  
                  if( isset($col['column_default']) )
                     $aux->addChild('defecto', $col['column_default']);
               }
            }
         }
         
         if($restricciones)
         {
            foreach($restricciones as $col)
            {
               $aux = $this->archivo_xml->addChild('restriccion');
               $aux->addChild('nombre', $col['restriccion']);
               
               switch($col['tipo'])
               {
                  default:
                     $aux->addChild('consulta', '...');
                     break;
                  
                  case 'p':
                     $aux->addChild('consulta', 'PRIMARY KEY (...)');
                     break;
                  
                  case 'f':
                     $aux->addChild('consulta', 'FOREIGN KEY (...) REFERENCES ...');
                     break;
                  
                  case 'u':
                     $aux->addChild('consulta', 'UNIQUE (...)');
                     break;
               }
            }
         }
      }
      
      header("content-type: application/xml; charset=UTF-8");
      header('Content-Disposition: attachment; filename="'.$table.'.xml"');
      echo $this->archivo_xml->asXML();
   }
}

?>