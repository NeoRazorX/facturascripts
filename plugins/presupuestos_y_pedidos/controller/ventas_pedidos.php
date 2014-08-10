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

require_model('pedido_cliente.php');

class ventas_pedidos extends fs_controller
{
   public $buscar_lineas;
   public $lineas;
   public $resultados;
   public $offset;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Pedido de cliente', 'ventas', FALSE, TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_POST['buscar_lineas']) )
      {
         $this->buscar_lineas();
      }
      else
      {
         $pedido = new pedido_cliente();
         $this->custom_search = TRUE;
         
         $this->buttons[] = new fs_button('b_nuevo_pedido', 'Nuevo', 'index.php?page=nueva_venta&tipo=pedido');
         $this->buttons[] = new fs_button('b_agrupar_pedidos', 'Agrupar', 'index.php?page=ventas_agrupar_pedidos');
         $this->buttons[] = new fs_button('b_buscar_lineas', 'Lineas');
         
         if( !isset($_GET['ptealbaran']) )
         {
            $this->buttons[] = new fs_button('b_pendientes', 'Pendientes', $this->url()."&amp;ptealbaran=TRUE");
         }
         
         if( isset($_POST['delete']) )
         {
            $this->delete_pedido();
         }
         
         $this->offset = 0;
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         
         if($this->query)
         {
            $this->resultados = $pedido->search($this->query, $this->offset);
         }
         else if( isset($_GET['ptealbaran']) )
         {
            $this->new_advice('Estos son los pedidos pendientes de pasar a {$albaran}. Haz clic <a href="'.$this->url().
                 '">aquí</a> para volver a la vista normal.');
            $this->resultados = $pedido->all_ptealbaran($this->offset);
         }
         else
            $this->resultados = $pedido->all($this->offset);
      }
   }
   
   public function anterior_url()
   {
      $url = '';
      $extra = '';
      
      if( isset($_GET['ptealbaran']) )
         $extra = '&ptealbaran=TRUE';
      
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
      
      if( isset($_GET['ptealbaran']) )
         $extra = '&ptealbaran=TRUE';
      
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT).$extra;
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT).$extra;
      
      return $url;
   }
   
   public function buscar_lineas()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/ventas_lineas_pedidos';
      
      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_pedido_cliente();
      $this->lineas = $linea->search($this->buscar_lineas);
   }
   
   private function delete_pedido()
   {
      $ped1 = new pedido_cliente();
      $ped1 = $ped->get($_POST['delete']);
      if($ped1)
      {
         /// ¿Actualizamos el stock de los artículos?
         if( isset($_POST['stock']) )
         {
            $articulo = new articulo();
            
            foreach($ped1->get_lineas() as $linea)
            {
               $art0 = $articulo->get($linea->referencia);
               if($art0)
               {
                  $art0->sum_stock($ped->codalmacen, $linea->cantidad);
                  $art0->save();
               }
            }
         }
         
         if( $ped1->delete() )
            $this->new_message("Pedido ".$ped1->codigo." borrado correctamente.");
         else
            $this->new_error_msg("¡Imposible borrar el pedido!");
      }
      else
         $this->new_error_msg("¡Pedido no encontrado!");
   }
}
