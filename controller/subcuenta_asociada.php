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
require_model('subcuenta.php');

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
      $this->subcuenta = FALSE;
      
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
         if($this->subcuenta_a)
            $this->subcuenta = $this->subcuenta_a->get_subcuenta();
      }
      else if( isset($_POST['cli']) )
      {
         $this->tipo = 'cli';
         $cliente = new cliente();
         $this->cliente = $cliente->get($_POST['cli']);
         $subcuenta_cliente = new subcuenta_cliente();
         $this->subcuenta_a = $subcuenta_cliente->get($_POST['cli'], $_POST['idsc']);
         if($this->subcuenta_a)
         {
            $subc = new subcuenta();
            $subc0 = $subc->get($_POST['idsc2']);
            if($subc0)
            {
               $this->subcuenta_a->idsubcuenta = $subc0->idsubcuenta;
               $this->subcuenta_a->codsubcuenta = $subc0->codsubcuenta;
               $this->subcuenta_a->codejercicio = $subc0->codejercicio;
               if( $this->subcuenta_a->save() )
               {
                  $this->new_message('Datos guardados correctamente.');
               }
               else
               {
                  $this->new_error_msg('Imposible guardar la subcuenta.');
               }
               
               $this->subcuenta = $subc0;
            }
            else
            {
               $this->new_error_msg('Subcuenta no encontrada.');
               $this->subcuenta = $this->subcuenta_a->get_subcuenta();
            }
         }
      }
      else if( isset($_GET['pro']) )
      {
         $this->tipo = 'pro';
         $proveedor = new proveedor();
         $this->proveedor = $proveedor->get($_GET['pro']);
         $subcuenta_proveedor = new subcuenta_proveedor();
         $this->subcuenta_a = $subcuenta_proveedor->get($_GET['pro'], $_GET['idsc']);
         if($this->subcuenta_a)
            $this->subcuenta = $this->subcuenta_a->get_subcuenta();
      }
      else if( isset($_POST['pro']) )
      {
         $this->tipo = 'pro';
         $proveedor = new proveedor();
         $this->proveedor = $proveedor->get($_POST['pro']);
         $subcuenta_proveedor = new subcuenta_proveedor();
         $this->subcuenta_a = $subcuenta_proveedor->get($_POST['pro'], $_POST['idsc']);
         if($this->subcuenta_a)
         {
            $subc = new subcuenta();
            $subc0 = $subc->get($_POST['idsc2']);
            if($subc0)
            {
               $this->subcuenta_a->idsubcuenta = $subc0->idsubcuenta;
               $this->subcuenta_a->codsubcuenta = $subc0->codsubcuenta;
               $this->subcuenta_a->codejercicio = $subc0->codejercicio;
               if( $this->subcuenta_a->save() )
               {
                  $this->new_message('Datos guardados correctamente.');
               }
               else
               {
                  $this->new_error_msg('Imposible guardar la subcuenta.');
               }
               
               $this->subcuenta = $subc0;
            }
            else
            {
               $this->new_error_msg('Subcuenta no encontrada.');
               $this->subcuenta = $this->subcuenta_a->get_subcuenta();
            }
         }
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
