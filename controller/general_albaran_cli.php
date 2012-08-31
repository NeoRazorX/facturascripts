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
require_once 'model/articulo.php';
require_once 'model/asiento.php';
require_once 'model/cliente.php';
require_once 'model/empresa.php';
require_once 'model/factura_cliente.php';
require_once 'model/impuesto.php';
require_once 'model/partida.php';
require_once 'model/subcuenta.php';

class general_albaran_cli extends fs_controller
{
   public $albaran;
   public $agente;
   public $nuevo_albaran_url;
   
   public function __construct()
   {
      parent::__construct('general_albaran_cli', 'Albarán de cliente', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_albaranes_cli');
      
      /*
       * buscamos la url del script general_nuevo_albaran,
       * imprescindible para buscar nuevo artículos.
       */
      $nuevoalbp = $this->page->get('general_nuevo_albaran');
      if($nuevoalbp)
         $this->nuevo_albaran_url = $nuevoalbp->url();
      else
         $this->nuevo_albaran_url = $this->url();
      
      if( isset($_POST['idalbaran']) )
      {
         $this->albaran = new albaran_cliente();
         $this->albaran = $this->albaran->get($_POST['idalbaran']);
         $this->modificar();
      }
      else if( isset($_GET['id']) )
      {
         $this->albaran = new albaran_cliente();
         $this->albaran = $this->albaran->get($_GET['id']);
      }
      
      if( $this->albaran )
      {
         $this->page->title = $this->albaran->codigo;
         $this->agente = $this->albaran->get_agente();
         
         if( isset($_GET['facturar']) )
            $this->generar_factura();
         
         if( $this->albaran->ptefactura )
            $this->buttons[] = new fs_button('b_facturar', 'generar factura', $this->url()."&facturar=TRUE");
         else
            $this->buttons[] = new fs_button('b_ver_factura', 'ver factura', $this->albaran->factura_url(), 'button', 'img/zoom.png');
         $this->buttons[] = new fs_button('b_remove_albaran', 'eliminar', '#', 'remove', 'img/remove.png', '-');
      }
      else
         $this->new_error_msg("¡Albarán de cliente no encontrado!");
   }
   
   public function version() {
      return parent::version().'-1';
   }
   
   public function url()
   {
      if($this->albaran)
         return $this->albaran->url();
      else
         return $this->page->url();
   }
   
   private function modificar()
   {
      $this->albaran->numero2 = $_POST['numero2'];
      $this->albaran->fecha = $_POST['fecha'];
      $this->albaran->hora = $_POST['hora'];
      $this->albaran->observaciones = $_POST['observaciones'];
      
      if( isset($_POST['lineas']) )
      {
         $lineas = $this->albaran->get_lineas();
         /// eliminamos las líneas que no encontremos en el $_POST
         foreach($lineas as $l)
         {
            $encontrada = FALSE;
            for($num = 0; $num <= 100; $num++)
            {
               if( isset($_POST['idlinea_'.$num]) )
               {
                  if($l->idlinea == intval($_POST['idlinea_'.$num]))
                  {
                     $encontrada = TRUE;
                     break;
                  }
               }
            }
            if( !$encontrada )
            {
               if( !$l->delete() )
                  $this->new_error_msg("¡Imposible eliminar la línea del artículo ".$l->referencia."!");
            }
         }
         
         $neto = 0;
         $iva = 0;
         $total = 0;
         /// modificamos y/o añadimos las demás líneas
         for($num = 0; $num <= 100; $num++)
         {
            $encontrada = FALSE;
            if( isset($_POST['idlinea_'.$num]) )
            {
               foreach($lineas as &$l)
               {
                  if($l->idlinea == intval($_POST['idlinea_'.$num]))
                  {
                     $encontrada = TRUE;
                     $l->cantidad = floatval($_POST['cantidad_'.$num]);
                     $l->pvpunitario = floatval($_POST['pvp_'.$num]);
                     $l->dtopor = floatval($_POST['dto_'.$num]);
                     $l->dtolineal = 0;
                     $l->pvpsindto = ($l->cantidad * $l->pvpunitario);
                     $l->pvptotal = ($l->cantidad * $l->pvpunitario * (100 - $l->dtopor)/100);
                     $neto += ($l->cantidad * $l->pvpunitario * (100 - $l->dtopor)/100);
                     $total += ($l->cantidad * $l->pvpunitario * (100 - $l->dtopor)/100 * (100 + $l->iva)/100);
                     $iva += ($l->cantidad * $l->pvpunitario * (100 - $l->dtopor)/100 * $l->iva/100);
                     if( !$l->save() )
                        $this->new_error_msg("¡Imposible modificar la línea del artículo ".$l->referencia."!");
                     break;
                  }
               }
               if(!$encontrada AND intval($_POST['idlinea_'.$num]) == -1 AND isset($_POST['referencia_'.$num]))
               {
                  $articulo = new articulo();
                  $articulo = $articulo->get( $_POST['referencia_'.$num] );
                  if($articulo)
                  {
                     $linea = new linea_albaran_cliente();
                     $linea->referencia = $articulo->referencia;
                     $linea->descripcion = $articulo->descripcion;
                     $linea->codimpuesto = $articulo->codimpuesto;
                     $linea->iva = $articulo->get_iva();
                     $linea->idalbaran = $this->albaran->idalbaran;
                     $linea->cantidad = floatval($_POST['cantidad_'.$num]);
                     $linea->pvpunitario = floatval($_POST['pvp_'.$num]);
                     $linea->dtopor = floatval($_POST['dto_'.$num]);
                     $linea->pvpsindto = ($linea->cantidad * $linea->pvpunitario);
                     $linea->pvptotal = ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);
                     $neto += ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100);
                     $total += ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100 * (100 + $linea->iva)/100);
                     $iva += ($linea->cantidad * $linea->pvpunitario * (100 - $linea->dtopor)/100 * $linea->iva/100);
                     if( !$linea->save() )
                        $this->new_error_msg("¡Imposible guardar la línea del artículo ".$linea->referencia."!");
                  }
               }
            }
         }
         $this->albaran->neto = $neto;
         $this->albaran->totaliva = $iva;
         $this->albaran->total = $total;
         $this->albaran->totaleuros = $total;
      }
      
      if( $this->albaran->save() )
         $this->new_message("Albarán modificado correctamente.");
      else
         $this->new_error_msg("¡Imposible modificar el albarán!");
   }

   private function generar_factura()
   {
      $factura = new factura_cliente();
      $factura->apartado = $this->albaran->apartado;
      $factura->automatica = TRUE;
      $factura->cifnif = $this->albaran->cifnif;
      $factura->ciudad = $this->albaran->ciudad;
      $factura->codagente = $this->albaran->codagente;
      $factura->codalmacen = $this->albaran->codalmacen;
      $factura->codcliente = $this->albaran->codcliente;
      $factura->coddir = $this->albaran->coddir;
      $factura->coddivisa = $this->albaran->coddivisa;
      $factura->codejercicio = $this->albaran->codejercicio;
      $factura->codpago = $this->albaran->codpago;
      $factura->codpais = $this->albaran->codpais;
      $factura->codpostal = $this->albaran->codpostal;
      $factura->codserie = $this->albaran->codserie;
      $factura->direccion = $this->albaran->direccion;
      $factura->editable = FALSE;
      $factura->fecha = $this->albaran->fecha;
      $factura->neto = $this->albaran->neto;
      $factura->nombrecliente = $this->albaran->nombrecliente;
      $factura->observaciones = $this->albaran->observaciones;
      $factura->provincia = $this->albaran->provincia;
      $factura->total = $this->albaran->total;
      $factura->totaleuros = $this->albaran->totaleuros;
      $factura->totaliva = $this->albaran->totaliva;
      if( $factura->save() )
      {
         $continuar = TRUE;
         foreach($this->albaran->get_lineas() as $l)
         {
            $n = new linea_factura_cliente();
            $n->idalbaran = $this->albaran->idalbaran;
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
         
         if($continuar)
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
         $this->new_error_msg("¡Imposible guardar la factura!");
   }
   
   private function generar_asiento($factura)
   {
      $empresa = new empresa();
      if( !$empresa->contintegrada )
      {
         $this->albaran->idfactura = $factura->idfactura;
         $this->albaran->editable = FALSE;
         $this->albaran->ptefactura = FALSE;
         if( $this->albaran->save() )
            $this->new_message("<a href='".$factura->url()."'>Factura</a> generada correctamente.");
         else
            $this->new_error_msg("¡Imposible vincular el albarán con la nueva factura!");
      }
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
            $cliente = new cliente();
            $cliente = $cliente->get($factura->codcliente);
            $subcuenta_cli = $cliente->get_subcuenta($asiento->codejercicio);
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
            
            /// desglosamos el iva
            $totales_iva = array();
            foreach($factura->get_lineas() as $l)
            {
               $encontrado = FALSE;
               foreach($totales_iva as &$t)
               {
                  if($t[0] == $l->codimpuesto)
                  {
                     $encontrado = TRUE;
                     $t[2] += $l->pvptotal;
                     $t[3] += ($l->iva * $l->pvptotal / 100);
                  }
               }
               
               if( !$encontrado )
                  $totales_iva[] = array($l->codimpuesto, $l->iva, $l->pvptotal, ($l->iva * $l->pvptotal / 100));
            }
            /// generamos una partida por cada impuesto
            $subcuenta_iva = $subcuenta->get_by_codigo('4770000000', $asiento->codejercicio);
            foreach($totales_iva as $t)
            {
               if($subcuenta_iva AND $asiento_correcto)
               {
                  $partida1 = new partida();
                  $partida1->idasiento = $asiento->idasiento;
                  $partida1->concepto = $asiento->concepto;
                  $partida1->idsubcuenta = $subcuenta_iva->idsubcuenta;
                  $partida1->codsubcuenta = $subcuenta_iva->codsubcuenta;
                  $partida1->haber = $t[3];
                  $partida1->idcontrapartida = $subcuenta_cli->idsubcuenta;
                  $partida1->codcontrapartida = $subcuenta_cli->codsubcuenta;
                  $partida1->cifnif = $cliente->cifnif;
                  $partida1->documento = $asiento->documento;
                  $partida1->tipodocumento = $asiento->tipodocumento;
                  $partida1->codserie = $factura->codserie;
                  $partida1->factura = $factura->idfactura;
                  $partida1->baseimponible = $t[2];
                  $partida1->iva = $t[1];
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
               {
                  $this->albaran->idfactura = $factura->idfactura;
                  $this->albaran->editable = FALSE;
                  $this->albaran->ptefactura = FALSE;
                  if( $this->albaran->save() )
                     $this->new_message("<a href='".$factura->url()."'>Factura</a> generada correctamente.");
                  else
                     $this->new_error_msg("¡Imposible vincular el albarán con la nueva factura!");
               }
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
}

?>
