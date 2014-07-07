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
require_model('linea_presupuesto_cliente.php');

class ver_presupuesto_cli extends fs_controller
{
   public $presupuesto;
   public $lineas;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Presupuesto', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('presupuestos_cliente');
      $this->presupuesto = FALSE;
      $this->lineas = FALSE;
      
      if( isset($_GET['id']) )
      {
         $presupuesto = new presupuesto_cliente();
         $this->presupuesto = $presupuesto->get($_GET['id']);
      }
      
      if($this->presupuesto)
      {
         $this->page->title = $this->presupuesto->codigo;
         
         $linea = new linea_presupuesto_cliente();
         $this->lineas = $linea->all_from_presupuesto($_GET['id']);
      }
   }
   
   public function url()
   {
      if( !isset($this->presupuesto) )
         return parent::url();
      else if($this->presupuesto)
         return $this->presupuesto->url();
      else
         return parent::url();
   }
}