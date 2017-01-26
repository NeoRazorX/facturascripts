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
require_model('asiento_factura.php');
require_model('cliente.php');
require_model('ejercicio.php');
require_model('factura_cliente.php');
require_model('factura_proveedor.php');
require_model('partida.php');
require_model('proveedor.php');
require_model('regularizacion_iva.php');
require_model('regularizacion_stock.php');
require_model('serie.php');
require_model('subcuenta.php');

class megafacturador extends fs_controller
{
   private $cliente;
   private $forma_pago;
   private $ejercicio;
   private $proveedor;
   public $opciones;
   private $regularizacion;
   private $total;
   
   public function __construct()
   {
      parent::__construct('megafacturador', 'MegaFacturador', 'ventas', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      
      $this->cliente = new cliente();
      $this->ejercicio = new ejercicio();
      $this->proveedor = new proveedor();
      $this->regularizacion = new regularizacion_iva();
      
      $this->opciones = array(
          'ventas' => TRUE,
          'compras' => TRUE,
          'fecha' => 'hoy'
      );
      
      if( isset($_REQUEST['fecha']) )
      {
         $this->opciones['fecha'] = $_REQUEST['fecha'];
         
         $this->total = 0;
         if( isset($_REQUEST['ventas']) )
         {
            $albaran_cli = new albaran_cliente();
            foreach($albaran_cli->all_ptefactura(0, 'ASC') as $alb)
            {
               $this->generar_factura_cliente( array($alb) );
            }
            $this->new_message($this->total.' '.FS_ALBARANES.' de cliente facturados.');
         }
         else
            $this->opciones['ventas'] = FALSE;
         
         $this->total = 0;
         if( isset($_REQUEST['compras']) )
         {
            $albaran_pro = new albaran_proveedor();
            foreach($albaran_pro->all_ptefactura(0, 'ASC') as $alb)
            {
               $this->generar_factura_proveedor( array($alb) );
            }
            $this->new_message($this->total.' '.FS_ALBARANES.' de proveedor facturados.');
         }
         else
            $this->opciones['compras'] = FALSE;
      }
   }
   
   private function generar_factura_cliente($albaranes)
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
      $factura->irpf = $albaranes[0]->irpf;
      $factura->numero2 = $albaranes[0]->numero2;
      $factura->observaciones = $albaranes[0]->observaciones;
      $factura->recfinanciero = $albaranes[0]->recfinanciero;
      
      if( $_REQUEST['fecha'] == 'albaran' )
      {
         $factura->fecha = $albaranes[0]->fecha;
      }
      
      /// comprobamos la forma de pago para saber si hay que marcar la factura como pagada
      $formapago = $this->forma_pago->get($factura->codpago);
      if($formapago)
      {
         if($formapago->genrecibos == 'Pagados')
         {
            $factura->pagada = TRUE;
         }
      }
      
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
            $factura->neto += $l->pvptotal;
            $factura->totaliva += $l->pvptotal * $l->iva/100;
            $factura->totalirpf += $l->pvptotal * $l->irpf/100;
            $factura->totalrecargo += $l->pvptotal * $l->recargo/100;
            
            //actualizamos el stock real del producto
            $this->calcular_stock_real($l->referencia,$albaranes[0]->codalmacen);
         }
      }
      
      /// redondeamos
      $factura->neto = round($factura->neto, FS_NF0);
      $factura->totaliva = round($factura->totaliva, FS_NF0);
      $factura->totalirpf = round($factura->totalirpf, FS_NF0);
      $factura->totalrecargo = round($factura->totalrecargo, FS_NF0);
      $factura->total = $factura->neto + $factura->totaliva - $factura->totalirpf + $factura->totalrecargo;
      
      /// asignamos la mejor fecha posible, pero dentro del ejercicio
      $eje0 = $this->ejercicio->get($factura->codejercicio);
      $factura->fecha = $eje0->get_best_fecha($factura->fecha);
      
      if( !$eje0->abierto() )
      {
         $this->new_error_msg('El ejercicio '.$eje0->codejercicio.' está cerrado.');
      }
      else if( $this->regularizacion->get_fecha_inside($factura->fecha) )
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
   
   private function generar_asiento_cliente($factura)
   {
      if($this->empresa->contintegrada)
      {
         $asiento_factura = new asiento_factura();
         $asiento_factura->generar_asiento_venta($factura);
         
         foreach($asiento_factura->errors as $err)
         {
            $this->new_error_msg($err);
         }
         
         foreach($asiento_factura->messages as $msg)
         {
            $this->new_message($msg);
         }
      }
   }
   
   private function generar_factura_proveedor($albaranes)
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
      
      if( $_REQUEST['fecha'] == 'albaran' )
      {
         $factura->fecha = $albaranes[0]->fecha;
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
      $eje0 = $this->ejercicio->get($factura->codejercicio);
      $factura->fecha = $eje0->get_best_fecha($factura->fecha);
      
      if( !$eje0->abierto() )
      {
         $this->new_error_msg('El ejercicio '.$eje0->codejercicio.' está cerrado.');
      }
      else if( $this->regularizacion->get_fecha_inside($factura->fecha) )
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
               $this->generar_asiento_proveedor($factura);
               $this->total++;
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
   
   private function generar_asiento_proveedor($factura)
   {
      if($this->empresa->contintegrada)
      {
         $asiento_factura = new asiento_factura();
         $asiento_factura->generar_asiento_compra($factura);
         
         foreach($asiento_factura->errors as $err)
         {
            $this->new_error_msg($err);
         }
         
         foreach($asiento_factura->messages as $msg)
         {
            $this->new_message($msg);
         }
      }
   }
   
   public function pendientes_venta()
   {
      $total = 0;
      
      $data = $this->db->select("SELECT count(idalbaran) as total FROM albaranescli WHERE ptefactura = true;");
      if($data)
      {
         $total = intval($data[0]['total']);
      }
      
      return $total;
   }
   
   public function pendientes_compra()
   {
      $total = 0;
      
      $data = $this->db->select("SELECT count(idalbaran) as total FROM albaranesprov WHERE ptefactura = true;");
      if($data)
      {
         $total = intval($data[0]['total']);
      }
      
      return $total;
   }
   private function calcular_stock_real($ref,$codalmacen)
   {

      $articulo = new articulo;
      $articulo = $articulo->get($ref);

      $almacen = new almacen;
      $almacen = $almacen->get($codalmacen);

      if($articulo){
         foreach($almacen->all() as $alm)
         {
            $total = 0;
            foreach($this->get_movimientos($articulo->referencia) as $mov)
            {
               if($mov['codalmacen'] == $alm->codalmacen)
               {
                  $total = $mov['final'];
               }
            }
            
            if( !$articulo->set_stock($alm->codalmacen, $total) )
            {
               $this->new_error_msg('Error al recarcular el stock del almacén '.$alm->codalmacen.'.');
            }
         }
      }
   }

   private function get_movimientos($ref, $desde='', $hasta='', $codagente='')
   {
      $mlist = array();
      $regularizacion = new regularizacion_stock();
      
      foreach($regularizacion->all_from_articulo($ref) as $reg)
      {
         $anyadir = TRUE;
         if($desde != '')
         {
            if( strtotime($desde) > strtotime($reg->fecha) )
            {
               $anyadir = FALSE;
            }
         }
         
         if($hasta != '')
         {
            if( strtotime($hasta) < strtotime($reg->fecha) )
            {
               $anyadir = FALSE;
            }
         }
         
         if($anyadir)
         {
            $mlist[] = array(
                'referencia' => $ref,
                'codalmacen' => $reg->codalmacendest,
                'origen' => 'Regularización',
                'url' => 'index.php?page=ventas_articulo&ref='.$ref,
                'clipro' => '-',
                'movimiento' => '-',
                'precio' => 0,
                'dto' => 0,
                'final' => $reg->cantidadfin,
                'fecha' => $reg->fecha,
                'hora' => $reg->hora
            );
         }
      }
      
      /// forzamos la comprobación de las tablas de albaranes
      $albc = new albaran_cliente();
      $lin1 = new linea_albaran_cliente();
      $albp = new albaran_proveedor();
      $lin2 = new linea_albaran_proveedor();
      
      $sql_extra = '';
      if($desde != '')
      {
         $sql_extra .= " AND fecha >= ".$this->empresa->var2str($desde);
      }
      
      if($hasta != '')
      {
         $sql_extra .= " AND fecha <= ".$this->empresa->var2str($hasta);
      }
      
      if($codagente != '')
      {
         $sql_extra .= " AND codagente = ".$this->empresa->var2str($codagente);
      }
      
      /// buscamos el artículo en albaranes de compra
      $sql = "SELECT a.codigo,l.cantidad,l.pvpunitario,l.dtopor,a.fecha,a.hora"
              .",a.codalmacen,a.idalbaran,a.codproveedor,a.nombre"
              ." FROM albaranesprov a, lineasalbaranesprov l"
              ." WHERE a.idalbaran = l.idalbaran AND l.referencia = ".$albc->var2str($ref).$sql_extra;
      
      $data = $this->db->select_limit($sql, 1000, 0);
      if($data)
      {
         foreach($data as $d)
         {
            $mlist[] = array(
                'referencia' => $ref,
                'codalmacen' => $d['codalmacen'],
                'origen' => 'Albaran compra '.$d['codigo'],
                'url' => 'index.php?page=compras_albaran&id='.$d['idalbaran'],
                'clipro' => $d['codproveedor'].' - '.$d['nombre'],
                'movimiento' => floatval($d['cantidad']),
                'precio' => floatval($d['pvpunitario']),
                'dto' => floatval($d['dtopor']),
                'final' => 0,
                'fecha' => date('d-m-Y', strtotime($d['fecha'])),
                'hora' => $d['hora']
            );
         }
      }
      
      /// buscamos el artículo en facturas de compra
      $sql = "SELECT f.codigo,l.cantidad,l.pvpunitario,l.dtopor,f.fecha,f.hora"
              .",f.codalmacen,f.idfactura,f.codproveedor,f.nombre"
              ." FROM facturasprov f, lineasfacturasprov l"
              ." WHERE f.idfactura = l.idfactura AND l.idalbaran IS NULL"
              ." AND l.referencia = ".$albc->var2str($ref).$sql_extra;
      
      $data = $this->db->select_limit($sql, 1000, 0);
      if($data)
      {
         foreach($data as $d)
         {
            $mlist[] = array(
                'referencia' => $ref,
                'codalmacen' => $d['codalmacen'],
                'origen' => 'Factura compra '.$d['codigo'],
                'url' => 'index.php?page=compras_factura&id='.$d['idfactura'],
                'clipro' => $d['codproveedor'].' - '.$d['nombre'],
                'movimiento' => floatval($d['cantidad']),
                'precio' => floatval($d['pvpunitario']),
                'dto' => floatval($d['dtopor']),
                'final' => 0,
                'fecha' => date('d-m-Y', strtotime($d['fecha'])),
                'hora' => $d['hora']
            );
         }
      }
      
      /// buscamos el artículo en albaranes de venta
      $sql = "SELECT a.codigo,l.cantidad,l.pvpunitario,l.dtopor,a.fecha,a.hora"
              .",a.codalmacen,a.idalbaran,a.codcliente,a.nombrecliente"
              ." FROM albaranescli a, lineasalbaranescli l"
              ." WHERE a.idalbaran = l.idalbaran AND l.referencia = ".$albc->var2str($ref).$sql_extra;
      
      $data = $this->db->select_limit($sql, 1000, 0);
      if($data)
      {
         foreach($data as $d)
         {
            $mlist[] = array(
                'referencia' => $ref,
                'codalmacen' => $d['codalmacen'],
                'origen' => 'Albaran venta '.$d['codigo'],
                'url' => 'index.php?page=ventas_albaran&id='.$d['idalbaran'],
                'clipro' => $d['codcliente'].' - '.$d['nombrecliente'],
                'movimiento' => 0-floatval($d['cantidad']),
                'precio' => floatval($d['pvpunitario']),
                'dto' => floatval($d['dtopor']),
                'final' => 0,
                'fecha' => date('d-m-Y', strtotime($d['fecha'])),
                'hora' => $d['hora']
            );
         }
      }
      
      /// buscamos el artículo en facturas de venta
      $sql = "SELECT f.codigo,l.cantidad,l.pvpunitario,l.dtopor,f.fecha,f.hora"
              .",f.codalmacen,f.idfactura,f.codcliente,f.nombrecliente"
              ." FROM facturascli f, lineasfacturascli l"
              ." WHERE f.idfactura = l.idfactura AND l.idalbaran IS NULL"
              ." AND l.referencia = ".$albc->var2str($ref).$sql_extra;
      
      $data = $this->db->select_limit($sql, 1000, 0);
      if($data)
      {
         foreach($data as $d)
         {
            $mlist[] = array(
                'referencia' => $ref,
                'codalmacen' => $d['codalmacen'],
                'origen' => 'Factura venta '.$d['codigo'],
                'url' => 'index.php?page=ventas_factura&id='.$d['idfactura'],
                'clipro' => $d['codcliente'].' - '.$d['nombrecliente'],
                'movimiento' => 0-floatval($d['cantidad']),
                'precio' => floatval($d['pvpunitario']),
                'dto' => floatval($d['dtopor']),
                'final' => 0,
                'fecha' => date('d-m-Y', strtotime($d['fecha'])),
                'hora' => $d['hora']
            );
         }
      }
      
      /// ordenamos por fecha y hora
      usort($mlist, function($a,$b) {
         if( strtotime($a['fecha'].' '.$a['hora']) == strtotime($b['fecha'].' '.$b['hora']) )
         {
            return 0;
         }
         else if( strtotime($a['fecha'].' '.$a['hora']) < strtotime($b['fecha'].' '.$b['hora']) )
         {
            return -1;
         }
         else
            return 1;
      });
      
      /// recalculamos
      $inicial = 0;
      foreach($mlist as $i => $value)
      {
         if($value['movimiento'] == '-')
         {
            $inicial = $value['final'];
         }
         else
            $inicial += $value['movimiento'];
         
         $mlist[$i]['final'] = $inicial;
      }
      
      return $mlist;
   }
}
