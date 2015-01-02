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

require_model('albaran_proveedor.php');
require_model('asiento.php');
require_model('asiento_factura.php');
require_model('ejercicio.php');
require_model('factura_proveedor.php');
require_model('forma_pago.php');
require_model('partida.php');
require_model('proveedor.php');
require_model('regularizacion_iva.php');
require_model('serie.php');
require_model('subcuenta.php');

class compras_agrupar_albaranes extends fs_controller
{
   public $albaran;
   public $desde;
   private $forma_pago;
   public $hasta;
   public $proveedor;
   public $resultados;
   public $serie;
   public $neto;
   public $total;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Agrupar '.FS_ALBARANES, 'compras', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('compras_albaranes');
      $this->albaran = new albaran_proveedor();
      $this->forma_pago = new forma_pago();
      $this->proveedor = new proveedor();
      $this->serie = new serie();
      $this->neto = 0;
      $this->total = 0;
      
      $this->desde = Date('01-m-Y');
      if( isset($_POST['desde']) )
         $this->desde = $_POST['desde'];
      
      $this->hasta = Date('t-m-Y');
      if( isset($_POST['hasta']) )
         $this->hasta = $_POST['hasta'];
      
      if( isset($_POST['idalbaran']) )
      {
         $this->agrupar();
      }
      else if( isset($_POST['proveedor']) )
      {
         $this->save_codproveedor($_POST['proveedor']);
         
         $this->resultados = $this->albaran->search_from_proveedor($_POST['proveedor'], $_POST['desde'], $_POST['hasta'], $_POST['serie']);
         if($this->resultados)
         {
            foreach($this->resultados as $alb)
            {
               $this->neto += $alb->neto;
               $this->total += $alb->total;
            }
         }
         else
            $this->new_message("Sin resultados.");
      }
   }
   
   private function agrupar()
   {
      $continuar = TRUE;
      $albaranes = array();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón Guardar
               y se han enviado dos peticiones. Mira en <a href="'.$this->ppage->url().'">'.FS_ALBARANES.'</a>
               para ver si los '.FS_ALBARANES.' se han guardado correctamente.');
         $continuar = FALSE;
      }
      else
      {
         foreach($_POST['idalbaran'] as $id)
            $albaranes[] = $this->albaran->get($id);
         
         $codejercicio = NULL;
         foreach($albaranes as $alb)
         {
            if( !isset($codejercicio) )
               $codejercicio = $alb->codejercicio;
            
            if( !$alb->ptefactura )
            {
               $this->new_error_msg("El ".FS_ALBARAN." <a href='".$alb->url()."'>".$alb->codigo."</a> ya está facturado.");
               $continuar = FALSE;
               break;
            }
            else if($alb->codejercicio != $codejercicio)
            {
               $this->new_error_msg("Los ejercicios de los ".FS_ALBARANES." no coinciden.");
               $continuar = FALSE;
               break;
            }
         }
         
         if( isset($codejercicio) )
         {
            $ejercicio = new ejercicio();
            $eje0 = $ejercicio->get($codejercicio);
            if($eje0)
            {
               if( !$eje0->abierto() )
               {
                  $this->new_error_msg("El ejercicio está cerrado.");
                  $continuar = FALSE;
               }
            }
         }
      }
      
      if($continuar)
      {
         if( isset($_POST['individuales']) )
         {
            foreach($albaranes as $alb)
               $this->generar_factura( array($alb) );
         }
         else
            $this->generar_factura($albaranes);
      }
   }
   
   private function generar_factura($albaranes)
   {
      $continuar = TRUE;
      
      $factura = new factura_proveedor();
      $factura->automatica = TRUE;
      $factura->editable = FALSE;
      $factura->codalmacen = $albaranes[0]->codalmacen;
      $factura->coddivisa = $albaranes[0]->coddivisa;
      $factura->tasaconv = $albaranes[0]->tasaconv;
      $factura->codejercicio = $albaranes[0]->codejercicio;
      $factura->codpago = $albaranes[0]->codpago;
      $factura->codserie = $albaranes[0]->codserie;
      $factura->irpf = $albaranes[0]->irpf;
      $factura->numproveedor = $albaranes[0]->numproveedor;
      $factura->observaciones = $albaranes[0]->observaciones;
      $factura->recfinanciero = $albaranes[0]->recfinanciero;
      
      /// comprobamos la forma de pago para saber si hay que marcar la factura como pagada
      $formapago = $this->forma_pago->get($factura->codpago);
      if($formapago)
      {
         if($formapago->genrecibos == 'Pagados')
         {
            $factura->pagada = TRUE;
         }
      }
      
      /// obtenemos los datos actualizados del proveedor
      $proveedor = $this->proveedor->get($albaranes[0]->codproveedor);
      if($proveedor)
      {
         $factura->cifnif = $proveedor->cifnif;
         $factura->codproveedor = $proveedor->codproveedor;
         $factura->nombre = $proveedor->nombrecomercial;
      }
      
      /// calculamos neto e iva
      foreach($albaranes as $alb)
      {
         foreach($alb->get_lineas() as $l)
         {
            $factura->neto += $l->pvptotal;
            $factura->totaliva += $l->pvptotal * $l->iva/100;
            $factura->totalirpf += $l->pvptotal * $l->irpf/100;
            $factura->totalrecargo += $l->pvptotal * $l->recargo/100;
         }
      }
      
      /// redondeamos
      $factura->neto = round($factura->neto, FS_NF0);
      $factura->totaliva = round($factura->totaliva, FS_NF0);
      $factura->totalirpf = round($factura->totalirpf, FS_NF0);
      $factura->totalrecargo = round($factura->totalrecargo, FS_NF0);
      $factura->total = $factura->neto + $factura->totaliva - $factura->totalirpf + $factura->totalrecargo;
      
      /// asignamos la mejor fecha posible, pero dentro del ejercicio
      $ejercicio = new ejercicio();
      $eje0 = $ejercicio->get($factura->codejercicio);
      $factura->fecha = $eje0->get_best_fecha($factura->fecha);
      
      $regularizacion = new regularizacion_iva();
      
      if( !$eje0->abierto() )
      {
         $this->new_error_msg('El ejercicio '.$eje0->codejercicio.' está cerrado.');
      }
      else if( $regularizacion->get_fecha_inside($factura->fecha) )
      {
         /*
          * comprobamos que la fecha de la factura no esté dentro de un periodo de
          * IVA regularizado.
          */
         $this->new_error_msg('El IVA de ese periodo ya ha sido regularizado. No se pueden añadir más facturas en esa fecha.');
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
               $this->generar_asiento($factura);
            }
            else
            {
               if( $factura->delete() )
               {
                  $this->new_error_msg("La factura se ha borrado.");
               }
               else
                  $this->new_error_msg("¡Imposible borrar la factura!");
            }
         }
         else
         {
            if( $factura->delete() )
            {
               $this->new_error_msg("La factura se ha borrado.");
            }
            else
               $this->new_error_msg("¡Imposible borrar la factura!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar la factura!");
   }
   
   private function generar_asiento($factura)
   {
      if($this->empresa->contintegrada)
      {
         $asiento_factura = new asiento_factura();
         if( $asiento_factura->generar_asiento_compra($factura) )
         {
            $this->new_message("<a href='".$factura->url()."'>Factura</a> generada correctamente.");
         }
         
         foreach($asiento_factura->errors as $err)
         {
            $this->new_error_msg($err);
         }
         
         foreach($asiento_factura->messages as $msg)
         {
            $this->new_message($msg);
         }
      }
      else
      {
         $this->new_message("<a href='".$factura->url()."'>Factura</a> generada correctamente.");
      }
      
      $this->new_change('Factura Proveedor '.$factura->codigo, $factura->url(), TRUE);
   }
}
