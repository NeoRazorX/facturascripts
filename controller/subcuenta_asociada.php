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

require_model('ejercicio.php');
require_model('subcuenta_cliente.php');
require_model('subcuenta_proveedor.php');

class subcuenta_asociada extends fs_controller
{
   public $tipo;
   public $cliente;
   public $proveedor;
   public $subcuenta;
   public $subcuenta_a;
   public $resultados;
   
   public function __construct() {
      parent::__construct(__CLASS__, 'Asignar subcuenta...', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->tipo = FALSE;
      
      if( isset($_POST['ejercicio']) AND isset($_POST['query']) )
      {
         $this->new_search();
      }
      else if( isset($_GET['cli']) )
      {
         $this->tipo = 'cli';
         $cliente = new cliente();
         $this->cliente = $cliente->get($_GET['cli']);
         $subcuenta_cliente = new subcuenta_cliente();
         $this->subcuenta_a = $subcuenta_cliente->get($_GET['cli'], $_GET['idsc']);
         $this->subcuenta = $this->subcuenta_a->get_subcuenta();
      }
      else if( isset($_GET['pro']) )
      {
         $this->tipo = 'pro';
      }
   }
   
   private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/subcuenta_asociada';
      
      $subcuenta = new subcuenta();
      $this->resultados = $subcuenta->search_by_ejercicio($_POST['ejercicio'], $_POST['query']);
   }
}
