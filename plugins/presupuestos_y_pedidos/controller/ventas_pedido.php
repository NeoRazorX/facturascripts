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

require_model('pedido_cliente.php');
require_model('linea_pedido_cliente.php');

class ventas_pedido extends fs_controller
{
   public $pedido;
   public $lineas;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Pedido...', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('pedidos_cliente');
      $this->pedido = FALSE;
      $this->lineas = array();
      
      if( isset($_GET['id']) )
      {
         $pedido = new pedido_cliente();
         $this->pedido = $pedido->get($_GET['id']);
      }
      
      if($this->pedido)
      {
         $this->page->title = $this->pedido->codigo;
         
         $linea = new linea_pedido_cliente();
         $this->lineas = $linea->all_from_pedido($_GET['id']);
      }
      else
         $this->new_error_msg('Pedido no encontrado.');
   }
   
   public function url()
   {
      if( !isset($this->pedido) )
         return parent::url();
      else if($this->pedido)
         return $this->pedido->url();
      else
         return parent::url();
   }
}