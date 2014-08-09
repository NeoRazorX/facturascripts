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

require_model('almacen.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('familia.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('serie.php');

class nueva_venta extends fs_controller
{
   public $agente;
   public $almacen;
   public $articulo;
   public $cliente;
   public $cliente_s;
   public $divisa;
   public $familia;
   public $forma_pago;
   public $impuesto;
   public $results;
   public $serie;
   public $tipo;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'nueva venta', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->cliente = new cliente();
      $this->cliente_s = FALSE;
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->results = array();
      
      if( isset($_GET['tipo']) )
      {
         $this->tipo = $_GET['tipo'];
      }
      else
      {
         foreach($this->tipos_a_guardar() as $t)
         {
            $this->tipo = $t['tipo'];
            break;
         }
      }
      
      if( isset($_GET['new_articulo']) )
      {
         $this->new_articulo();
      }
      else if( $this->query != '' )
      {
         $this->new_search();
      }
      else if( isset($_POST['referencia4precios']) )
      {
         $this->get_precios_articulo();
      }
      else if( isset($_POST['cliente']) )
      {
         $this->cliente_s = $this->cliente->get($_POST['cliente']);
         
         if( isset($_POST['codagente']) )
         {
            $agente = new agente();
            $this->agente = $agente->get($_POST['codagente']);
         }
         else
            $this->agente = $this->user->get_agente();
         
         $this->almacen = new almacen();
         $this->serie = new serie();
         $this->forma_pago = new forma_pago();
         $this->divisa = new divisa();
         
         if( isset($_POST['tipo']) )
         {
            $this->tipo = $_POST['tipo'];

            if($_POST['tipo'] == 'presupuesto')
            {
               $this->nuevo_presupuesto_cliente();
            }
            else if($_POST['tipo'] == 'pedido')
            {
               $this->nueva_pedido_cliente();
            }            
            else if($_POST['tipo'] == 'albaran')
            {
               $this->nuevo_albaran_cliente();
            }
            else if($_POST['tipo'] == 'factura')
            {
               $this->nueva_factura_cliente();
            }
         }
      }
   }
   
   /**
    * Devuelve los tipos de documentos a guardar,
    * así para añadir tipos no hay que tocar la vista.
    * @return type
    */
   public function tipos_a_guardar()
   {
      return array(
          array('tipo' => 'presupuesto', 'nombre' => 'Presupuesto a cliente'),
          array('tipo' => 'pedido', 'nombre' => 'Pedido de cliente')
          array('tipo' => 'albaran', 'nombre' => ucfirst(FS_ALBARAN).' de cliente'),
          array('tipo' => 'factura', 'nombre' => 'Factura de cliente')
      );
   }
   
   public function url()
   {
      return 'index.php?page='.__CLASS__.'&tipo='.$this->tipo;
   }
   
   private function new_articulo()
   {
      $art0 = new articulo();
      $art0->referencia = $_POST['referencia'];
      $art0->descripcion = $_POST['descripcion'];
      $art0->codfamilia = $_POST['codfamilia'];
      $art0->set_impuesto($_POST['codimpuesto']);
      
      if( $art0->save() )
         $this->results[] = $art0;
      
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/nueva_venta';
   }
   
   private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/nueva_venta';
      
      $articulo = new articulo();
      $codfamilia = '';
      if( isset($_POST['codfamilia']) )
         $codfamilia = $_POST['codfamilia'];
      
      $con_stock = isset($_POST['con_stock']);
      $this->results = $articulo->search($this->query, 0, $codfamilia, $con_stock);
      
      $cliente = $this->cliente->get($_POST['codcliente']);
      if($cliente)
      {
         if($cliente->regimeniva == 'Exento')
         {
            foreach($this->results as $i => $value)
               $this->results[$i]->iva = 0;
         }
      }
   }
   
   private function nuevo_presupuesto_cliente()
   {
      $continuar = TRUE;
      
      $cliente = $this->cliente->get($_POST['cliente']);
      if( $cliente )
         $this->save_codcliente( $cliente->codcliente );
      else
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( $serie )
         $this->save_codserie( $serie->codserie );
      else
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $presupuesto = new presupuesto_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$presupuesto->url().'">Presupuestos</a>
               para ver si el presupuesto se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if($continuar)
      {
         $presupuesto->fecha = $_POST['fecha'];
         $presupuesto->hora = $_POST['hora'];
         $presupuesto->codalmacen = $almacen->codalmacen;
         $presupuesto->codejercicio = $ejercicio->codejercicio;
         $presupuesto->codserie = $serie->codserie;
         $presupuesto->codpago = $forma_pago->codpago;
         $presupuesto->coddivisa = $divisa->coddivisa;
         $presupuesto->tasaconv = $divisa->tasaconv;
         $presupuesto->codagente = $this->agente->codagente;
         if( isset($_POST['numero2']) )
            $presupuesto->numero2 = $_POST['numero2'];
         $presupuesto->observaciones = $_POST['observaciones'];
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $presupuesto->codcliente = $cliente->codcliente;
               $presupuesto->cifnif = $cliente->cifnif;
               $presupuesto->nombrecliente = $cliente->nombrecomercial;
               $presupuesto->apartado = $d->apartado;
               $presupuesto->ciudad = $d->ciudad;
               $presupuesto->coddir = $d->id;
               $presupuesto->codpais = $d->codpais;
               $presupuesto->codpostal = $d->codpostal;
               $presupuesto->direccion = $d->direccion;
               $presupuesto->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($presupuesto->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $presupuesto->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_presupuesto_cliente();
                     $linea->idpresupuesto = $presupuesto->idpresupuesto;
                     $linea->referencia = $articulo->referencia;
                     
                     if( isset($_POST['desc_'.$i]) )
                        $linea->descripcion = $_POST['desc_'.$i];
                     else
                        $linea->descripcion = $articulo->descripcion;
                     
                     if( $serie->siniva OR $cliente->regimeniva == 'Exento' )
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = 0;
                     }
                     else
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = $imp0->iva;
                        }
                        else
                        {
                           $linea->codimpuesto = NULL;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                        }
                     }
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['total_'.$i]);
                     
                     if( $linea->save() )
                     {
                        /// descontamos del stock
                        $articulo->sum_stock($presupuesto->codalmacen, 0 - $linea->cantidad);
                        
                        $presupuesto->neto += $linea->pvptotal;
                        $presupuesto->totaliva += ($linea->pvptotal * $linea->iva/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                        $continuar = FALSE;
                     }
                  }
                  else
                  {
                     $this->new_error_msg("Artículo no encontrado: ".$_POST['referencia_'.$i]);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $presupuesto->neto = round($presupuesto->neto, 2);
               $presupuesto->totaliva = round($presupuesto->totaliva, 2);
               $presupuesto->total = $presupuesto->neto + $presupuesto->totaliva;
               
               if( $presupuesto->save() )
               {
                  $this->new_message("<a href='".$presupuesto->url()."'>Presupuesto</a> guardado correctamente.");
                  $this->new_change('Presupuesto a Cliente '.$presupuesto->codigo, $presupuesto->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$presupuesto->url()."'>Presupuesto</a>!");
            }
            else if( $presupuesto->delete() )
            {
               $this->new_message("Presupuesto eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$presupuesto->url()."'>Presupuesto</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el presupuesto!");
      }
   }
   
   private function nuevo_pedido_cliente()
   {
      $continuar = TRUE;
      
      $cliente = $this->cliente->get($_POST['cliente']);
      if( $cliente )
         $this->save_codcliente( $cliente->codcliente );
      else
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( $serie )
         $this->save_codserie( $serie->codserie );
      else
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $pedido = new pedido_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$pedido->url().'">Pedidos</a>
               para ver si el pedido se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if($continuar)
      {
         $pedido->fecha = $_POST['fecha'];
         $pedido->hora = $_POST['hora'];
         $pedido->codalmacen = $almacen->codalmacen;
         $pedido->codejercicio = $ejercicio->codejercicio;
         $pedido->codserie = $serie->codserie;
         $pedido->codpago = $forma_pago->codpago;
         $pedido->coddivisa = $divisa->coddivisa;
         $pedido->tasaconv = $divisa->tasaconv;
         $pedido->codagente = $this->agente->codagente;
         if( isset($_POST['numero2']) )
            $pedido->numero2 = $_POST['numero2'];
         $pedido->observaciones = $_POST['observaciones'];
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $pedido->codcliente = $cliente->codcliente;
               $pedido->cifnif = $cliente->cifnif;
               $pedido->nombrecliente = $cliente->nombrecomercial;
               $pedido->apartado = $d->apartado;
               $pedido->ciudad = $d->ciudad;
               $pedido->coddir = $d->id;
               $pedido->codpais = $d->codpais;
               $pedido->codpostal = $d->codpostal;
               $pedido->direccion = $d->direccion;
               $pedido->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($pedido->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $pedido->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_pedido_cliente();
                     $linea->idpedido = $pedido->idpedido;
                     $linea->referencia = $articulo->referencia;
                     
                     if( isset($_POST['desc_'.$i]) )
                        $linea->descripcion = $_POST['desc_'.$i];
                     else
                        $linea->descripcion = $articulo->descripcion;
                     
                     if( $serie->siniva OR $cliente->regimeniva == 'Exento' )
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = 0;
                     }
                     else
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = $imp0->iva;
                        }
                        else
                        {
                           $linea->codimpuesto = NULL;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                        }
                     }
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['total_'.$i]);
                     
                     if( $linea->save() )
                     {
                        /// descontamos del stock
                        $articulo->sum_stock($pedido->codalmacen, 0 - $linea->cantidad);
                        
                        $pedido->neto += $linea->pvptotal;
                        $pedido->totaliva += ($linea->pvptotal * $linea->iva/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                        $continuar = FALSE;
                     }
                  }
                  else
                  {
                     $this->new_error_msg("Artículo no encontrado: ".$_POST['referencia_'.$i]);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $pedido->neto = round($pedido->neto, 2);
               $pedido->totaliva = round($pedido->totaliva, 2);
               $pedido->total = $pedido->neto + $pedido->totaliva;
               
               if( $pedido->save() )
               {
                  $this->new_message("<a href='".$pedido->url()."'>Pedido</a> guardado correctamente.");
                  $this->new_change('Presupuesto a Cliente '.$pedido->codigo, $pedido->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$pedido->url()."'>Pedido</a>!");
            }
            else if( $pedido->delete() )
            {
               $this->new_message("Pedido eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$pedido->url()."'>Pedido</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el pedido!");
      }
   }
   
   private function nuevo_albaran_cliente()
   {
      $continuar = TRUE;
      
      $cliente = $this->cliente->get($_POST['cliente']);
      if( $cliente )
         $this->save_codcliente( $cliente->codcliente );
      else
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( $serie )
         $this->save_codserie( $serie->codserie );
      else
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $albaran = new albaran_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$albaran->url().'">'.FS_ALBARANES.'</a>
               para ver si el '.FS_ALBARAN.' se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if($continuar)
      {
         $albaran->fecha = $_POST['fecha'];
         $albaran->hora = $_POST['hora'];
         $albaran->codalmacen = $almacen->codalmacen;
         $albaran->codejercicio = $ejercicio->codejercicio;
         $albaran->codserie = $serie->codserie;
         $albaran->codpago = $forma_pago->codpago;
         $albaran->coddivisa = $divisa->coddivisa;
         $albaran->tasaconv = $divisa->tasaconv;
         $albaran->codagente = $this->agente->codagente;
         if( isset($_POST['numero2']) )
            $albaran->numero2 = $_POST['numero2'];
         $albaran->observaciones = $_POST['observaciones'];
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $albaran->codcliente = $cliente->codcliente;
               $albaran->cifnif = $cliente->cifnif;
               $albaran->nombrecliente = $cliente->nombrecomercial;
               $albaran->apartado = $d->apartado;
               $albaran->ciudad = $d->ciudad;
               $albaran->coddir = $d->id;
               $albaran->codpais = $d->codpais;
               $albaran->codpostal = $d->codpostal;
               $albaran->direccion = $d->direccion;
               $albaran->provincia = $d->provincia;
               break;
            }
         }
         
         if( is_null($albaran->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $albaran->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_albaran_cliente();
                     $linea->idalbaran = $albaran->idalbaran;
                     $linea->referencia = $articulo->referencia;
                     
                     if( isset($_POST['desc_'.$i]) )
                        $linea->descripcion = $_POST['desc_'.$i];
                     else
                        $linea->descripcion = $articulo->descripcion;
                     
                     if( $serie->siniva OR $cliente->regimeniva == 'Exento' )
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = 0;
                     }
                     else
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = $imp0->iva;
                        }
                        else
                        {
                           $linea->codimpuesto = NULL;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                        }
                     }
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['total_'.$i]);
                     
                     if( $linea->save() )
                     {
                        /// descontamos del stock
                        $articulo->sum_stock($albaran->codalmacen, 0 - $linea->cantidad);
                        
                        $albaran->neto += $linea->pvptotal;
                        $albaran->totaliva += ($linea->pvptotal * $linea->iva/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                        $continuar = FALSE;
                     }
                  }
                  else
                  {
                     $this->new_error_msg("Artículo no encontrado: ".$_POST['referencia_'.$i]);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $albaran->neto = round($albaran->neto, 2);
               $albaran->totaliva = round($albaran->totaliva, 2);
               $albaran->total = $albaran->neto + $albaran->totaliva;
               
               if( $albaran->save() )
               {
                  $this->new_message("<a href='".$albaran->url()."'>".ucfirst(FS_ALBARAN)."</a> guardado correctamente.");
                  $this->new_change(ucfirst(FS_ALBARAN).' Cliente '.$albaran->codigo, $albaran->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
            }
            else if( $albaran->delete() )
            {
               $this->new_message(FS_ALBARAN." eliminado correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_ALBARAN."!");
      }
   }
   
   private function nueva_factura_cliente()
   {
      $continuar = TRUE;
      
      $cliente = $this->cliente->get($_POST['cliente']);
      if( $cliente )
         $this->save_codcliente( $cliente->codcliente );
      else
      {
         $this->new_error_msg('Cliente no encontrado.');
         $continuar = FALSE;
      }
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $this->save_codalmacen( $almacen->codalmacen );
      else
      {
         $this->new_error_msg('Almacén no encontrado.');
         $continuar = FALSE;
      }
      
      $eje0 = new ejercicio();
      $ejercicio = $eje0->get_by_fecha($_POST['fecha']);
      if( $ejercicio )
         $this->save_codejercicio( $ejercicio->codejercicio );
      else
      {
         $this->new_error_msg('Ejercicio no encontrado.');
         $continuar = FALSE;
      }
      
      $serie = $this->serie->get($_POST['serie']);
      if( $serie )
         $this->save_codserie( $serie->codserie );
      else
      {
         $this->new_error_msg('Serie no encontrada.');
         $continuar = FALSE;
      }
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $this->save_codpago( $forma_pago->codpago );
      else
      {
         $this->new_error_msg('Forma de pago no encontrada.');
         $continuar = FALSE;
      }
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $this->save_coddivisa( $divisa->coddivisa );
      else
      {
         $this->new_error_msg('Divisa no encontrada.');
         $continuar = FALSE;
      }
      
      $factura = new factura_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guardar
               y se han enviado dos peticiones. Mira en <a href="'.$factura->url().'">Facturas</a>
               para ver si la factura se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if($continuar)
      {
         $factura->fecha = $_POST['fecha'];
         $factura->hora = $_POST['hora'];
         $factura->codalmacen = $almacen->codalmacen;
         $factura->codejercicio = $ejercicio->codejercicio;
         $factura->codserie = $serie->codserie;
         $factura->codpago = $forma_pago->codpago;
         $factura->coddivisa = $divisa->coddivisa;
         $factura->tasaconv = $divisa->tasaconv;
         $factura->codagente = $this->agente->codagente;
         $factura->observaciones = $_POST['observaciones'];
         
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
         
         if( is_null($factura->codcliente) )
         {
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         }
         else if( $factura->save() )
         {
            $art0 = new articulo();
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $art0->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_factura_cliente();
                     $linea->idfactura = $factura->idfactura;
                     $linea->referencia = $articulo->referencia;
                     
                     if( isset($_POST['desc_'.$i]) )
                        $linea->descripcion = $_POST['desc_'.$i];
                     else
                        $linea->descripcion = $articulo->descripcion;
                     
                     if( $serie->siniva )
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = 0;
                     }
                     else
                     {
                        $imp0 = $this->impuesto->get_by_iva($_POST['iva_'.$i]);
                        if($imp0)
                        {
                           $linea->codimpuesto = $imp0->codimpuesto;
                           $linea->iva = $imp0->iva;
                        }
                        else
                        {
                           $linea->codimpuesto = NULL;
                           $linea->iva = floatval($_POST['iva_'.$i]);
                        }
                     }
                     
                     $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                     $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                     $linea->dtopor = floatval($_POST['dto_'.$i]);
                     $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                     $linea->pvptotal = floatval($_POST['total_'.$i]);
                     
                     if( $linea->save() )
                     {
                        /// descontamos del stock
                        $articulo->sum_stock($factura->codalmacen, 0 - $linea->cantidad);
                        
                        $factura->neto += $linea->pvptotal;
                        $factura->totaliva += ($linea->pvptotal * $linea->iva/100);
                     }
                     else
                     {
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                        $continuar = FALSE;
                     }
                  }
                  else
                  {
                     $this->new_error_msg("Artículo no encontrado: ".$_POST['referencia_'.$i]);
                     $continuar = FALSE;
                  }
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $factura->neto = round($factura->neto, 2);
               $factura->totaliva = round($factura->totaliva, 2);
               $factura->total = $factura->neto + $factura->totaliva;
               
               if( $factura->save() )
               {
                  $this->new_message("<a href='".$factura->url()."'>Factura</a> guardada correctamente.");
                  $this->new_change('Factura Cliente '.$factura->codigo, $factura->url(), TRUE);
               }
               else
                  $this->new_error_msg("¡Imposible actualizar la <a href='".$factura->url()."'>Factura</a>!");
            }
            else if( $factura->delete() )
            {
               $this->new_message("Factura eliminada correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible eliminar la <a href='".$factura->url()."'>Factura</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar la Factura!");
      }
   }
   
   private function get_precios_articulo()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/nueva_venta_precios';
      
      $articulo = new articulo();
      $this->articulo = $articulo->get($_POST['referencia4precios']);
   }
}
