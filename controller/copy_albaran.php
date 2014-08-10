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
require_model('almacen.php');
require_model('articulo.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('forma_pago.php');
require_model('impuesto.php');
require_model('proveedor.php');
require_model('serie.php');

class copy_albaran extends fs_controller
{
   public $albaran;
   public $almacen;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $forma_pago;
   public $proveedor;
   public $serie;
   public $tipo_albaran;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Copiar '.FS_ALBARAN, 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $albaran_cliente = new albaran_cliente();
      $albaran_proveedor = new albaran_proveedor();
      $this->almacen = new almacen();
      $this->cliente = new cliente();
      $this->divisa = new divisa();
      $this->ejercicio = new ejercicio();
      $this->forma_pago = new forma_pago();
      $this->proveedor = new proveedor();
      $this->serie = new serie();
      
      if( isset($_GET['idalbcli']) )
      {
         $this->albaran = $albaran_cliente->get($_GET['idalbcli']);
         $this->tipo_albaran = 'cliente';
      }
      else if( isset($_GET['idalbpro']) )
      {
         $this->albaran = $albaran_proveedor->get($_GET['idalbpro']);
         $this->tipo_albaran = 'proveedor';
      }
      else
      {
         $this->albaran = FALSE;
         $this->tipo_albaran = 'cliente';
         $this->new_error_msg('Ningún albaran seleccionado.');
      }
      
      if($this->albaran)
      {
         if($this->tipo_albaran == 'cliente')
            $this->ppage = $this->page->get('ventas_albaran');
         else
            $this->ppage = $this->page->get('compras_albaran');
         
         if($this->ppage)
         {
            $this->ppage->title = $this->albaran->codigo;
            $this->ppage->extra_url = '&id='.$this->albaran->idalbaran;
         }
         
         if( isset($_POST['tipo']) )
         {
            if($_POST['tipo'] == 'cliente')
               $this->nuevo_albaran_cliente();
            else
               $this->nuevo_albaran_proveedor();
         }
      }
   }
   
   public function url()
   {
      if( !isset($this->albaran) )
         return parent::url();
      else if($this->albaran)
      {
         if($this->tipo_albaran == 'cliente')
            return $this->page->url().'&idalbcli='.$this->albaran->idalbaran;
         else
            return $this->page->url().'&idalbpro='.$this->albaran->idalbaran;
      }
      else
         return $this->page->url();
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
      
      $albaran = new albaran_cliente();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón Guardar
               y se han enviado dos peticiones. Mira en <a href="'.$albaran->url().'">'.FS_ALBARANES.'</a>
               para ver si el '.FS_ALBARAN.' se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $albaran->fecha = $_POST['fecha'];
         $albaran->hora = $_POST['hora'];
         $albaran->codalmacen = $almacen->codalmacen;
         $albaran->codejercicio = $ejercicio->codejercicio;
         $albaran->codserie = $serie->codserie;
         $albaran->codpago = $forma_pago->codpago;
         $albaran->coddivisa = $divisa->coddivisa;
         $albaran->tasaconv = $divisa->tasaconv;
         $albaran->codagente = $this->user->codagente;
         $albaran->observaciones = $_POST['observaciones'];
         
         foreach($cliente->get_direcciones() as $d)
         {
            if($d->domfacturacion)
            {
               $albaran->codcliente = $cliente->codcliente;
               $albaran->cifnif = $cliente->cifnif;
               $albaran->nombrecliente = $cliente->nombre;
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
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         else if( $albaran->save() )
         {
            $articulo = new articulo();
            $impuesto = new impuesto();
            
            foreach($this->albaran->get_lineas() as $lin)
            {
               $art0 = $articulo->get($lin->referencia);
               if($art0)
               {
                  $linea = new linea_albaran_cliente();
                  $linea->idalbaran = $albaran->idalbaran;
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
                     $art0->sum_stock($albaran->codalmacen, 0 - $linea->cantidad);
                     
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
                  $this->new_error_msg("Artículo no encontrado: ".$lin->referencia);
                  $continuar = FALSE;
               }
            }
            
            if( $continuar )
            {
               /// redondeamos
               $albaran->neto = round($albaran->neto, 2);
               $albaran->totaliva = round($albaran->totaliva, 2);
               $albaran->total = $albaran->neto + $albaran->totaliva;
               
               if( $albaran->save() )
                  $this->new_message("<a href='".$albaran->url()."'>".FS_ALBARAN."</a> guardado correctamente.");
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
            }
            else if( $albaran->delete() )
               $this->new_message(FS_ALBARAN." eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_ALBARAN."!");
      }
   }
   
   private function nuevo_albaran_proveedor()
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
      
      $albaran = new albaran_proveedor();
      
      if( $this->duplicated_petition($_POST['petition_id']) )
      {
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón Guardar
               y se han enviado dos peticiones. Mira en <a href="'.$albaran->url().'">'.FS_ALBARANES.'</a>
               para ver si el '.FS_ALBARAN.' se ha guardado correctamente.');
         $continuar = FALSE;
      }
      
      if( $continuar )
      {
         $albaran->fecha = $_POST['fecha'];
         $albaran->hora = $_POST['hora'];
         $albaran->codproveedor = $proveedor->codproveedor;
         $albaran->nombre = $proveedor->nombre;
         $albaran->cifnif = $proveedor->cifnif;
         $albaran->codalmacen = $almacen->codalmacen;
         $albaran->codejercicio = $ejercicio->codejercicio;
         $albaran->codserie = $serie->codserie;
         $albaran->codpago = $forma_pago->codpago;
         $albaran->coddivisa = $divisa->coddivisa;
         $albaran->tasaconv = $divisa->tasaconv;
         $albaran->codagente = $this->user->codagente;
         $albaran->observaciones = $_POST['observaciones'];
         
         if( $albaran->save() )
         {
            foreach($this->albaran->get_lineas() as $lin)
            {
               $articulo = new articulo();
               $impuesto = new impuesto();
               
               $art0 = $articulo->get($lin->referencia);
               if($art0)
               {
                  $linea = new linea_albaran_proveedor();
                  $linea->idalbaran = $albaran->idalbaran;
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
                     $art0->sum_stock($albaran->codalmacen, $linea->cantidad);
                     
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
                  $this->new_error_msg("Artículo no encontrado: ".$lin->referencia);
                  $continuar = FALSE;
               }
            }
            
            if($continuar)
            {
               /// redondeamos
               $albaran->neto = round($albaran->neto, 2);
               $albaran->totaliva = round($albaran->totaliva, 2);
               $albaran->total = $albaran->neto + $albaran->totaliva;
               
               if( $albaran->save() )
                  $this->new_message("<a href='".$albaran->url()."'>".FS_ALBARAN."</a> guardado correctamente.");
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
            }
            else if( $albaran->delete() )
               $this->new_message(FS_ALBARAN." eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$albaran->url()."'>".FS_ALBARAN."</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el ".FS_ALBARAN."!");
      }
   }
}
