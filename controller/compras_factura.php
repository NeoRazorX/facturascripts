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

require_once 'base/fs_pdf.php';
require_model('asiento.php');
require_model('asiento_factura.php');
require_model('ejercicio.php');
require_model('factura_proveedor.php');
require_model('partida.php');
require_model('proveedor.php');
require_model('subcuenta.php');

class compras_factura extends fs_controller
{
   public $agente;
   public $ejercicio;
   public $factura;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Factura de proveedor', 'compras', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('compras_facturas');
      $this->agente = FALSE;
      $this->ejercicio = new ejercicio();
      $factura = new factura_proveedor();
      $this->factura = FALSE;
      
      /// desactivamos la barra de botones
      $this->show_fs_toolbar = FALSE;
      
      if( isset($_POST['idfactura']) )
      {
         $this->factura = $factura->get($_POST['idfactura']);
         $this->factura->numproveedor = $_POST['numproveedor'];
         $this->factura->observaciones = $_POST['observaciones'];
         
         /// obtenemos el ejercicio para poder acotar la fecha
         $eje0 = $this->ejercicio->get( $this->factura->codejercicio );
         if( $eje0 )
         {
            $this->factura->fecha = $eje0->get_best_fecha($_POST['fecha'], TRUE);
         }
         else
            $this->new_error_msg('No se encuentra el ejercicio asociado a la factura.');
         
         if( $this->factura->save() )
         {
            $asiento = $this->factura->get_asiento();
            if($asiento)
            {
               $asiento->fecha = $_POST['fecha'];
               if( !$asiento->save() )
                  $this->new_error_msg("Imposible modificar la fecha del asiento.");
            }
            $this->new_message("Factura modificada correctamente.");
            $this->new_change('Factura Proveedor '.$this->factura->codigo, $this->factura->url());
         }
         else
            $this->new_error_msg("¡Imposible modificar la factura!");
      }
      else if( isset($_GET['id']) )
      {
         $this->factura = $factura->get($_GET['id']);
      }
      
      if($this->factura)
      {
         $this->page->title = $this->factura->codigo;
         
         if( isset($_GET['gen_asiento']) AND isset($_GET['petid']) )
         {
            if( $this->duplicated_petition($_GET['petid']) )
            {
               $this->new_error_msg('Petición duplicada. Evita hacer doble clic sobre los botones.');
            }
            else
               $this->generar_asiento();
         }
         else if( isset($_REQUEST['pagada']) )
         {
            $this->factura->pagada = ($_REQUEST['pagada'] == 'TRUE');
            if( $this->factura->save() )
            {
               $this->new_message("Factura modificada correctamente.");
            }
            else
               $this->new_error_msg("¡Imposible modificar la factura!");
         }
         
         /// comprobamos la factura
         $this->factura->full_test();
         
         /// cargamos el agente
         if( !is_null($this->factura->codagente) )
         {
            $agente = new agente();
            $this->agente = $agente->get($this->factura->codagente);
         }
      }
      else
         $this->new_error_msg("¡Factura de proveedor no encontrada!");
   }
   
   public function url()
   {
      if( !isset($this->factura) )
      {
         return parent::url();
      }
      else if($this->factura)
      {
         return $this->factura->url();
      }
      else
         return $this->page->url();
   }
   
   private function generar_asiento()
   {
      if( $this->factura->get_asiento() )
      {
         $this->new_error_msg('Ya hay un asiento asociado a esta factura.');
      }
      else
      {
         $asiento_factura = new asiento_factura();
         if( $asiento_factura->generar_asiento_compra($this->factura) )
         {
            $this->new_message("<a href='".$asiento_factura->asiento->url()."'>Asiento</a> generado correctamente.");
            $this->new_change('Factura Proveedor '.$this->factura->codigo, $this->factura->url());
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
   }
}
