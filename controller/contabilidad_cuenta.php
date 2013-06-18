<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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

class contabilidad_cuenta extends fs_controller
{
   public $cuenta;
   public $ejercicio;
   
   public function __construct()
   {
      parent::__construct('contabilidad_cuenta', 'Cuenta', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('contabilidad_cuentas');
      
      if( isset($_GET['id']) )
      {
         $this->cuenta = new cuenta();
         $this->cuenta = $this->cuenta->get($_GET['id']);
      }
      
      if($this->cuenta)
      {
         $this->page->title = 'Cuenta: '.$this->cuenta->codcuenta;
         $this->ejercicio = $this->cuenta->get_ejercicio();
      }
      else
         $this->new_error_msg("Cuenta no encontrada.");
   }
   
   public function version()
   {
      return parent::version().'-2';
   }
   
   public function url()
   {
      if( !isset($this->cuenta) )
         return parent::url();
      else if($this->cuenta)
         return $this->cuenta->url();
      else
         return $this->page->url();
   }
}

?>