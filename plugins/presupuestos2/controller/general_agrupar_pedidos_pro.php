<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2014  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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

require_model('pedido_proveedor.php');
require_model('ejercicio.php');
require_model('albaran_proveedor.php');
require_model('proveedor.php');
require_model('regularizacion_iva.php');
require_model('serie.php');

class general_agrupar_pedidos_pro extends fs_controller
{
   public $pedido;
   public $desde;
   public $hasta;
   public $proveedor;
   public $resultados;
   public $serie;
   public $neto;
   public $total;
   
   public function __construct()
   {
      parent::__construct('general_agrupar_pedidos_pro', 'Agrupar pedidos', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_pedidos_prov');
      $this->pedido = new pedido_proveedor();
      $this->proveedor = new proveedor();
      $this->serie = new serie();
      $this->neto = 0;
      $this->total = 0;
      
      if( isset($_POST['desde']) )
         $this->desde = $_POST['desde'];
      else
         $this->desde = Date('d-m-Y');
      
      if( isset($_POST['hasta']) )
         $this->hasta = $_POST['hasta'];
      else
         $this->hasta = Date('d-m-Y');
      
      if( isset($_POST['idpedido']) )
         $this->agrupar();
      else if( isset($_POST['proveedor']) )
      {
         $this->save_codproveedor($_POST['proveedor']);
         
         $this->resultados = $this->pedido->search_from_proveedor($_POST['proveedor'],
                 $_POST['desde'], $_POST['hasta'], $_POST['serie']);
         
         if($this->resultados)
         {
            foreach($this->resultados as $presu)
            {
               $this->neto += $presu->neto;
               $this->total += $presu->total;
            }
         }
         else
            $this->new_message("Sin resultados.");
      }
   }
   
   private function agrupar()
   {
      $continuar = TRUE;
      $pedidos = array();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guadar
               y se han enviado dos peticiones. Mira en <a href="'.$this->ppage->url().'">pedidos</a>
               para ver si los pedidos se han guardado correctamente.');
         $continuar = FALSE;
      }
      else
      {
         foreach($_POST['idpedido'] as $id)
            $pedidos[] = $this->pedido->get($id);
         
         $codejercicio = NULL;
         foreach($pedidos as $presu)
         {
            if( !isset($codejercicio) )
               $codejercicio = $presu->codejercicio;
            
            if( !$presu->ptealbaran )
            {
               $this->new_error_msg("El pedido <a href='".$presu->url()."'>".$presu->codigo."</a> ya está aprobado.");
               $continuar = FALSE;
               break;
            }
            else if($presu->codejercicio != $codejercicio)
            {
               $this->new_error_msg("Los ejercicios de los pedidos no coinciden.");
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
            foreach($pedidos as $presu)
               $this->generar_albaran( array($presu) );
         }
         else
            $this->generar_albaran($pedidos);
      }
   }
   
   private function generar_albaran($pedidos)
   {
      $continuar = TRUE;
      
      $albaran = new albaran_proveedor();
      $albaran->automatica = TRUE;
      $albaran->editable = FALSE;
      $albaran->codalmacen = $pedidos[0]->codalmacen;
      $albaran->coddivisa = $pedidos[0]->coddivisa;
      $albaran->tasaconv = $pedidos[0]->tasaconv;
      $albaran->codejercicio = $pedidos[0]->codejercicio;
      $albaran->codpago = $pedidos[0]->codpago;
      $albaran->codserie = $pedidos[0]->codserie;
      $albaran->irpf = $pedidos[0]->irpf;
      $albaran->numproveedor = $pedidos[0]->numproveedor;
      $albaran->observaciones = $pedidos[0]->observaciones;
      $albaran->recfinanciero = $pedidos[0]->recfinanciero;
      $albaran->totalirpf = $pedidos[0]->totalirpf;
      $albaran->totalrecargo = $pedidos[0]->totalrecargo;
      
      /// obtenemos los datos actualizados del proveedor
      $proveedor = $this->proveedor->get($pedidos[0]->codproveedor);
      if($proveedor)
      {
         $albaran->cifnif = $proveedor->cifnif;
         $albaran->codproveedor = $proveedor->codproveedor;
         $albaran->nombre = $proveedor->nombrecomercial;
      }
      
      /// calculamos neto e iva
      foreach($pedidos as $presu)
      {
         foreach($presu->get_lineas() as $l)
         {
            $albaran->neto += ($l->cantidad * $l->pvpunitario * (100 - $l->dtopor)/100);
            $albaran->totaliva += ($l->cantidad * $l->pvpunitario * (100 - $l->dtopor)/100 * $l->iva/100);
         }
      }
      /// redondeamos
      $albaran->neto = round($albaran->neto, 2);
      $albaran->totaliva = round($albaran->totaliva, 2);
      $albaran->total = $albaran->neto + $albaran->totaliva;
      
      /// asignamos la mejor fecha posible, pero dentro del ejercicio
      $ejercicio = new ejercicio();
      $eje0 = $ejercicio->get($albaran->codejercicio);
      $albaran->fecha = $eje0->get_best_fecha($albaran->fecha);
      
      /*
       * comprobamos que la fecha de el albarán no esté dentro de un periodo de
       * IVA regularizado.
       */
      $regularizacion = new regularizacion_iva();
      
      if( $regularizacion->get_fecha_inside($albaran->fecha) )
      {
         $this->new_error_msg('El IVA de ese periodo ya ha sido regularizado.
            No se pueden añadir más '.FS_ALBARANES.' en esa fecha.');
      }
      else if( $albaran->save() )
      {
         foreach($pedidos as $presu)
         {
            foreach($presu->get_lineas() as $l)
            {
               $n = new linea_albaran_proveedor();
               $n->idpedido = $presu->idpedido;
               $n->idalbaran = $albaran->idalbaran;
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
                  $this->new_error_msg("¡Imposible guardar la línea del artículo ".$n->referencia."! ");
                  break;
               }
            }
         }
         
         if($continuar)
         {
            foreach($pedidos as $presu)
            {
               $presu->idalbaran = $albaran->idalbaran;
               $presu->ptealbaran = FALSE;
               
               if( !$presu->save() )
               {
                  $this->new_error_msg("¡Imposible vincular el pedido con el nuevo albarán!");
                  $continuar = FALSE;
                  break;
               }
            }
         }
         else
         {
            if( $albaran->delete() )
               $this->new_error_msg("El albarán se ha borrado.");
            else
               $this->new_error_msg("¡Imposible borrar el albarán!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar el albarán!");
   }
}
