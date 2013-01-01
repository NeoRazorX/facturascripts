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

require_once 'model/cuenta.php';
require_once 'model/ejercicio.php';
require_once 'model/epigrafe.php';
require_once 'model/subcuenta.php';

class contabilidad_ejercicio extends fs_controller
{
   public $cuentas;
   public $ejercicio;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('contabilidad_ejercicio', 'Ejercicio', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      if( isset($_POST['codejercicio']) )
      {
         $this->ejercicio = new ejercicio();
         $this->ejercicio = $this->ejercicio->get($_POST['codejercicio']);
         if($this->ejercicio)
         {
            $this->ejercicio->nombre = $_POST['nombre'];
            $this->ejercicio->fechainicio = $_POST['fechainicio'];
            $this->ejercicio->fechafin = $_POST['fechafin'];
            $this->ejercicio->estado = $_POST['estado'];
            if( $this->ejercicio->save() )
               $this->new_message('Datos guardados correctamente.');
            else
               $this->new_error_msg('Imposible guardar los datos.');
         }
      }
      else if( isset($_GET['cod']) )
      {
         $this->ejercicio = new ejercicio();
         $this->ejercicio = $this->ejercicio->get($_GET['cod']);
      }
      else
         $this->ejercicio = FALSE;
      
      if($this->ejercicio)
      {
         if( isset($_GET['export']) )
            $this->exportar_xml();
         else
         {
            $this->ppage = $this->page->get('contabilidad_ejercicios');
            $this->page->title = $this->ejercicio->codejercicio.' ('.$this->ejercicio->nombre.')';
            $this->buttons[] = new fs_button('b_export', 'exportar',
                    $this->url().'&export=TRUE', '', 'img/tools.png', '*', TRUE);
            
            if( isset($_GET['offset']) )
               $this->offset = intval($_GET['offset']);
            else
               $this->offset = 0;
            
            $cuenta = new cuenta();
            $this->cuentas = $cuenta->all_from_ejercicio($this->ejercicio->codejercicio, $this->offset);
         }
      }
      else
         $this->new_error_msg('Ejercicio no encontrado.');
   }
   
   public function version()
   {
      return parent::version().'-1';
   }
   
   public function url()
   {
      if( $this->ejercicio )
         return $this->ejercicio->url();
      else
         return parent::url();
   }
   
   public function anterior_url()
   {
      if($this->offset > 0)
         return $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      else
         return '';
   }
   
   public function siguiente_url()
   {
      if(count($this->cuentas) == FS_ITEM_LIMIT)
         return $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      else
         return '';
   }
   
   private function exportar_xml()
   {
      /// desactivamos el motor de plantillas
      $this->template = FALSE;
      
      /// creamos el xml
      $cadena_xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<!--
    Document   : ejercicio_".$this->ejercicio->codejercicio.".xml
    Description:
        Estructura de grupos de epígrafes, epígrafes, cuentas y subcuentas del ejercicio ".
      $this->ejercicio->codejercicio.".
-->

<ejercicio>
</ejercicio>\n";
      $archivo_xml = simplexml_load_string($cadena_xml);
      
      /// añadimos los grupos de epigrafes
      $grupo_epigrafes = new grupo_epigrafes();
      $grupos_ep = $grupo_epigrafes->all_from_ejercicio($this->ejercicio->codejercicio);
      foreach($grupos_ep as $ge)
      {
         $aux = $archivo_xml->addChild("grupo_epigrafes");
         $aux->addChild("codgrupo", $ge->codgrupo);
         $aux->addChild("descripcion", base64_encode($ge->descripcion) );
      }
      
      /// añadimos los epigrafes
      $epigrafe = new epigrafe();
      foreach($epigrafe->all_from_ejercicio($this->ejercicio->codejercicio) as $ep)
      {
         $aux = $archivo_xml->addChild("epigrafe");
         $aux->addChild("codepigrafe", $ep->codepigrafe);
         foreach($grupos_ep as $ge)
         {
            if($ep->idgrupo == $ge->idgrupo)
            {
               $aux->addChild("codgrupo", $ge->codgrupo);
               break;
            }
         }
         $aux->addChild("descripcion", base64_encode($ep->descripcion) );
      }
      
      /// añadimos las cuentas
      $cuenta = new cuenta();
      $num = 0;
      $cuentas = $cuenta->all_from_ejercicio($this->ejercicio->codejercicio);
      while( count($cuentas) > 0 )
      {
         foreach($cuentas as $c)
         {
            $aux = $archivo_xml->addChild("cuenta");
            $aux->addChild("codcuenta", $c->codcuenta);
            $aux->addChild("codepigrafe", $c->codepigrafe);
            $aux->addChild("descripcion", base64_encode($c->descripcion) );
         }
         unset($cuentas);
         $num += FS_ITEM_LIMIT;
         $cuentas = $cuenta->all_from_ejercicio($this->ejercicio->codejercicio, $num);
      }
      
      /// añadimos las subcuentas
      $subcuenta = new subcuenta();
      foreach($subcuenta->all_from_ejercicio($this->ejercicio->codejercicio) as $sc)
      {
         $aux = $archivo_xml->addChild("subcuenta");
         $aux->addChild("codsubcuenta", $sc->codsubcuenta);
         $aux->addChild("codcuenta", $sc->codcuenta);
         $aux->addChild("descripcion", base64_encode($sc->descripcion) );
         $aux->addChild("coddivisa", $sc->coddivisa);
      }
      
      /// volcamos el XML
      header("content-type: application/xml; charset=UTF-8");
      echo $archivo_xml->asXML();
   }
}

?>
