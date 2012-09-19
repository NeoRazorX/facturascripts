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

require_once 'model/articulo.php';
require_once 'model/familia.php';
require_once 'model/impuesto.php';
require_once 'model/tarifa.php';

class general_articulos extends fs_controller
{
   public $familia;
   public $impuesto;
   public $offset;
   public $resultados;
   public $tarifa;

   public function __construct()
   {
      parent::__construct('general_articulos', 'Artículos', 'general', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->tarifa = new tarifa();
      $articulo = new articulo();
      
      $this->custom_search = TRUE;
      $this->buttons[] = new fs_button('b_nuevo_articulo','nuevo');
      $this->buttons[] = new fs_button('b_tarifas','tarifas', '#', 'button', 'img/zoom.png');
      $this->buttons[] = new fs_button('b_modificar_iva','modificar iva', '#', 'button', 'img/tools.png');
      
      if( isset($_POST['codtarifa']) )
      {
         $tar0 = $this->tarifa->get($_POST['codtarifa']);
         if( !$tar0 )
         {
            $tar0 = new tarifa();
            $tar0->codtarifa = $_POST['codtarifa'];
         }
         $tar0->nombre = $_POST['nombre'];
         $tar0->incporcentual = floatval( $_POST['incporcentual'] );
         if( $tar0->save() )
            $this->new_message("Tarifa guardada correctamente.");
         else
            $this->new_error_msg("¡Imposible modificar la tarifa!");
      }
      else if( isset($_GET['delete_tarifa']) )
      {
          $tar0 = $this->tarifa->get($_GET['delete_tarifa']);
          if($tar0)
          {
             if( $tar0->delete() )
                $this->new_message("Tarifa borrada correctamente.");
             else
                $this->new_error_msg("¡Imposible borrar la tarifa!");
          }
          else
             $this->new_error_msg("¡La tarifa no existe!");
      }
      else if( isset($_POST['mod_iva']) )
      {
         if($_POST['codimpuesto'] == $_POST['codimpuesto2'])
            $this->new_error_msg("¡Has seleccionado el mismo IVA dos veces!");
         else if( $articulo->move_codimpuesto($_POST['codimpuesto'], $_POST['codimpuesto2'], isset($_POST['mantener'])) )
            $this->new_message("Artículos modificados correctamente.");
         else
            $this->new_error_msg("¡Impodible modificar los artículos!");
      }
      else if(isset($_POST['referencia']) AND isset($_POST['codfamilia']) AND isset($_POST['codimpuesto']))
      {
         if( $articulo->set_referencia($_POST['referencia']) )
         {
            $articulo->descripcion = $_POST['referencia'];
            $articulo->codfamilia = $_POST['codfamilia'];
            $articulo->codimpuesto = $_POST['codimpuesto'];
            if( $articulo->save() )
            {
               $imp = $this->impuesto->get($_POST['codimpuesto']);
               if($imp)
                  $imp->set_default();
               header('location: '.$articulo->url());
            }
            else
               $this->new_error_msg("¡Error al crear el articulo! ".$articulo->error_msg);
         }
         else
            $this->new_error_msg($articulo->error_msg);
      }
      else if( isset($_GET['delete']) )
      {
         $art = $articulo->get($_GET['delete']);
         if($art)
         {
            if( $art->delete() )
               $this->new_message("Articulo ".$art->referencia." eliminado correctamente.");
            else
               $this->new_error_msg("¡Error al eliminarl el articulo! ".$art->error_msg);
         }
      }
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      if($this->query != '')
         $this->resultados = $articulo->search($this->query, $this->offset);
      else
         $this->resultados = $articulo->all($this->offset);
   }
   
   public function version() {
      return parent::version().'-2';
   }
   
   public function anterior_url()
   {
      $url = '';
      if($this->query!='' AND $this->offset>'0')
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT);
      else if($this->query=='' AND $this->offset>'0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT);
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      return $url;
   }
}

?>
