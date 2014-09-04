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

require_model('cuenta_especial.php');

class plan_contable extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'nuevo plan contable', 'contabilidad');
   }
   
   protected function process()
   {
      if( isset($_POST['archivo']) )
      {
         $this->check($_FILES['farchivo']['tmp_name'], $_FILES['farchivo']['name']);
      }
   }
   
   private function check($file_loc, $file_name = 'nuevo')
   {
      $file = fopen($file_loc, 'r');
      if($file)
      {
         /// desactivamos el motor de plantillas
         $this->template = FALSE;
         
         /// creamos el xml
         $cadena_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!--
    Document   : ".$file_name.".xml
    Description:
        Estructura de grupos de epígrafes, epígrafes, cuentas y subcuentas.
-->

<ejercicio>
</ejercicio>\n";
         $archivo_xml = simplexml_load_string($cadena_xml);
         
         /// añadimos las cuentas especiales
         $cuenta_esp = new cuenta_especial();
         foreach($cuenta_esp->all() as $ce)
         {
            $child = $archivo_xml->addChild("cuenta_especial");
            $child->addChild("idcuentaesp", $ce->idcuentaesp);
            $child->addChild("descripcion", base64_encode($ce->descripcion) );
         }
         
         /// comprobamos las longitudes de los códigos
         $longitudes = array();
         while( !feof($file) )
         {
            $line = trim(fgets($file));
            $aux = explode(';', $line);
            if( count($aux) == 2 )
            {
               if( !isset($longitudes[strlen($aux[0])]) )
               {
                  $longitudes[strlen($aux[0])] = strlen($aux[0]);
               }
            }
         }
         
         /// ordenamos las longitudes
         sort($longitudes);
         
         /// volvemos a leer
         rewind($file);
         $last_ge = '';
         $last_e = '';
         $last_c = '';
         while( !feof($file) )
         {
            $line = trim(fgets($file));
            $aux = explode(';', $line);
            if( count($aux) == 2 )
            {
               if( strlen($aux[0]) == $longitudes[0] )
               {
                  $child = $archivo_xml->addChild("grupo_epigrafes");
                  $child->addChild("codgrupo", $aux[0]);
                  $child->addChild("descripcion", base64_encode($aux[1]) );
                  $last_ge = $aux[0];
               }
               else if( strlen($aux[0]) == $longitudes[1] )
               {
                  $child = $archivo_xml->addChild("epigrafe");
                  $child->addChild("codgrupo", $last_ge);
                  $child->addChild("codepigrafe", $aux[0]);
                  $child->addChild("descripcion", base64_encode($aux[1]) );
                  $last_e = $aux[0];
               }
               else if( count($longitudes) == 3 AND strlen($aux[0]) == $longitudes[2] )
               {
                  $child = $archivo_xml->addChild("cuenta");
                  $child->addChild("codepigrafe", $last_e);
                  $child->addChild("codcuenta", $aux[0]);
                  $child->addChild("descripcion", base64_encode($aux[1]) );
                  $child->addChild("idcuentaesp", '');
                  $last_c = $aux[0];
               }
               else if( count($longitudes) > 3 AND strlen($aux[0]) == $longitudes[ count($longitudes)-1 ] )
               {
                  $child = $archivo_xml->addChild("subcuenta");
                  $child->addChild("codcuenta", $last_c);
                  $child->addChild("codsubcuenta", $aux[0]);
                  $child->addChild("descripcion", base64_encode($aux[1]) );
                  $child->addChild("coddivisa", $this->empresa->coddivisa);
               }
               else if(strlen($aux[0]) == $longitudes[2])
               {
                  $child = $archivo_xml->addChild("cuenta");
                  $child->addChild("codepigrafe", $last_e);
                  $child->addChild("codcuenta", $aux[0]);
                  $child->addChild("descripcion", base64_encode($aux[1]) );
                  $child->addChild("idcuentaesp", '');
                  $last_c = $aux[0];
               }
            }
         }
         
         fclose($file);
         
         /// volcamos el XML
         header("content-type: application/xml; charset=UTF-8");
         header('Content-Disposition: attachment; filename="ejercicio_'.$file_name.'.xml"');
         echo $archivo_xml->asXML();
      }
   }
}