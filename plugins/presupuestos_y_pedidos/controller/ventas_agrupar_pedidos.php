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

require_model('pedido_cliente.php');
require_model('asiento.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('albaran_cliente.php');
require_model('partida.php');
require_model('regularizacion_iva.php');
require_model('serie.php');
require_model('subcuenta.php');

class ventas_agrupar_pedidos extends fs_controller
{
   public $pedido;
   public $cliente;
   public $desde;
   public $hasta;
   public $neto;
   public $observaciones;
   public $resultados;
   public $serie;
   public $total;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Agrupar '.FS_PEDIDOS, 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('ventas_pedidos');
      $this->pedido = new pedido_cliente();
      $this->cliente = new cliente();
      $this->serie = new serie();
      $this->neto = 0;
      $this->total = 0;
      
      $this->desde = Date('01-m-Y');
      if( isset($_POST['desde']) )
         $this->desde = $_POST['desde'];
      
      $this->hasta = Date('t-m-Y');
      if( isset($_POST['hasta']) )
         $this->hasta = $_POST['hasta'];
      
      $this->observaciones = '';
      if( isset($_POST['observaciones']) )
         $this->observaciones = $_POST['observaciones'];
      
      if( isset($_POST['idpedido']) )
      {
         $this->agrupar();
      }
      else if( isset($_POST['cliente']) )
      {
         $this->save_codcliente($_POST['cliente']);
         
         $this->resultados = $this->pedido->search_from_cliente($_POST['cliente'],
                 $_POST['desde'], $_POST['hasta'], $_POST['serie'], $_POST['observaciones']);
         
         if($this->resultados)
         {
            foreach($this->resultados as $ped)
            {
               $this->neto += $ped->neto;
               $this->total += $ped->total;
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
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón Guardar
               y se han enviado dos peticiones. Mira en <a href="'.$this->ppage->url().'">Pedidos</a>
               para ver si los pedidos se han guardado correctamente.');
         $continuar = FALSE;
      }
      else
      {
         foreach($_POST['idpedido'] as $id)
            $pedidos[] = $this->pedido->get($id);
         
         $codejercicio = NULL;
         foreach($pedidos as $ped)
         {
            if( !isset($codejercicio) )
               $codejercicio = $ped->codejercicio;
            
            if( !$ped->ptealbaran )
            {
               $this->new_error_msg("El ".FS_PEDIDOS." <a href='".$ped->url()."'>".$ped->codigo."</a> ya está procesado.");
               $continuar = FALSE;
               break;
            }
            else if($ped->codejercicio != $codejercicio)
            {
               $this->new_error_msg("Los ejercicios de los ".FS_PEDIDOS." no coinciden.");
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
            foreach($pedidos as $ped)
               $this->generar_albaran( array($ped) );
         }
         else
            $this->generar_albaran($pedidos);
      }
   }
   
   private function generar_albaran($pedidos)
   {
      $continuar = TRUE;
      
      $factura = new factura_cliente();
      $factura->automatica = TRUE;
      $factura->codalmacen = $albaranes[0]->codalmacen;
      $factura->coddivisa = $albaranes[0]->coddivisa;
      $factura->tasaconv = $albaranes[0]->tasaconv;
      $factura->codejercicio = $albaranes[0]->codejercicio;
      $factura->codpago = $albaranes[0]->codpago;
      $factura->codserie = $albaranes[0]->codserie;
      $factura->editable = FALSE;
      
      /// obtenemos los datos actuales del cliente, por si ha habido cambios
      $cliente = $this->cliente->get($albaranes[0]->codcliente);
      if($cliente)
      {
         foreach($cliente->get_direcciones() as $dir)
         {
            if($dir->domfacturacion)
            {
               $factura->apartado = $dir->apartado;
               $factura->cifnif = $cliente->cifnif;
               $factura->ciudad = $dir->ciudad;
               $factura->codcliente = $cliente->codcliente;
               $factura->coddir = $dir->id;
               $factura->codpais = $dir->codpais;
               $factura->codpostal = $dir->codpostal;
               $factura->direccion = $dir->direccion;
               $factura->nombrecliente = $cliente->nombrecomercial;
               $factura->provincia = $dir->provincia;
               break;
            }
         }
      }
      
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
      $ejercicio = new ejercicio();
      $eje0 = $ejercicio->get($factura->codejercicio);
      $factura->fecha = $eje0->get_best_fecha($factura->fecha);
      
      /*
       * comprobamos que la fecha del pedido no esté dentro de un periodo de
       * IVA regularizado.
       */
      $regularizacion = new regularizacion_iva();
      
      if( $regularizacion->get_fecha_inside($factura->fecha) )
      {
         $this->new_error_msg('El IVA de ese periodo ya ha sido regularizado.
            No se pueden añadir más '.FS_PEDIDOS.' en esa fecha.');
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
                  $this->new_error_msg("¡Imposible vincular el ".FS_ALBARAN." con el nuevo ".FS_PEDIDO."!");
                  $continuar = FALSE;
                  break;
               }
            }
            
            if( $factura->delete() )
            {
               $this->new_error_msg("El ".FS_PEDIDO." se ha borrado.");
            }
            else
               $this->new_error_msg("¡Imposible borrar EL ".FS_PEDIDO."!");
         }
         else
         {
            if( $factura->delete() )
            {
               $this->new_error_msg("El ".FS_PEDIDO." se ha borrado.");
            }
            else
               $this->new_error_msg("¡Imposible borrar el ".FS_PEDIDO."!");
         }
      }
      else
         $this->new_error_msg("¡Imposible guardar el ".FS_PEDIDO."!");
   }
}
