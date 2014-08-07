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

require_model('cuenta.php');
require_model('ejercicio.php');
require_model('subcuenta_cliente.php');
require_model('subcuenta_proveedor.php');
require_model('subcuenta.php');

class subcuenta_asociada extends fs_controller
{
   public $tipo;
   public $cliente;
   public $codejercicio;
   public $cuenta;
   public $proveedor;
   public $subcuenta;
   public $subcuenta_a;
   public $resultados;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Asignar subcuenta...', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->tipo = FALSE;
      $this->subcuenta = FALSE;
      $this->cuenta = new cuenta();
      
      $this->codejercicio = $this->default_items->codejercicio();
      if( isset($_POST['codejercicio']) )
         $this->codejercicio = $_POST['codejercicio'];
      
      if( isset($_POST['ejercicio']) AND isset($_POST['query']) )
      {
         $this->new_search();
      }
      else if( isset($_GET['cli']) OR isset($_POST['cli']) )
      {
         $this->tipo = 'cli';
         $cliente = new cliente();
         
         if( isset($_GET['cli']) )
            $this->cliente = $cliente->get($_GET['cli']);
         else
            $this->cliente = $cliente->get($_POST['cli']);
         
         if($this->cliente)
         {
            $this->ppage = $this->page->get('ventas_cliente');
            $this->ppage->title = 'Volver al cliente';
            $this->ppage->extra_url = '&cod='.$this->cliente->codcliente.'#subcuentas';
            
            $subcuenta_cliente = new subcuenta_cliente();
            
            if( isset($_GET['idsc']) )
            {
               $this->subcuenta_a = $subcuenta_cliente->get($_GET['cli'], $_GET['idsc']);
               if($this->subcuenta_a)
               {
                  $this->subcuenta = $this->subcuenta_a->get_subcuenta();
                  $this->codejercicio = $this->subcuenta_a->codejercicio;
               }
            }
            else if( isset($_POST['idsc']) )
            {
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
                        $this->new_error_msg('Imposible asignar la subcuenta al cliente.');
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
            else if( isset($_POST['cuenta']) )
            {
               $cuenta0 = $this->cuenta->get($_POST['cuenta']);
               if($cuenta0)
               {
                  $subc0 = new subcuenta();
                  $subc0->codcuenta = $cuenta0->codcuenta;
                  $subc0->coddivisa = $this->default_items->coddivisa();
                  $subc0->codejercicio = $cuenta0->codejercicio;
                  $subc0->codsubcuenta = $_POST['codsubcuenta'];
                  $subc0->descripcion = $this->cliente->nombre;
                  $subc0->idcuenta = $cuenta0->idcuenta;
                  if( $subc0->save() )
                  {
                     $subcuenta_cliente->codcliente = $this->cliente->codcliente;
                     $subcuenta_cliente->idsubcuenta = $subc0->idsubcuenta;
                     $subcuenta_cliente->codsubcuenta = $subc0->codsubcuenta;
                     $subcuenta_cliente->codejercicio = $subc0->codejercicio;
                     if( $subcuenta_cliente->save() )
                     {
                        $this->new_message('Datos guardados correctamente.');
                     }
                     else
                     {
                        $this->new_error_msg('Imposible asignar la subcuenta al cliente.');
                     }
                     
                     $this->subcuenta = $subc0;
                  }
                  else
                  {
                     $this->new_error_msg('Imposible crear la sucuenta.');
                  }
               }
               else
                  $this->new_error_msg('Cuenta no encontrada.');
            }
         }
      }
      else if( isset($_GET['pro']) OR isset($_POST['pro']) )
      {
         $this->tipo = 'pro';
         $proveedor = new proveedor();
         
         if( isset($_GET['pro']) )
            $this->proveedor = $proveedor->get($_GET['pro']);
         else
            $this->proveedor = $proveedor->get($_POST['pro']);
         
         if($this->proveedor)
         {
            $this->ppage = $this->page->get('compras_proveedor');
            $this->ppage->title = 'Volver al proveedor';
            $this->ppage->extra_url = '&cod='.$this->proveedor->codproveedor.'#subcuentas';
            
            $subcuenta_proveedor = new subcuenta_proveedor();
            
            if( isset($_GET['idsc']) )
            {
               $this->subcuenta_a = $subcuenta_proveedor->get($_GET['pro'], $_GET['idsc']);
               if($this->subcuenta_a)
               {
                  $this->subcuenta = $this->subcuenta_a->get_subcuenta();
                  $this->codejercicio = $this->subcuenta_a->codejercicio;
               }
            }
            else if( isset($_POST['idsc']) )
            {
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
                        $this->new_error_msg('Imposible asignar la subcuenta al proveedor.');
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
            else if( isset($_POST['cuenta']) )
            {
               $cuenta0 = $this->cuenta->get($_POST['cuenta']);
               if($cuenta0)
               {
                  $subc0 = new subcuenta();
                  $subc0->codcuenta = $cuenta0->codcuenta;
                  $subc0->coddivisa = $this->default_items->coddivisa();
                  $subc0->codejercicio = $cuenta0->codejercicio;
                  $subc0->codsubcuenta = $_POST['codsubcuenta'];
                  $subc0->descripcion = $this->proveedor->nombre;
                  $subc0->idcuenta = $cuenta0->idcuenta;
                  if( $subc0->save() )
                  {
                     $subcuenta_proveedor->codproveedor = $this->proveedor->codproveedor;
                     $subcuenta_proveedor->idsubcuenta = $subc0->idsubcuenta;
                     $subcuenta_proveedor->codsubcuenta = $subc0->codsubcuenta;
                     $subcuenta_proveedor->codejercicio = $subc0->codejercicio;
                     if( $subcuenta_proveedor->save() )
                     {
                        $this->new_message('Datos guardados correctamente.');
                     }
                     else
                     {
                        $this->new_error_msg('Imposible asignar la subcuenta al proveedor.');
                     }
                     
                     $this->subcuenta = $subc0;
                  }
                  else
                  {
                     $this->new_error_msg('Imposible crear la sucuenta.');
                  }
               }
               else
                  $this->new_error_msg('Cuenta no encontrada.');
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
