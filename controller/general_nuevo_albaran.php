<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'model/agente.php';
require_once 'model/albaran_cliente.php';
require_once 'model/albaran_proveedor.php';
require_once 'model/almacen.php';
require_once 'model/articulo.php';
require_once 'model/cliente.php';
require_once 'model/divisa.php';
require_once 'model/ejercicio.php';
require_once 'model/familia.php';
require_once 'model/forma_pago.php';
require_once 'model/impuesto.php';
require_once 'model/proveedor.php';
require_once 'model/serie.php';

class general_nuevo_albaran extends fs_controller
{
   public $agente;
   public $almacen;
   public $articulo;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $familia;
   public $forma_pago;
   public $impuesto;
   public $proveedor;
   public $results;
   public $serie;
   
   public function __construct()
   {
      parent::__construct('general_nuevo_albaran', 'nuevo albarán', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->articulo = new articulo();
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      $this->results = array();
      
      if( isset($_GET['new_articulo']) )
         $this->new_articulo();
      else if( $this->query != '' )
         $this->new_search();
      else if( isset($_POST['referencia4precios']) )
         $this->get_precios_articulo();
      else
      {
         if( isset($_POST['codagente']) )
         {
            $agente = new agente();
            $this->agente = $agente->get($_POST['codagente']);
         }
         else
            $this->agente = $this->user->get_agente();
         
         $this->almacen = new almacen();
         $this->cliente = new cliente();
         $this->divisa = new divisa();
         $this->ejercicio = new ejercicio();
         $this->forma_pago = new forma_pago();
         $this->proveedor = new proveedor();
         $this->serie = new serie();
         
         if( !$this->agente )
         {
            $this->new_error_msg('No tienes un <a href="'.$this->user->url().'">agente asociado</a>
               a tu usuario, y por tanto no puedes hacer tickets.');
         }
         else if( isset($_POST['tipoalbaran']) )
         {
            if($_POST['tipoalbaran'] == 'cliente')
               $this->nuevo_albaran_cliente();
            else if($_POST['tipoalbaran'] == 'proveedor')
               $this->nuevo_albaran_proveedor();
         }
      }
   }
   
   private function new_articulo()
   {
      $art0 = new articulo();
      $art0->referencia = $_POST['referencia'];
      $art0->descripcion = $_POST['referencia'];
      $art0->codfamilia = $_POST['codfamilia'];
      $art0->set_impuesto($_POST['codimpuesto']);
      
      if( $art0->save() )
         $this->results[] = $art0;
      
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_nuevo_albaran';
   }
   
   private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_nuevo_albaran';
      
      $codfamilia = '';
      if( isset($_POST['codfamilia']) )
         $codfamilia = $_POST['codfamilia'];
      
      $con_stock = isset($_POST['con_stock']);
      $this->results = $this->articulo->search($this->query, 0, $codfamilia, $con_stock);
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
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guadar
               y se han enviado dos peticiones. Mira en <a href="'.$albaran->url().'">albaranes</a>
               para ver si el albarán se ha guardado correctamente.');
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
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $this->articulo->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_albaran_cliente();
                     $linea->idalbaran = $albaran->idalbaran;
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
            
            if( $continuar )
            {
               /// redondeamos
               $albaran->neto = round($albaran->neto, 2);
               $albaran->totaliva = round($albaran->totaliva, 2);
               $albaran->total = $albaran->neto + $albaran->totaliva;
               
               if( $albaran->save() )
                  $this->new_message("<a href='".$albaran->url()."'>Albarán</a> guardado correctamente.");
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>albarán</a>!");
            }
            else if( $albaran->delete() )
               $this->new_message("Albarán eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$albaran->url()."'>albarán</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el albarán!");
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
         $this->new_error_msg('Petición duplicada. Has hecho doble clic sobre el botón guadar
               y se han enviado dos peticiones. Mira en <a href="'.$albaran->url().'">albaranes</a>
               para ver si el albarán se ha guardado correctamente.');
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
         $albaran->codagente = $this->agente->codagente;
         if( isset($_POST['numproveedor']) )
            $albaran->numproveedor = $_POST['numproveedor'];
         $albaran->observaciones = $_POST['observaciones'];
         
         if( $albaran->save() )
         {
            $n = floatval($_POST['numlineas']);
            for($i = 1; $i <= $n; $i++)
            {
               if( isset($_POST['referencia_'.$i]) )
               {
                  $articulo = $this->articulo->get($_POST['referencia_'.$i]);
                  if($articulo)
                  {
                     $linea = new linea_albaran_proveedor();
                     $linea->idalbaran = $albaran->idalbaran;
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
                     else if( floatval($_POST['iva_'.$i]) == $articulo->get_iva() )
                     {
                        $linea->codimpuesto = $articulo->codimpuesto;
                        $linea->iva = $articulo->iva;
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
                        /// sumamos al stock
                        $articulo->sum_stock($albaran->codalmacen, $linea->cantidad);
                        
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
                  $this->new_message("<a href='".$albaran->url()."'>Albarán</a> guardado correctamente.");
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>albarán</a>!");
            }
            else if( $albaran->delete() )
               $this->new_message("Albarán eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar el <a href='".$albaran->url()."'>albarán</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el albarán!");
      }
   }
   
   private function get_precios_articulo()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_nuevo_albaran_precios';
      
      $this->articulo = $this->articulo->get($_POST['referencia4precios']);
      if( $this->articulo )
      {
         $this->tarifas = $this->articulo->get_tarifas();
         $this->equivalentes = $this->articulo->get_equivalentes();
         $this->ultimas_compras = $this->articulo->get_lineas_albaran_prov(0, 10);
         $this->ultimas_ventas = $this->articulo->get_lineas_albaran_cli(0, 10);
      }
   }
}

?>