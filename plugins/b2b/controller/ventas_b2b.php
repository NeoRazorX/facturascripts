<?php
/*
 * This file is part of FacturaSctipts
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

require_model('cliente.php');
require_model('fs_extension.php');
require_model('presupuesto_cliente.php');
require_model('pedido_cliente.php');
require_model('albaran_cliente.php');
require_model('factura_cliente.php');

class ventas_b2b extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Ventas B2B', 'B2B', FALSE, TRUE, TRUE);
   }
   
   protected function process()
   {
         $this->custom_search = TRUE;
         $this->share_extension();
         
         $this->buttons[] = new fs_button('b_nuevo_presupuesto', 'Nuevo presupuesto', 'index.php?page=ventas_b2b&tipo=presupuesto');
         $this->buttons[] = new fs_button('b_nuevo_pedido', 'Nuevo pedido', 'index.php?page=ventas_b2b&tipo=pedido');
   }
   
   private function share_extension()
   {
      /// cargamos la extensión para clientes
      $fsext0 = new fs_extension();
      if( !$fsext0->get_by(__CLASS__, 'ventas_b2b') )
      {
         $fsext = new fs_extension();
         $fsext->from = __CLASS__;
         $fsext->to = 'ventas_b2b';
         $fsext->type = 'button';
         $fsext->text = 'Pedidos';
         $fsext->save();
      }
      
      if( !$fsext0->get_by(__CLASS__, 'admin_agente') )
      {
         $fsext = new fs_extension();
         $fsext->from = __CLASS__;
         $fsext->to = 'admin_agente';
         $fsext->type = 'button';
         $fsext->text = 'Pedidos de clientes';
         $fsext->save();
      }
      
      if( !$fsext0->get_by(__CLASS__, 'ventas_articulo') )
      {
         $fsext = new fs_extension();
         $fsext->from = __CLASS__;
         $fsext->to = 'ventas_articulo';
         $fsext->type = 'button';
         $fsext->text = 'Pedidos de clientes';
         $fsext->save();
      }
   }
}
