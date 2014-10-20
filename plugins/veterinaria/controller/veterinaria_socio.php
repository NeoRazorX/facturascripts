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
require_model('fbm_mascota.php');
require_model('fbm_pago_socio.php');
require_model('fbm_socio.php');
require_model('forma_pago.php');

class veterinaria_socio extends fs_controller
{
   public $cliente;
   public $forma_pago;
   public $mascotas;
   public $pagos;
   public $socio;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Socio', 'Veterinaria', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $cliente = new cliente();
      $this->cliente = FALSE;
      $socio = new fbm_socio();
      $this->socio = FALSE;
      $this->forma_pago = new forma_pago();
      
      if( isset($_REQUEST['buscar_cliente']) )
      {
         $this->buscar_cliente();
      }
      else if( isset($_REQUEST['id']) )
      {
         $this->socio = $socio->get($_REQUEST['id']);
      }
      
      if($this->socio)
      {
         $mascota = new fbm_mascota();
         $pago = new fbm_pago_socio();
         
         /// ¿Modificamos?
         if( isset($_POST['codcliente']) )
         {
            $this->socio->codcliente = $_POST['codcliente'];
            $this->socio->fecha_alta = $_POST['fecha_alta'];
            $this->socio->cuota = $_POST['cuota'];
            $this->socio->periodicidad = $_POST['periodicidad'];
            
            if( isset($_POST['de_baja']) )
            {
               $this->socio->en_alta = FALSE;
               $this->socio->fecha_baja = Date('d-m-Y');
               
               if($_POST['fecha_baja'] != '')
               {
                  $this->socio->fecha_baja = $_POST['fecha_baja'];
               }
            }
            else
            {
               $this->socio->en_alta = TRUE;
               $this->socio->fecha_baja = NULL;
            }
            
            if( $this->socio->save() )
            {
               $this->new_message('Datos guardados correctamente.');
            }
            else
               $this->new_error_msg('Imposible actualizar los datos.');
         }
         else if( isset($_POST['np_fecha']) ) /// nuevo pago
         {
            $pago->idsocio = $this->socio->idsocio;
            $pago->fecha = $_POST['np_fecha'];
            $pago->cuota = floatval($_POST['np_cuota']);
            $pago->codpago = $_POST['np_codpago'];
            $pago->pagado = isset($_POST['np_pagado']);
            
            if( $pago->save() )
            {
               $this->new_message('Pago guardado correctamente.');
               
               if(!$pago->pagado AND $this->socio->al_corriente)
               {
                  $this->socio->al_corriente = FALSE;
                  $this->socio->save();
               }
               
               if( $pago->codpago != 'REME' )
               {
                  $this->nueva_factura($pago);
               }
            }
            else
               $this->new_error_msg('Imposible guardar el pago.');
         }
         else if( isset($_GET['delete']) )
         {
            foreach($pago->all_from_socio($this->socio->idsocio) as $pg)
            {
               if($pg->id == $_GET['delete'])
               {
                  if( $pg->delete() )
                  {
                     $this->new_message('Pago eliminado correctamente.');
                  }
                  else
                     $this->new_error_msg('Imposible eliminar el pago.');
                  
                  break;
               }
            }
         }
         else if( isset($_GET['pagado']) OR isset($_GET['impagado']) )
         {
            foreach($pago->all_from_socio($this->socio->idsocio) as $pg)
            {
               if( isset($_GET['pagado']) )
               {
                  if($pg->id == $_GET['pagado'])
                  {
                     $pg->pagado = TRUE;
                     if( $pg->save() )
                     {
                        $this->new_message('Pago modificado correctamente.');
                     }
                     else
                        $this->new_error_msg('Imposible modificar el pago.');
                     
                     break;
                  }
               }
               else
               {
                  if($pg->id == $_GET['impagado'])
                  {
                     $pg->pagado = FALSE;
                     if( $pg->save() )
                     {
                        $this->new_message('Pago modificado correctamente.');
                        
                        if($this->socio->al_corriente)
                        {
                           $this->socio->al_corriente = FALSE;
                           $this->socio->save();
                        }
                     }
                     else
                        $this->new_error_msg('Imposible modificar el pago.');
                     
                     break;
                  }
               }
            }
         }
         
         $this->cliente = $cliente->get($this->socio->codcliente);
         $this->pagos = $pago->all_from_socio($this->socio->idsocio);
         $this->mascotas = $mascota->all_from_cliente($this->socio->codcliente);
      }
      else
         $this->new_error_msg('Socio no encontrado.');
   }
   
   public function url()
   {
      if( !isset($this->socio) )
      {
         return parent::url();
      }
      else if($this->socio)
      {
         return $this->socio->url();
      }
      else
         return parent::url();
   }
   
   private function buscar_cliente()
   {
      /// desactivamos la plantilla HTML
      $this->template = FALSE;
      
      $cliente = new cliente();
      $json = array();
      foreach($cliente->search($_REQUEST['buscar_cliente']) as $cli)
      {
         $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
      }
      
      header('Content-Type: application/json');
      echo json_encode( array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json) );
   }
   
   private function nueva_factura($pago)
   {
      $continuar = TRUE;
      $factura = new factura_cliente();
      $factura->codalmacen = $this->empresa->codalmacen;
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha( Date('d-m-Y') );
      if($ejercicio)
      {
         $factura->codejercicio = $ejercicio->codejercicio;
      }
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = new serie();
      $serie->codserie = 'D';
      $serie->descripcion = 'SERIE D';
      $serie->siniva = TRUE;
      if( $serie->save() )
      {
         $factura->codserie = $serie->codserie;
      }
      else
      {
         $this->new_error_msg('Série no encontrada.');
         $continuar = FALSE;
      }
      
      $factura->codpago = $pago->codpago;
      
      $divisa0 = new divisa();
      $divisa = $divisa0->get($this->empresa->coddivisa);
      if($divisa)
      {
         $factura->coddivisa = $divisa->coddivisa;
         $factura->tasaconv = $divisa->tasaconv;
      }
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $agente = $this->user->get_agente();
      if($agente)
      {
         $factura->codagente = $agente->codagente;
      }
      
      $cliente0 = new cliente();
      $cliente = $cliente0->get($this->socio->codcliente);
      foreach($cliente->get_direcciones() as $d)
      {
         if($d->domfacturacion)
         {
            $factura->codcliente = $cliente->codcliente;
            $factura->cifnif = $cliente->cifnif;
            $factura->nombrecliente = $cliente->nombrecomercial;
            $factura->apartado = $d->apartado;
            $factura->ciudad = $d->ciudad;
            $factura->coddir = $d->id;
            $factura->codpais = $d->codpais;
            $factura->codpostal = $d->codpostal;
            $factura->direccion = $d->direccion;
            $factura->provincia = $d->provincia;
            break;
         }
      }
      
      if($continuar)
      {
         $factura->pagada = $pago->pagado;
         $factura->neto = round($pago->cuota, FS_NF0);
         $factura->total = $factura->neto;
         
         if( $factura->save() )
         {
            $linea = new linea_factura_cliente();
            $linea->idfactura = $factura->idfactura;
            $linea->descripcion = 'CUOTA DE SOCIO';
            $linea->pvptotal = $linea->pvpsindto = $linea->pvpunitario = $pago->cuota;
            $linea->cantidad = 1;
            
            if( $linea->save() )
            {
               $this->new_message('Factura guardada correctamente.');
            }
            else
            {
               $this->new_error_msg("¡Imposible guardar la línea!");
            }
         }
         else
            $this->new_error_msg('Imposible guardar la factura.');
      }
   }
}