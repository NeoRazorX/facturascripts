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
      $this->results = array();
      
      if( isset($_GET['new_articulo']) )
         $this->new_articulo();
      else if( $this->query != '' )
         $this->new_search();
      else
      {
         $this->agente = $this->user->get_agente();
         $this->almacen = new almacen();
         $this->cliente = new cliente();
         $this->divisa = new divisa();
         $this->ejercicio = new ejercicio();
         $this->forma_pago = new forma_pago();
         $this->proveedor = new proveedor();
         $this->serie = new serie();
         
         if( isset($_POST['tipoalbaran']) )
         {
            if($_POST['tipoalbaran'] == 'cliente')
               $this->nuevo_albaran_cliente();
            else if($_POST['tipoalbaran'] == 'proveedor')
               $this->nuevo_albaran_proveedor();
         }
      }
   }
   
   public function version()
   {
      return parent::version().'-6';
   }
   
   private function new_articulo()
   {
      $this->impuesto = new impuesto();
      
      $art0 = new articulo();
      $art0->referencia = $_POST['referencia'];
      $art0->descripcion = $_POST['referencia'];
      $art0->codfamilia = $_POST['codfamilia'];
      $art0->codimpuesto = $_POST['codimpuesto'];
      if( $art0->save() )
         $this->results[] = $art0;
      
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_nuevo_albaran';
   }
   
   private function new_search()
   {
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_nuevo_albaran';
      
      if( isset($_POST['codfamilia']) )
         $codfamilia = $_POST['codfamilia'];
      else
         $codfamilia = '';
      $con_stock = isset($_POST['con_stock']);
      $this->results = $this->articulo->search($this->query, 0, $codfamilia, $con_stock);
   }
   
   private function nuevo_albaran_cliente()
   {
      $continuar = TRUE;
      
      $cliente = $this->cliente->get($_POST['cliente']);
      if( $cliente )
      {
         $cliente->set_default();
         $dirscliente = $cliente->get_direcciones();
      }
      else
         $continuar = FALSE;
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $almacen->set_default();
      else
         $continuar = FALSE;
      
      $ejercicio = $this->ejercicio->get($_POST['ejercicio']);
      if( $ejercicio )
         $ejercicio->set_default();
      else
         $continuar = FALSE;
      
      $serie = $this->serie->get($_POST['serie']);
      if( $serie )
         $serie->set_default();
      else
         $continuar = FALSE;
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $forma_pago->set_default();
      else
         $continuar = FALSE;
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $divisa->set_default();
      else
         $continuar = FALSE;
      
      if( $continuar )
      {
         $albaran = new albaran_cliente();
         $albaran->codcliente = $cliente->codcliente;
         $albaran->cifnif = $cliente->cifnif;
         $albaran->nombrecliente = $cliente->nombre;
         if($dirscliente)
         {
            foreach($dirscliente as $d)
            {
               if($d->domfacturacion)
               {
                  $albaran->apartado = $d->apartado;
                  $albaran->ciudad = $d->ciudad;
                  $albaran->coddir = $d->id;
                  $albaran->codpais = $d->codpais;
                  $albaran->codpostal = $d->codpostal;
                  $albaran->direccion = $d->direccion;
                  $albaran->provincia = $d->provincia;
               }
            }
         }
         
         if( is_null($albaran->coddir) )
            $this->new_error_msg("No hay ninguna dirección asociada al cliente.");
         else
         {
            $albaran->codalmacen = $almacen->codalmacen;
            $albaran->codejercicio = $ejercicio->codejercicio;
            $albaran->codserie = $serie->codserie;
            $albaran->codpago = $forma_pago->codpago;
            $albaran->coddivisa = $divisa->coddivisa;
            $albaran->codagente = $this->agente->codagente;
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
                        $linea = new linea_albaran_cliente();
                        $linea->idalbaran = $albaran->idalbaran;
                        $linea->referencia = $articulo->referencia;
                        $linea->descripcion = $articulo->descripcion;
                        
                        if( $serie->siniva )
                        {
                           $linea->codimpuesto = NULL;
                           $linea->iva = 0;
                        }
                        else
                        {
                           $linea->codimpuesto = $articulo->codimpuesto;
                           $linea->iva = floatval($_POST['iva_'.$i]);
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
                           $albaran->totaliva += ($linea->iva * $linea->pvptotal / 100);
                           $albaran->total = ($albaran->neto + $albaran->totaliva);
                           $albaran->totaleuros = ($albaran->neto + $albaran->totaliva);
                        }
                        else
                           $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                     }
                  }
               }
               if( $albaran->save() )
                  $this->new_message("<a href='".$albaran->url()."'>Albarán</a> guardado correctamente.");
               else
                  $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>albaran</a>!");
            }
            else
               $this->new_error_msg("¡Imposible guardar el albaran!");
         }
      }
      else
         $this->new_error_msg("¡Faltan datos!");
   }
   
   private function nuevo_albaran_proveedor()
   {
      $continuar = TRUE;
      
      $proveedor = $this->proveedor->get($_POST['proveedor']);
      if( $proveedor )
         $proveedor->set_default();
      else
         $continuar = FALSE;
      
      $almacen = $this->almacen->get($_POST['almacen']);
      if( $almacen )
         $almacen->set_default();
      else
         $continuar = FALSE;
      
      $ejercicio = $this->ejercicio->get($_POST['ejercicio']);
      if( $ejercicio )
         $ejercicio->set_default();
      else
         $continuar = FALSE;
      
      $serie = $this->serie->get($_POST['serie']);
      if( $serie )
         $serie->set_default();
      else
         $continuar = FALSE;
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( $forma_pago )
         $forma_pago->set_default();
      else
         $continuar = FALSE;
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( $divisa )
         $divisa->set_default();
      else
         $continuar = FALSE;
      
      if( $continuar )
      {
         $albaran = new albaran_proveedor();
         $albaran->codproveedor = $proveedor->codproveedor;
         $albaran->nombre = $proveedor->nombre;
         $albaran->cifnif = $proveedor->cifnif;
         $albaran->codalmacen = $almacen->codalmacen;
         $albaran->codejercicio = $ejercicio->codejercicio;
         $albaran->codserie = $serie->codserie;
         $albaran->codpago = $forma_pago->codpago;
         $albaran->coddivisa = $divisa->coddivisa;
         $albaran->codagente = $this->agente->codagente;
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
                     $linea->descripcion = $articulo->descripcion;
                     
                     if( $serie->siniva )
                     {
                        $linea->codimpuesto = NULL;
                        $linea->iva = 0;
                     }
                     else
                     {
                        $linea->codimpuesto = $articulo->codimpuesto;
                        $linea->iva = floatval($_POST['iva_'.$i]);
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
                        $albaran->totaliva += ($linea->iva * $linea->pvptotal / 100);
                        $albaran->total = ($albaran->neto + $albaran->totaliva);
                        $albaran->totaleuros = ($albaran->neto + $albaran->totaliva);
                     }
                     else
                        $this->new_error_msg("¡Imposible guardar la linea con referencia: ".$linea->referencia);
                  }
               }
            }
            if( $albaran->save() )
               $this->new_message("<a href='".$albaran->url()."'>Albarán</a> guardado correctamente.");
            else
               $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>albarán</a>!");
         }
         else
            $this->new_error_msg("¡Imposible guardar el albarán!");
      }
      else
         $this->new_error_msg("¡Faltan datos!");
   }
}

?>