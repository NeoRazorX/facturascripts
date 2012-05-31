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

require_once 'base/fs_cache.php';
require_once 'model/articulo.php';
require_once 'model/cliente.php';
require_once 'model/proveedor.php';
require_once 'model/ejercicio.php';
require_once 'model/serie.php';
require_once 'model/forma_pago.php';
require_once 'model/divisa.php';
require_once 'model/albaran_cliente.php';
require_once 'model/albaran_proveedor.php';

class general_nuevo_albaran extends fs_controller
{
   public $agente;
   public $articulo;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $forma_pago;
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
      $this->results = array();
      
      if( $this->query != '' )
         $this->new_search();
      else
      {
         $this->buttons[] = new fs_button('b_new_line', 'añadir');
         
         $this->agente = $this->user->get_agente();
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
   
   private function new_search()
   {
      $cache = new fs_cache();
      $this->results = $cache->get_array('search_'.$this->query);
      if( count($this->results) < 1 )
      {
         $this->results = $this->articulo->search($this->query);
         $cache->set('search_'.$this->query, $this->results);
      }
   }
   
   private function nuevo_albaran_cliente()
   {
      $cliente = $this->cliente->get($_POST['cliente']);
      if( !$cliente->is_default() )
         $cliente->set_default();
      
      $ejercicio = $this->ejercicio->get($_POST['ejercicio']);
      if( !$ejercicio->is_default() )
         $ejercicio->set_default();
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie->is_default() )
         $serie->set_default();
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( !$forma_pago->is_default() )
         $forma_pago->set_default();
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( !$divisa->is_default() )
         $divisa->set_default();
      
      $albaran = new albaran_cliente();
      $albaran->codcliente = $cliente->codcliente;
      $albaran->cifnif = $cliente->cifnif;
      $albaran->nombrecliente = $cliente->nombre;
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
                  $linea->codimpuesto = $articulo->codimpuesto;
                  $linea->iva = floatval($_POST['iva_'.$i]);
                  $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                  $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                  $linea->dtopor = floatval($_POST['dto_'.$i]);
                  $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                  $linea->pvptotal = floatval($_POST['total_'.$i]);
                  if( $linea->save() )
                  {
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
            $this->new_message("<a href='".$albaran->url()."'>Albaran</a> guardado correctamente");
         else
            $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>albaran</a>!");
      }
      else
         $this->new_error_msg("¡Imposible guardar el albaran!");
   }
   
   private function nuevo_albaran_proveedor()
   {
      $proveedor = $this->proveedor->get($_POST['proveedor']);
      if( !$proveedor->is_default() )
         $proveedor->set_default();
      
      $ejercicio = $this->ejercicio->get($_POST['ejercicio']);
      if( !$ejercicio->is_default() )
         $ejercicio->set_default();
      
      $serie = $this->serie->get($_POST['serie']);
      if( !$serie->is_default() )
         $serie->set_default();
      
      $forma_pago = $this->forma_pago->get($_POST['forma_pago']);
      if( !$forma_pago->is_default() )
         $forma_pago->set_default();
      
      $divisa = $this->divisa->get($_POST['divisa']);
      if( !$divisa->is_default() )
         $divisa->set_default();
      
      $albaran = new albaran_proveedor();
      $albaran->codproveedor = $proveedor->codproveedor;
      $albaran->nombre = $proveedor->nombre;
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
                  $linea->codimpuesto = $articulo->codimpuesto;
                  $linea->iva = floatval($_POST['iva_'.$i]);
                  $linea->pvpunitario = floatval($_POST['pvp_'.$i]);
                  $linea->cantidad = floatval($_POST['cantidad_'.$i]);
                  $linea->dtopor = floatval($_POST['dto_'.$i]);
                  $linea->pvpsindto = ($linea->pvpunitario * $linea->cantidad);
                  $linea->pvptotal = floatval($_POST['total_'.$i]);
                  if( $linea->save() )
                  {
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
            $this->new_message("<a href='".$albaran->url()."'>Albaran</a> guardado correctamente");
         else
            $this->new_error_msg("¡Imposible actualizar el <a href='".$albaran->url()."'>albaran</a>!");
      }
      else
         $this->new_error_msg("¡Imposible guardar el albaran!");
   }
}

?>