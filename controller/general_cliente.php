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

require_once 'model/cliente.php';

class general_cliente extends fs_controller
{
   public $cliente;
   
   public function __construct()
   {
      parent::__construct('general_cliente', 'Cliente', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_clientes');
      
      if(isset($_GET['cod']))
      {
         $this->cliente = new cliente();
         $this->cliente = $this->cliente->get($_GET['cod']);
         $this->page->title = $_GET['cod'];
      }
   }
   
   public function url()
   {
      if($this->cliente)
         return $this->cliente->url();
      else
         return $this->page->url();
   }
}

?>
