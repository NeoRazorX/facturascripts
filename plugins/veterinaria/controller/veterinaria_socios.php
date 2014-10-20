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
require_model('factura_cliente.php');
require_model('fbm_socio.php');
require_model('fbm_pago_socio.php');
require_model('forma_pago.php');

class veterinaria_socios extends fs_controller
{
   public $forma_pago;
   public $resultados;
   public $socio;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Socios', 'Veterinaria');
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      $this->forma_pago = new forma_pago();
      $this->socio = new fbm_socio();
      
      if( isset($_POST['cifnif']) )
      {
         $this->nuevo_socio();
      }
      else if( isset($_GET['delete']) )
      {
         $socio = new fbm_socio();
         $socio2 = $socio->get($_GET['delete']);
         if($socio2)
         {
            if( $socio2->delete() )
            {
               $this->new_message('Socio eliminado correctamente.');
            }
            else
               $this->new_error_msg('Imposible eliminar al socio.');
         }
         else
            $this->new_error_msg('Socio no encontrado');
      }
      
      $this->resultados = $this->socio->all();
   }
   
   private function nuevo_socio()
   {
      $cliente0 = new cliente();
      $cliente = FALSE;
      
      $continuar = FALSE;
      foreach($cliente0->search_by_dni($_POST['cifnif']) as $cli)
      {
         $cliente = $cli;
         $continuar = TRUE;
         break;
      }
      
      if(!$cliente)
      {
         $cliente = new cliente();
         $cliente->codcliente = $cliente->get_new_codigo();
         $cliente->nombre = $_POST['nombre'];
         $cliente->nombrecomercial = $_POST['nombre'];
         $cliente->cifnif = $_POST['cifnif'];
         $cliente->codserie = $this->empresa->codserie;
         $cliente->codpago = $_POST['forma_pago'];
         if( $cliente->save() )
         {
            $dircliente = new direccion_cliente();
            $dircliente->codcliente = $cliente->codcliente;
            $dircliente->codpais = $this->empresa->codpais;;
            $dircliente->provincia = $_POST['provincia'];
            $dircliente->ciudad = $_POST['ciudad'];
            $dircliente->codpostal = $_POST['codpostal'];
            $dircliente->direccion = $_POST['direccion'];
            $dircliente->descripcion = 'Principal';
            if( $dircliente->save() )
            {
               $continuar = TRUE;
            }
            else
               $this->new_error_msg("¡Imposible guardar la dirección del cliente!");
         }
         else
            $this->new_error_msg("¡Imposible guardar los datos del cliente!");
      }
      
      if($continuar)
      {
         $this->socio->codcliente = $cliente->codcliente;
         $this->socio->cuota = floatval($_POST['cuota']);
         $this->socio->periodicidad = $_POST['periodicidad'];
         if( $this->socio->save() )
         {
            $this->new_message('Socio creado correctamente.');
            
            if($cliente->codpago == 'REME')
            {
               $this->socio->ultimo_pago = NULL;
               $this->socio->proximo_pago = Date('1-m-Y', strtotime('+1 month'));
               $this->socio->save();
               
               header('Location: '.$this->socio->url());
            }
            else
            {
               $pago = new fbm_pago_socio();
               $pago->idsocio = $this->socio->idsocio;
               $pago->fecha = Date('d-m-Y');
               $pago->cuota = $this->socio->cuota;
               $pago->codpago = $cliente->codpago;
               $pago->pagado = TRUE;
               
               if( $pago->save() )
               {
                  $this->socio->ultimo_pago = $pago->fecha;
                  $this->socio->al_corriente = TRUE;
                  $this->socio->proximo_pago = $this->socio->fecha_proximo_pago();
                  $this->socio->save();
                  
                  $this->nueva_factura($cliente, $pago);
               }
               else
                  $this->new_error_msg("¡Imposible guardar el pago!");
            }
         }
         else
            $this->new_error_msg("¡Imposible crear el socio!");
      }
   }
   
   private function nueva_factura($cliente, $pago)
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
         $factura->pagada = TRUE;
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
               
               header('Location: '.$this->socio->url());
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