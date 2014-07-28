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

require_model('balance.php');

class contabilidad_balance extends fs_controller
{
   public $balance;
   public $balances_cuenta;
   public $balances_cuenta_a;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Balance', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      if( isset($_GET['cod']) )
      {
         $balance = new balance();
         $this->balance = $balance->get($_GET['cod']);
      }
      else
         $this->balance = FALSE;
      
      if($this->balance)
      {
         $this->ppage = $this->page->get('contabilidad_balances');
         $this->page->title = $this->balance->codbalance;
         
         $bc = new balance_cuenta();
         $this->balances_cuenta = $bc->all_from_codbalance( $this->balance->codbalance );
         $bca = new balance_cuenta_a();
         $this->balances_cuenta_a = $bca->all_from_codbalance( $this->balance->codbalance );
      }
      else
         $this->new_error_msg('Balance no encontrado.');
   }
   
   public function url()
   {
      if( !isset($this->balance) )
         return parent::url();
      else if($this->balance)
         return $this->balance->url();
      else
         return parent::url();
   }
}
