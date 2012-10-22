<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'model/albaran_cliente.php';
require_once 'model/asiento.php';
require_once 'model/cliente.php';
require_once 'model/factura_cliente.php';
require_once 'model/partida.php';
require_once 'model/subcuenta.php';

class general_agrupar_albaranes_cli extends fs_controller
{
   public $albaran;
   public $cliente;
   public $desde;
   public $hasta;
   public $resultados;
   
   public function __construct() {
      parent::__construct('general_agrupar_albaranes_cli', 'Agrupar albaranes', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_albaranes_cli');
      $this->albaran = new albaran_cliente();
      $this->cliente = new cliente();
      
      if( isset($_POST['desde']) )
         $this->desde = $_POST['desde'];
      else
         $this->desde = Date('d-m-Y');
      
      if( isset($_POST['hasta']) )
         $this->hasta = $_POST['hasta'];
      else
         $this->hasta = Date('d-m-Y');
      
      if( isset($_POST['idalbaran']) )
         $this->agrupar();
      else if( isset($_POST['cliente']) )
      {
         $this->set_default_elements();
         
         $this->resultados = $this->albaran->search_from_cliente($_POST['cliente'], $_POST['desde'], $_POST['hasta']);
         if( !$this->resultados )
            $this->new_message("Sin resultados.");
      }
   }
   
   private function agrupar()
   {
      $albaranes = array();
      foreach($_POST['idalbaran'] as $id)
         $albaranes[] = $this->albaran->get($id);
      
      $continuar = TRUE;
      foreach($albaranes as $alb)
      {
         if( !$alb->ptefactura )
         {
            $this->new_error_msg("El albarán <a href='".$alb->url()."'>".$alb->codigo."</a> ya está facturado.");
            $continuar = FALSE;
            break;
         }
      }
      if($continuar)
      {
         $factura = new factura_cliente();
         $factura->apartado = $albaranes[0]->apartado;
         $factura->automatica = TRUE;
         $factura->cifnif = $albaranes[0]->cifnif;
         $factura->ciudad = $albaranes[0]->ciudad;
         $factura->codalmacen = $albaranes[0]->codalmacen;
         $factura->codcliente = $albaranes[0]->codcliente;
         $factura->coddir = $albaranes[0]->coddir;
         $factura->coddivisa = $albaranes[0]->coddivisa;
         $factura->codejercicio = $albaranes[0]->codejercicio;
         $factura->codpago = $albaranes[0]->codpago;
         $factura->codpais = $albaranes[0]->codpais;
         $factura->codpostal = $albaranes[0]->codpostal;
         $factura->codserie = $albaranes[0]->codserie;
         $factura->direccion = $albaranes[0]->direccion;
         $factura->editable = FALSE;
         $factura->fecha = $albaranes[0]->fecha;
         $factura->nombrecliente = $albaranes[0]->nombrecliente;
         $factura->provincia = $albaranes[0]->provincia;
         foreach($albaranes as $alb)
         {
            $factura->neto += $alb->neto;
            $factura->total += $alb->total;
            $factura->totaleuros += $alb->totaleuros;
            $factura->totaliva += $alb->totaliva;
         }
         if( $factura->save() )
         {
            foreach($albaranes as $alb)
            {
               foreach($alb->get_lineas() as $l)
               {
                  $n = new linea_factura_cliente();
                  $n->idalbaran = $alb->idalbaran;
                  $n->idfactura = $factura->idfactura;
                  $n->cantidad = $l->cantidad;
                  $n->codimpuesto = $l->codimpuesto;
                  $n->descripcion = $l->descripcion;
                  $n->dtolineal = $l->dtolineal;
                  $n->dtopor = $l->dtopor;
                  $n->irpf = $l->irpf;
                  $n->iva = $l->iva;
                  $n->pvpsindto = $l->pvpsindto;
                  $n->pvptotal = $l->pvptotal;
                  $n->pvpunitario = $l->pvpunitario;
                  $n->recargo = $l->recargo;
                  $n->referencia = $l->referencia;
                  if( !$n->save() )
                  {
                     $continuar = FALSE;
                     $this->new_error_msg("¡Imposible guardar la línea el artículo ".$n->referencia."! ");
                     break;
                  }
               }
            }
            
            if($continuar)
            {
               foreach($albaranes as $alb)
               {
                  $alb->idfactura = $factura->idfactura;
                  $alb->editable = FALSE;
                  $alb->ptefactura = FALSE;
                  if( !$alb->save() )
                  {
                     $this->new_error_msg("¡Imposible vincular el albarán con la nueva factura!");
                     $continuar = FALSE;
                     break;
                  }
               }
               if( $continuar )
                  $this->generar_asiento($factura);
               else
               {
                  if( $factura->delete() )
                     $this->new_error_msg("La factura se ha borrado.");
                  else
                     $this->new_error_msg("¡Imposible borrar la factura!");
               }
            }
            else
            {
               if( $factura->delete() )
                  $this->new_error_msg("La factura se ha borrado.");
               else
                  $this->new_error_msg("¡Imposible borrar la factura!");
            }
         }
         else
            $this->new_error_msg("¡Imposible guardar la factura!");
      }
   }
   
   private function generar_asiento($factura)
   {
      $cliente = new cliente();
      $cliente = $cliente->get($factura->codcliente);
      $subcuenta_cli = $cliente->get_subcuenta($factura->codejercicio);
      
      if( !$this->empresa->contintegrada )
         $this->new_message("<a href='".$factura->url()."'>Factura</a> generada correctamente.");
      else if( !$subcuenta_cli )
         $this->new_message("El cliente no tiene asociada una subcuenta y por tanto no se generará
            un asiento. Aun así la <a href='".$factura->url()."'>factura</a> se ha generado correctamente.");
      else
      {
         $asiento = new asiento();
         $asiento->codejercicio = $factura->codejercicio;
         $asiento->concepto = "Nuestra factura ".$factura->codigo." - ".$factura->nombrecliente;
         $asiento->documento = $factura->codigo;
         $asiento->editable = FALSE;
         $asiento->fecha = $factura->fecha;
         $asiento->importe = $factura->totaleuros;
         $asiento->tipodocumento = 'Factura de cliente';
         if( $asiento->save() )
         {
            $asiento_correcto = TRUE;
            $subcuenta = new subcuenta();
            $partida0 = new partida();
            $partida0->idasiento = $asiento->idasiento;
            $partida0->concepto = $asiento->concepto;
            $partida0->idsubcuenta = $subcuenta_cli->idsubcuenta;
            $partida0->codsubcuenta = $subcuenta_cli->codsubcuenta;
            $partida0->debe = $factura->totaleuros;
            $partida0->coddivisa = $factura->coddivisa;
            if( !$partida0->save() )
            {
               $asiento_correcto = FALSE;
               $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
            }
            
            /// generamos una partida por cada impuesto
            $subcuenta_iva = $subcuenta->get_by_codigo('4770000000', $asiento->codejercicio);
            foreach($factura->get_lineas_iva() as $li)
            {
               if($subcuenta_iva AND $asiento_correcto)
               {
                  $partida1 = new partida();
                  $partida1->idasiento = $asiento->idasiento;
                  $partida1->concepto = $asiento->concepto;
                  $partida1->idsubcuenta = $subcuenta_iva->idsubcuenta;
                  $partida1->codsubcuenta = $subcuenta_iva->codsubcuenta;
                  $partida1->haber = $li->totaliva;
                  $partida1->idcontrapartida = $subcuenta_cli->idsubcuenta;
                  $partida1->codcontrapartida = $subcuenta_cli->codsubcuenta;
                  $partida1->cifnif = $cliente->cifnif;
                  $partida1->documento = $asiento->documento;
                  $partida1->tipodocumento = $asiento->tipodocumento;
                  $partida1->codserie = $factura->codserie;
                  $partida1->factura = $factura->numero;
                  $partida1->baseimponible = $li->neto;
                  $partida1->iva = $li->iva;
                  $partida1->coddivisa = $factura->coddivisa;
                  if( !$partida1->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida1->codsubcuenta."!");
                  }
               }
            }
            
            $subcuenta_ventas = $subcuenta->get_by_codigo('7000000000', $asiento->codejercicio);
            if($subcuenta_ventas AND $asiento_correcto)
            {
               $partida2 = new partida();
               $partida2->idasiento = $asiento->idasiento;
               $partida2->concepto = $asiento->concepto;
               $partida2->idsubcuenta = $subcuenta_ventas->idsubcuenta;
               $partida2->codsubcuenta = $subcuenta_ventas->codsubcuenta;
               $partida2->haber = $factura->neto;
               $partida2->coddivisa = $factura->coddivisa;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
            }
            
            if($asiento_correcto)
            {
               $factura->idasiento = $asiento->idasiento;
               if( $factura->save() )
                  $this->new_message("<a href='".$factura->url()."'>Factura</a> generada correctamente.");
               else
                  $this->new_error_msg("¡Imposible añadir el asiento a la factura!");
            }
            else
            {
               if( $asiento->delete() )
               {
                  $this->new_message("El asiento se ha borrado.");
                  if( $factura->delete() )
                     $this->new_message("La factura se ha borrado.");
                  else
                     $this->new_error_msg("¡Imposible borrar la factura!");
               }
               else
                  $this->new_error_msg("¡Imposible borrar el asiento!");
            }
         }
         else
         {
            $this->new_error_msg("¡Imposible guardar el asiento!");
            if( $factura->delete() )
               $this->new_error_msg("La factura se ha borrado.");
            else
               $this->new_error_msg("¡Imposible borrar la factura!");
         }
      }
   }
   
   private function set_default_elements()
   {
      if( isset($_POST['cliente']) )
      {
         $cliente = $this->cliente->get($_POST['cliente']);
         if( $cliente )
            $cliente->set_default();
      }
   }
   
   public function version() {
      return parent::version().'-2';
   }
}

?>
