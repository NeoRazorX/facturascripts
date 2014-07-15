<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2014  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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

require_model('presupuesto_proveedor.php');
require_model('articulo.php');

class general_presupuestos_prov extends fs_controller
{
   public $buscar_lineas;
   public $lineas;
   public $resultados;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('general_presupuestos_prov', 'Presupuestos de proveedor', 'compras', FALSE, TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_POST['buscar_lineas']) )
      {
         $this->buscar_lineas();
      }
      else
      {
         $presupuesto = new presupuesto_proveedor();
         $this->custom_search = TRUE;
         
         $this->buttons[] = new fs_button_img('b_nuevo_presupuesto', 'Nuevo', 'add.png', 'index.php?page=general_nuevo_presupuesto');
         $this->buttons[] = new fs_button('b_agrupar_presupuestos', 'Agrupar', 'index.php?page=general_agrupar_presupuestos_pro');
         $this->buttons[] = new fs_button_img('b_buscar_lineas', 'Lineas', 'zoom.png');
         
         if( !isset($_GET['ptepedido']) )
         {
            $this->buttons[] = new fs_button('b_pendientes', 'Pendientes', $this->url()."&amp;ptepedido=TRUE");
         }
         
         if( isset($_POST['delete']) )
         {
            $this->delete_presupuesto();
         }
         
         $this->offset = 0;
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         
         if($this->query != '')
         {
            $this->resultados = $presupuesto->search($this->query, $this->offset);
         }
         else if( isset($_GET['ptepedido']) )
         {
            $this->new_advice('Estos son los presupuestos pendientes de pedidor. Haz clic <a class="link" href="'.$this->url().
                 '">aquí</a> para volver a la vista normal.');
            $this->resultados = $presupuesto->all_ptepedido($this->offset);
         }
         else
            $this->resultados = $presupuesto->all($this->offset);
      }
   }
   
   public function anterior_url()
   {
      $url = '';
      $extra = '';
      
      if( isset($_GET['ptepedido']) )
         $extra = '&ptepedido=TRUE';
      
      if($this->query!='' AND $this->offset>'0')
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT).$extra;
      else if($this->query=='' AND $this->offset>'0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT).$extra;
      
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      $extra = '';
      
      if( isset($_GET['ptepedido']) )
         $extra = '&ptepedido=TRUE';
      
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT).$extra;
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT).$extra;
      
      return $url;
   }
   
   public function buscar_lineas()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_lineas_presupuestos_prov';
      
      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_presupuesto_proveedor();
      $this->lineas = $linea->search($this->buscar_lineas);
   }
   
   private function delete_presupuesto()
   {
      $presu1 = new presupuesto_proveedor();
      $presu1 = $presu1->get($_POST['delete']);
      if($presu1)
      {
         /// ¿Actualizamos el stock de los artículos?
         if( isset($_POST['stock']) )
         {
            $articulo = new articulo();
            
            foreach($presu1->get_lineas() as $linea)
            {
               $art0 = $articulo->get($linea->referencia);
               if($art0)
               {
                  $art0->sum_stock($presu1->codalmacen, 0 - $linea->cantidad);
                  $art0->save();
               }
            }
         }
         
         if( $presu1->delete() )
            $this->new_message("Presupuesto ".$presu1->codigo." borrado correctamente.");
         else
            $this->new_error_msg("¡Imposible borrar el presupuesto!");
      }
      else
         $this->new_error_msg("¡Presupuesto no encontrado!");
   }
}

?>
