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

require_model('presupuesto_cliente.php');

class ventas_presupuestos extends fs_controller
{
   public $buscar_lineas;
   public $lineas;
   public $resultados;
   public $offset;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Presupuestos de cliente', 'ventas', FALSE, TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_POST['buscar_lineas']) )
      {
         $this->buscar_lineas();
      }
      else
      {
         $presupuesto = new presupuesto_cliente();
         $this->custom_search = TRUE;
         
         $this->buttons[] = new fs_button('b_nuevo_presupuesto', 'Nuevo', 'index.php?page=nueva_venta&tipo=presupuesto');
         $this->buttons[] = new fs_button('b_agrupar_presupuestos', 'Agrupar', 'index.php?page=ventas_agrupar_presupuestos');
         $this->buttons[] = new fs_button('b_buscar_lineas', 'Lineas');
         
         if( !isset($_GET['ptepedir']) )
         {
            $this->buttons[] = new fs_button('b_pendientes', 'Pendientes', $this->url()."&amp;ptepedir=TRUE");
         }
         
         if( isset($_POST['delete']) )
         {
            $this->delete_presupuesto();
         }
         
         $this->offset = 0;
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         
         if($this->query)
         {
            $this->resultados = $presupuesto->search($this->query, $this->offset);
         }
         else if( isset($_GET['ptepedir']) )
         {
            $this->new_advice('Estos son los presupuesto pendientes de pedir. Haz clic <a href="'.$this->url().
                 '">aquí</a> para volver a la vista normal.');
            $this->resultados = $presupuesto->all_ptepedir($this->offset);
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
      $this->template = 'ajax/ventas_lineas_presupuestos';
      
      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_presupuesto_cliente();
      $this->lineas = $linea->search($this->buscar_lineas);
   }
   
   private function delete_presupuesto()
   {
      $pre = new presupuesto_cliente();
      $pre1 = $ped->get($_POST['delete']);
      if($pre1)
      {
         /// ¿Actualizamos el stock de los artículos?
         if( isset($_POST['stock']) )
         {
            $articulo = new articulo();
            
            foreach($pre1->get_lineas() as $linea)
            {
               $art0 = $articulo->get($linea->referencia);
               if($art0)
               {
                  $art0->sum_stock($alb1->codalmacen, $linea->cantidad);
                  $art0->save();
               }
            }
         }
         
         if( $pre1->delete() )
            $this->new_message("Presupuesto ".$pre1->codigo." borrado correctamente.");
         else
            $this->new_error_msg("¡Imposible borrar el presupuesto!");
      }
      else
         $this->new_error_msg("¡Presupuesto no encontrado!");
   }
}
