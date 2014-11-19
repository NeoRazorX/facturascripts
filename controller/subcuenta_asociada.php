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

require_model('cliente.php');
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
      $this->show_fs_toolbar = FALSE;
      $this->tipo = FALSE;
      $this->subcuenta = FALSE;
      $this->cuenta = new cuenta();
      
      $this->codejercicio = $this->default_items->codejercicio();
      if( isset($_POST['codejercicio']) )
      {
         $this->codejercicio = $_POST['codejercicio'];
      }
      
      /// comprobamos el ejercicio
      $ejercicio = new ejercicio();
      $eje0 = $ejercicio->get($this->codejercicio);
      if(!$eje0)
      {
         $eje0 = $ejercicio->get_by_fecha( date('d-m-Y') );
         if($eje0)
         {
            $this->codejercicio = $eje0->codejercicio;
         }
      }
      
      if( isset($_POST['ejercicio']) AND isset($_POST['query']) )
      {
         $this->new_search();
      }
      else if( isset($_REQUEST['cli']) )
      {
         $this->tipo = 'cli';
         $cliente = new cliente();
         $this->cliente = $cliente->get($_REQUEST['cli']);
         
         if($this->cliente)
         {
            $subcuenta_cliente = new subcuenta_cliente();
            
            if( isset($_GET['delete_sca']) )
            {
               $aux_sca = $subcuenta_cliente->get2($_GET['delete_sca']);
               if($aux_sca)
               {
                  if( $aux_sca->delete() )
                  {
                     $this->new_message('El cliente ya no está asocuado a esa subcuenta.');
                  }
                  else
                     $this->new_error_msg('Imposible quitar la subcuenta.');
               }
               else
                  $this->new_error_msg('Relación con la subcuenta no encontrada.');
            }
            else if( isset($_GET['idsc']) )
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
            else if( isset($_POST['idsc2']) )
            {
               $subc = new subcuenta();
               $subc0 = $subc->get($_POST['idsc2']);
               if($subc0)
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
                  $this->new_error_msg('Subcuenta no encontrada.');
               }
            }
            else if( isset($_POST['cuenta']) ) /// crear y asignar subcuenta
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
            else
            {
               foreach($subcuenta_cliente->all_from_cliente($_REQUEST['cli']) as $sca)
               {
                  if($sca->codejercicio == $this->codejercicio)
                  {
                     $this->subcuenta_a = $sca;
                     $this->subcuenta = $sca->get_subcuenta();
                     break;
                  }
               }
            }
         }
      }
      else if( isset($_REQUEST['pro']) )
      {
         $this->tipo = 'pro';
         $proveedor = new proveedor();
         $this->proveedor = $proveedor->get($_REQUEST['pro']);
         
         if($this->proveedor)
         {
            $subcuenta_proveedor = new subcuenta_proveedor();
            
            if( isset($_GET['delete_sca']) )
            {
               $aux_sca = $subcuenta_proveedor->get2($_GET['delete_sca']);
               if($aux_sca)
               {
                  if( $aux_sca->delete() )
                  {
                     $this->new_message('El proveedor ya no está asocuado a esa subcuenta.');
                  }
                  else
                     $this->new_error_msg('Imposible quitar la subcuenta.');
               }
               else
                  $this->new_error_msg('Relación con la subcuenta no encontrada.');
            }
            else if( isset($_GET['idsc']) )
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
            else if( isset($_POST['idsc2']) )
            {
               $subc = new subcuenta();
               $subc0 = $subc->get($_POST['idsc2']);
               if($subc0)
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
                     $this->new_error_msg('Imposible asignar la subcuenta al cliente.');
                  }
                  
                  $this->subcuenta = $subc0;
               }
               else
               {
                  $this->new_error_msg('Subcuenta no encontrada.');
               }
            }
            else if( isset($_POST['cuenta']) ) /// crear y asignar subcuenta
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
            else
            {
               foreach($subcuenta_proveedor->all_from_proveedor($_REQUEST['pro']) as $sca)
               {
                  if($sca->codejercicio == $this->codejercicio)
                  {
                     $this->subcuenta_a = $sca;
                     $this->subcuenta = $sca->get_subcuenta();
                     break;
                  }
               }
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
   
   public function url()
   {
      if( isset($_REQUEST['cli']) )
      {
         return 'index.php?page='.__CLASS__.'&cli='.$_REQUEST['cli'];
      }
      else if( isset($_REQUEST['pro']) )
      {
         return 'index.php?page='.__CLASS__.'&pro='.$_REQUEST['pro'];
      }
      else
         return 'index.php?page='.__CLASS__;
   }
}
