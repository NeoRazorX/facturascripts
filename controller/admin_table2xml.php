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

class admin_table2xml extends fs_controller
{
   private $cadena_xml;
   private $archivo_xml;
   
   public function __construct()
   {
      parent::__construct('admin_table2xml', 'Tabla a XML', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_GET['table']) )
      {
         $this->template = FALSE; /// desactivamos la renderización del template
         $this->generate_xml($_GET['table']);
      }
   }
   
   public function all()
   {
      return $this->db->list_tables();
   }
   
   public function generate_xml($table)
   {
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
               $aux = $this->archivo_xml->addChild("columna");
               $aux->addChild("nombre", $col['column_name']);
               
               if( isset($col['character_maximum_length']) )
                  $aux->addChild("tipo", $col['data_type'] . "(" . $col['character_maximum_length'] . ")");
               else
                  $aux->addChild("tipo", $col['data_type']);
               
               if( $col['is_nullable'] == "YES")
                  $aux->addChild("nulo", "YES");
               else
                  $aux->addChild("nulo", "NO");
               
               if( isset($col['column_default']) )
                  $aux->addChild("defecto", $col['column_default']);
            }
         }
         
         if($restricciones)
         {
            foreach($restricciones as $col)
            {
               $aux = $this->archivo_xml->addChild("restriccion");
               $aux->addChild("nombre", $col['restriccion']);
               $aux->addChild("consulta", "");
            }
         }
      }
      header( "content-type: application/xml; charset=UTF-8" );
      echo $this->archivo_xml->asXML();
   }
}
?>
