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

require_model('albaran_cliente.php');
require_model('albaran_proveedor.php');
require_model('asiento.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('factura_cliente.php');
require_model('factura_proveedor.php');
require_model('partida.php');
require_model('proveedor.php');
require_model('regularizacion_iva.php');
require_model('serie.php');
require_model('subcuenta.php');

class megafacturador extends fs_controller
{
   private $ejercicio;
   private $regularizacion;
   private $total;
   
   public function __construct()
   {
      parent::__construct('megafacturador', 'MegaFacturador', 'general', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->ejercicio = new ejercicio();
      $this->regularizacion = new regularizacion_iva();
      
      if( isset($_GET['start']) )
      {
         $this->total = 0;
         $albaran_cli = new albaran_cliente();
         foreach($albaran_cli->all_ptefactura(0, 'ASC') as $alb)
         {
            $this->generar_factura_cliente( array($alb) );
         }
         $this->new_message($this->total.' '.FS_ALBARANES.' de cliente facturados.');
         
         $this->total = 0;
         $albaran_pro = new albaran_proveedor();
         foreach($albaran_pro->all_ptefactura(0, 'ASC') as $alb)
         {
            $this->generar_factura_proveedor( array($alb) );
         }
         $this->new_message($this->total.' '.FS_ALBARANES.' de proveedor facturados.');
      }
   }
   
   private function generar_factura_cliente($albaranes)
   {
      $continuar = TRUE;
      
      $factura = new factura_cliente();
      $factura->apartado = $albaranes[0]->apartado;
      $factura->automatica = TRUE;
      $factura->cifnif = $albaranes[0]->cifnif;
      $factura->ciudad = $albaranes[0]->ciudad;
      $factura->codalmacen = $albaranes[0]->codalmacen;
      $factura->codcliente = $albaranes[0]->codcliente;
      $factura->coddir = $albaranes[0]->coddir;
      $factura->coddivisa = $albaranes[0]->coddivisa;
      $factura->tasaconv = $albaranes[0]->tasaconv;
      $factura->codejercicio = $albaranes[0]->codejercicio;
      $factura->codpago = $albaranes[0]->codpago;
      $factura->codpais = $albaranes[0]->codpais;
      $factura->codpostal = $albaranes[0]->codpostal;
      $factura->codserie = $albaranes[0]->codserie;
      $factura->direccion = $albaranes[0]->direccion;
      $factura->editable = FALSE;
      $factura->nombrecliente = $albaranes[0]->nombrecliente;
      $factura->provincia = $albaranes[0]->provincia;
      
      /// calculamos neto e iva
      foreach($albaranes as $alb)
      {
         foreach($alb->get_lineas() as $l)
         {
            $factura->neto += ($l->cantidad * $l->pvpunitario * (100 - $l->dtopor)/100);
            $factura->totaliva += ($l->cantidad * $l->pvpunitario * (100 - $l->dtopor)/100 * $l->iva/100);
         }
      }
      /// redondeamos
      $factura->neto = round($factura->neto, 2);
      $factura->totaliva = round($factura->totaliva, 2);
      $factura->total = $factura->neto + $factura->totaliva;
      
      /// asignamos la mejor fecha posible, pero dentro del ejercicio
      $eje0 = $this->ejercicio->get($factura->codejercicio);
      $factura->fecha = $eje0->get_best_fecha($factura->fecha);
      
      if( !$eje0->abierto() )
      {
         $this->new_error_msg('El ejercicio '.$eje0->codejercicio.' está cerrado.');
      }
      else if( $this->regularizacion->get_fecha_inside($factura->fecha) )
      {
         $this->new_error_msg('El IVA de ese periodo ya ha sido regularizado.
            No se pueden añadir más facturas en esa fecha.');
      }
      else if( $factura->save() )
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
               $alb->ptefactura = FALSE;
               
               if( !$alb->save() )
               {
                  $this->new_error_msg("¡Imposible vincular el ".FS_ALBARAN." con la nueva factura!");
                  $continuar = FALSE;
                  break;
               }
            }
            
            if( $continuar )
            {
               $this->generar_asiento_cliente($factura);
               $this->total++;
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
   
   private function generar_asiento_cliente($factura)
   {
      $cliente = new cliente();
      $cliente = $cliente->get($factura->codcliente);
      $subcuenta_cli = $cliente->get_subcuenta($factura->codejercicio);
      
      if( !$this->empresa->contintegrada )
      {
         
      }
      else if( !$subcuenta_cli )
      {
         $this->new_message("El cliente no tiene asociada una subcuenta y por tanto no se generará
            un asiento. Aun así la <a href='".$factura->url()."'>factura</a> se ha generado correctamente.");
      }
      else
      {
         $asiento = new asiento();
         $asiento->codejercicio = $factura->codejercicio;
         $asiento->concepto = "Nuestra factura ".$factura->codigo." - ".$factura->nombrecliente;
         $asiento->documento = $factura->codigo;
         $asiento->editable = FALSE;
         $asiento->fecha = $factura->fecha;
         $asiento->importe = $factura->total;
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
            $partida0->debe = $factura->total;
            $partida0->coddivisa = $factura->coddivisa;
            $partida0->tasaconv = $factura->tasaconv;
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
                  $partida1->tasaconv = $factura->tasaconv;
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
               $partida2->tasaconv = $factura->tasaconv;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
            }
            
            if($asiento_correcto)
            {
               $factura->idasiento = $asiento->idasiento;
               if( !$factura->save() )
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
   
   private function generar_factura_proveedor($albaranes)
   {
      $continuar = TRUE;
      
      $factura = new factura_proveedor();
      $factura->automatica = TRUE;
      $factura->editable = FALSE;
      $factura->cifnif = $albaranes[0]->cifnif;
      $factura->codalmacen = $albaranes[0]->codalmacen;
      $factura->coddivisa = $albaranes[0]->coddivisa;
      $factura->tasaconv = $albaranes[0]->tasaconv;
      $factura->codejercicio = $albaranes[0]->codejercicio;
      $factura->codpago = $albaranes[0]->codpago;
      $factura->codproveedor = $albaranes[0]->codproveedor;
      $factura->codserie = $albaranes[0]->codserie;
      $factura->irpf = $albaranes[0]->irpf;
      $factura->nombre = $albaranes[0]->nombre;
      $factura->numproveedor = $albaranes[0]->numproveedor;
      $factura->observaciones = $albaranes[0]->observaciones;
      $factura->recfinanciero = $albaranes[0]->recfinanciero;
      $factura->totalirpf = $albaranes[0]->totalirpf;
      $factura->totalrecargo = $albaranes[0]->totalrecargo;
      
      /// calculamos neto e iva
      foreach($albaranes as $alb)
      {
         foreach($alb->get_lineas() as $l)
         {
            $factura->neto += ($l->cantidad * $l->pvpunitario * (100 - $l->dtopor)/100);
            $factura->totaliva += ($l->cantidad * $l->pvpunitario * (100 - $l->dtopor)/100 * $l->iva/100);
         }
      }
      /// redondeamos
      $factura->neto = round($factura->neto, 2);
      $factura->totaliva = round($factura->totaliva, 2);
      $factura->total = $factura->neto + $factura->totaliva;
      
      /// asignamos la mejor fecha posible, pero dentro del ejercicio
      $eje0 = $this->ejercicio->get($factura->codejercicio);
      $factura->fecha = $eje0->get_best_fecha($factura->fecha);
      
      if( !$eje0->abierto() )
      {
         $this->new_error_msg('El ejercicio '.$eje0->codejercicio.' está cerrado.');
      }
      else if( $this->regularizacion->get_fecha_inside($factura->fecha) )
      {
         $this->new_error_msg('El IVA de ese periodo ya ha sido regularizado.
            No se pueden añadir más facturas en esa fecha.');
      }
      else if( $factura->save() )
      {
         foreach($albaranes as $alb)
         {
            foreach($alb->get_lineas() as $l)
            {
               $n = new linea_factura_proveedor();
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
               $alb->ptefactura = FALSE;
               
               if( !$alb->save() )
               {
                  $this->new_error_msg("¡Imposible vincular el ".FS_ALBARAN." con la nueva factura!");
                  $continuar = FALSE;
                  break;
               }
            }
            
            if( $continuar )
            {
               $this->generar_asiento_proveedor($factura);
               $this->total++;
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
   
   private function generar_asiento_proveedor($factura)
   {
      $proveedor = new proveedor();
      $proveedor = $proveedor->get($factura->codproveedor);
      $subcuenta_prov = $proveedor->get_subcuenta($factura->codejercicio);
      
      if( !$this->empresa->contintegrada )
      {
         
      }
      else if( !$subcuenta_prov )
      {
         $this->new_message("El proveedor no tiene asociada una subcuenta, y por tanto no se generará
            un asiento. Aun así la <a href='".$factura->url()."'>factura</a> se ha generado correctamente.");
      }
      else
      {
         $asiento = new asiento();
         $asiento->codejercicio = $factura->codejercicio;
         $asiento->concepto = "Su factura ".$factura->codigo." - ".$factura->nombre;
         $asiento->documento = $factura->codigo;
         $asiento->editable = FALSE;
         $asiento->fecha = $factura->fecha;
         $asiento->importe = $factura->total;
         $asiento->tipodocumento = "Factura de proveedor";
         if( $asiento->save() )
         {
            $asiento_correcto = TRUE;
            $subcuenta = new subcuenta();
            $partida0 = new partida();
            $partida0->idasiento = $asiento->idasiento;
            $partida0->concepto = $asiento->concepto;
            $partida0->idsubcuenta = $subcuenta_prov->idsubcuenta;
            $partida0->codsubcuenta = $subcuenta_prov->codsubcuenta;
            $partida0->haber = $factura->total;
            $partida0->coddivisa = $factura->coddivisa;
            $partida0->tasaconv = $factura->tasaconv;
            if( !$partida0->save() )
            {
               $asiento_correcto = FALSE;
               $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida0->codsubcuenta."!");
            }
            
            /// generamos una partida por cada impuesto
            $subcuenta_iva = $subcuenta->get_by_codigo('4720000000', $asiento->codejercicio);
            foreach($factura->get_lineas_iva() as $li)
            {
               if($subcuenta_iva AND $asiento_correcto)
               {
                  $partida1 = new partida();
                  $partida1->idasiento = $asiento->idasiento;
                  $partida1->concepto = $asiento->concepto;
                  $partida1->idsubcuenta = $subcuenta_iva->idsubcuenta;
                  $partida1->codsubcuenta = $subcuenta_iva->codsubcuenta;
                  $partida1->debe = $li->totaliva;
                  $partida1->idcontrapartida = $subcuenta_prov->idsubcuenta;
                  $partida1->codcontrapartida = $subcuenta_prov->codsubcuenta;
                  $partida1->cifnif = $proveedor->cifnif;
                  $partida1->documento = $asiento->documento;
                  $partida1->tipodocumento = $asiento->tipodocumento;
                  $partida1->codserie = $factura->codserie;
                  $partida1->factura = $factura->numero;
                  $partida1->baseimponible = $li->neto;
                  $partida1->iva = $li->iva;
                  $partida1->coddivisa = $factura->coddivisa;
                  $partida1->tasaconv = $factura->tasaconv;
                  if( !$partida1->save() )
                  {
                     $asiento_correcto = FALSE;
                     $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida1->codsubcuenta."!");
                  }
               }
            }
            
            $subcuenta_compras = $subcuenta->get_by_codigo('6000000000', $asiento->codejercicio);
            if($subcuenta_compras AND $asiento_correcto)
            {
               $partida2 = new partida();
               $partida2->idasiento = $asiento->idasiento;
               $partida2->concepto = $asiento->concepto;
               $partida2->idsubcuenta = $subcuenta_compras->idsubcuenta;
               $partida2->codsubcuenta = $subcuenta_compras->codsubcuenta;
               $partida2->debe = $factura->neto;
               $partida2->coddivisa = $factura->coddivisa;
               $partida2->tasaconv = $factura->tasaconv;
               if( !$partida2->save() )
               {
                  $asiento_correcto = FALSE;
                  $this->new_error_msg("¡Imposible generar la partida para la subcuenta ".$partida2->codsubcuenta."!");
               }
            }
            
            if( $asiento_correcto )
            {
               $factura->idasiento = $asiento->idasiento;
               if( !$factura->save() )
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
}

?>