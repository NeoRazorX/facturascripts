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

require_model('pedido_cliente.php');
require_model('pedido_proveedor.php');
require_model('almacen.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('proveedor.php');
require_model('serie.php');

class general_copy_pedido extends fs_controller
{
   public $pedido;
   public $almacen;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $forma_pago;
   public $proveedor;
   public $serie;
   public $tipo_pedido;
   
   public function __construct()
   {
      parent::__construct('general_copy_pedido', 'Copiar pedido', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $pedido_cliente = new pedido_cliente();
      $pedido_proveedor = new pedido_proveedor();
      $this->almacen = new almacen();
      $this->cliente = new cliente();
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->forma_pago = new forma_pago();
      $this->proveedor = new proveedor();
      $this->serie = new serie();
      
      if( isset($_GET['idprecli']) )
      {
         $this->pedido = $pedido_cliente->get($_GET['idprecli']);
         $this->tipo_pedido = 'cliente';
      }
      else if( isset($_GET['idprepro']) )
      {
         $this->pedido = $pedido_proveedor->get($_GET['idprepro']);
         $this->tipo_pedido = 'proveedor';
      }
      else
      {
         $this->pedido = FALSE;
         $this->tipo_pedido = 'cliente';
         $this->new_error_msg('Ningún pedido seleccionado.');
      }
      
      if($this->pedido)
      {
         if($this->tipo_pedido == 'cliente')
            $this->ppage = $this->page->get('general_pedido_cli');
         else
            $this->ppage = $this->page->get('general_pedido_prov');
         
         if($this->ppage)
         {
            $this->ppage->title = $this->pedido->codigo;
            $this->ppage->extra_url = '&id='.$this->pedido->idpedido;
         }
         
         if( isset($_POST['tipo']) )
         {
            if($_POST['tipo'] == 'cliente')
               $this->nuevo_pedido_cliente();
            else
               $this->nuevo_pedido_proveedor();
         }
      }
   }
   
   public function url()
   {
      if( !isset($this->pedido) )
         return parent::url();
      else if($this->pedido)
      {
         if($this->tipo_pedido == 'cliente')
            return $this->page->url().'&idprecli='.$this->pedido->idpedido;
         else
            return $this->page->url().'&idprepro='.$this->pedido->idpedido;
      }
      else
         return $this->page->url();
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
      
      $ejercicio = $this->ejercicio->get_by_fecha($_POST['fecha']);
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
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guadar
               y se han enviado dos peticiones. Mira en <a href="'.$pedido->url().'">pedidos</a>
               para ver si el pedido se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $pedido->fecha = $_POST['fecha'];
         $pedido->hora = $_POST['hora'];
         $pedido->codalmacen = $almacen->codalmacen;
         $pedido->codejercicio = $ejercicio->codejercicio;
         $pedido->codserie = $serie->codserie;
         $pedido->codpago = $forma_pago->codpago;
         $pedido->coddivisa = $divisa->coddivisa;
         $pedido->tasaconv = $divisa->tasaconv;
         $pedido->codagente = $this->user->codagente;
         $pedido->observaciones = $_POST['observaciones'];
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->get_direcciones)
            {
               $pedido->codcliente = $cliente->codcliente;
               $pedido->cifnif = $cliente->cifnif;
               $pedido->nombrecliente = $cliente->nombre;
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
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         else if( $pedido->save() )
         {
            $articulo = new articulo();
            $impuesto = new impuesto();
            
            foreach($this->pedido->get_lineas() as $lin)
            {
               $art0 = $articulo->get($lin->referencia);
               if($art0)
               {
                  $linea = new linea_pedido_cliente();
                  $linea->idpedido = $pedido->idpedido;
                  $linea->referencia = $lin->referencia;
                  $linea->descripcion = $lin->descripcion;
                  
                  if( $serie->siniva )
                  {
                     $linea->codimpuesto = NULL;
                     $linea->iva = 0;
                  }
                  else
                  {
                     $imp0 = $impuesto->get_by_iva($lin->iva);
                     if($imp0)
                     {
                        $linea->codimpuesto = $imp0->codimpuesto;
                        $linea->iva = $imp0->iva;
                     }
                     else
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = $lin->iva;
                     }
                  }
                  
                  $linea->pvpunitario = $lin->pvpunitario;
                  $linea->cantidad = $lin->cantidad;
                  $linea->dtopor = $lin->dtopor;
                  $linea->pvpsindto = $lin->pvpsindto;
                  $linea->pvptotal = $lin->pvptotal;
                  
                  if( $linea->save() )
                  {
                     /// descontamos del stock
                     $art0->sum_stock($pedido->codalmacen, 0 - $linea->cantidad);
                     
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
                  $this->new_error_msg("Artículo no encontrado: ".$lin->referencia);
                  $continuar = FALSE;
               }
            }
            
            if( $continuar )
            {
               /// redondeamos
               $pedido->neto = round($pedido->neto, 2);
               $pedido->totaliva = round($pedido->totaliva, 2);
               $pedido->total = $pedido->neto + $pedido->totaliva;
               
               if( $pedido->save() )
                  $this->new_message("<a href='".$pedido->url()."'>pedido</a> guardado correctamente.");
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$pedido->url()."'>pedido</a>!");
            }
            else if( $pedido->delete() )
               $this->new_message("Pedido eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$pedido->url()."'>pedido</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el pedido!");
      }
   }
   
   private function nuevo_pedido_proveedor()
   {
      $continuar = TRUE;
      
      $proveedor = $this->proveedor->get($_POST['proveedor']);
      if( $proveedor )
         $this->save_codproveedor( $proveedor->codproveedor );
      else
      {
         $this->new_error_msg('Proveedor no encontrado.');
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
      
      $ejercicio = $this->ejercicio->get_by_fecha($_POST['fecha']);
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
      
      $pedido = new pedido_proveedor();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guadar
               y se han enviado dos peticiones. Mira en <a href="'.$pedido->url().'">pedidos</a>
               para ver si el pedido se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $pedido->fecha = $_POST['fecha'];
         $pedido->hora = $_POST['hora'];
         $pedido->codproveedor = $proveedor->codproveedor;
         $pedido->nombre = $proveedor->nombre;
         $pedido->cifnif = $proveedor->cifnif;
         $pedido->codalmacen = $almacen->codalmacen;
         $pedido->codejercicio = $ejercicio->codejercicio;
         $pedido->codserie = $serie->codserie;
         $pedido->codpago = $forma_pago->codpago;
         $pedido->coddivisa = $divisa->coddivisa;
         $pedido->tasaconv = $divisa->tasaconv;
         $pedido->codagente = $this->user->codagente;
         $pedido->observaciones = $_POST['observaciones'];
         
         if( $pedido->save() )
         {
            foreach($this->pedido->get_lineas() as $lin)
            {
               $articulo = new articulo();
               $impuesto = new impuesto();
               
               $art0 = $articulo->get($lin->referencia);
               if($art0)
               {
                  $linea = new linea_pedido_proveedor();
                  $linea->idpedido = $pedido->idpedido;
                  $linea->referencia = $art0->referencia;
                  $linea->descripcion = $lin->descripcion;
                  
                  if( $serie->siniva )
                  {
                     $linea->codimpuesto = NULL;
                     $linea->iva = 0;
                  }
                  else
                  {
                     $imp0 = $impuesto->get_by_iva($lin->iva);
                     if($imp0)
                     {
                        $linea->codimpuesto = $imp0->codimpuesto;
                        $linea->iva = $imp0->iva;
                     }
                     else
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = $lin->iva;
                     }
                  }
                  
                  $linea->pvpunitario = $lin->pvpunitario;
                  $linea->cantidad = $lin->cantidad;
                  $linea->dtopor = $lin->dtopor;
                  $linea->pvpsindto = $lin->pvpsindto;
                  $linea->pvptotal = $lin->pvptotal;
                  
                  if( $linea->save() )
                  {
                     /// sumamos al stock
                     $art0->sum_stock($pedido->codalmacen, $linea->cantidad);
                     
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
                  $this->new_error_msg("Artículo no encontrado: ".$lin->referencia);
                  $continuar = FALSE;
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $pedido->neto = round($pedido->neto, 2);
               $pedido->totaliva = round($pedido->totaliva, 2);
               $pedido->total = $pedido->neto + $pedido->totaliva;
               
               if( $pedido->save() )
                  $this->new_message("<a href='".$pedido->url()."'>pedido</a> guardado correctamente.");
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$pedido->url()."'>pedido</a>!");
            }
            else if( $pedido->delete() )
               $this->new_message("Pedido eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$pedido->url()."'>pedido</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el pedido!");
      }
   }
}

?>
