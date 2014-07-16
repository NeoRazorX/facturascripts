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

class presupuestos_cliente extends fs_controller
{
   public $presupuesto;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Presupuestos cliente', 'ventas');
   }
   
   protected function process()
   {
      if(isset($_GET['opcion']))
      {
          if($_GET['opcion']=="nuevo")
          {
              $this->template = 'nuevo_presupuesto';
          }
      }
      else
      {
        $this->presupuesto = new presupuesto_cliente();
      
        $npage = $this->page->get('presupuestos_cliente');
        if($npage)
         $this->buttons[] = new fs_button_img('b_nuevo_presupuesto', 'Nuevo', 'add.png', $npage->url().'&opcion=nuevo');
      }
      
      }
   
}
