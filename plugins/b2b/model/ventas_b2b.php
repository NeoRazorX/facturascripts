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
require_model('pedido_cliente.php');
require_model('presupuesto_cliente.php');
require_model('albaran_cliente.php');
require_model('factura_cliente.php');

class ventas_b2b extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Ventas B2B', 'B2B', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('ventas_presupuestos');
      
      /**
       * Comprobamos si el usuario tiene acceso a nueva_venta,
       * necesario para poder añadir líneas.
       */
      if( $this->user->have_access_to('ventas_b2b', FALSE) )
      {
         $nuevoprep = $this->page->get('ventas_b2b');
      }
   }
}
