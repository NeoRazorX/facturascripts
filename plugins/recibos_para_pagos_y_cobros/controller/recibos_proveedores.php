<?php
/*
 * This file is part of FacturaScripts
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

// Saber quien gestiona el pago
require_model('agente.php');
// Saber quien cobra
require_model('proveedor.php');
// Saber que documento se paga
require_model('factura_proveedor.php');
// Saber que recibo se paga
require_model('recibo_proveedor.php');

class recibos_proveedores extends fs_controller
{
   public $factura;
   public $proveedor;
   public $recibo;
   public $resultados;
   public $total;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Recibos de proveedores', 'tesoreria', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('compras_facturas');
      $this->factura = new factura_proveedor();
      $this->proveedor = new proveedor();
      $this->recibo = new recibo_proveedor();
      $this->serie = new serie();
      $this->total = 0;
      
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      
      if( isset($_POST['proveedor']) )
      {
         $this->save_codproveedor($_POST['proveedor']);
         
         $this->resultados = $this->factura->all_from_proveedor($_POST['proveedor']);
         
         if($this->resultados)
         {
            foreach($this->resultados as $fac)
            {
               $this->total += $fac->total;
            }
         }
         else
            $this->new_message("Sin resultados.");
      }
   }
   
   public function anterior_url()
   {
      
   }
   
   public function siguiente_url()
   {
      
   }
   
   private function share_extension()
   {
      
   }
}
