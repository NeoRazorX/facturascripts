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
require_once 'model/asiento.php';
require_once 'model/concepto_partida.php';
require_once 'model/divisa.php';
require_once 'model/ejercicio.php';
require_once 'model/impuesto.php';
require_once 'model/partida.php';
require_once 'model/subcuenta.php';

class contabilidad_nuevo_asiento extends fs_controller
{
   public $asiento;
   public $concepto;
   public $divisa;
   public $impuesto;
   public $ejercicio;
   public $resultados;

   public function __construct()
   {
      parent::__construct('contabilidad_nuevo_asiento', 'nuevo asiento', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->asiento = new asiento();
      $this->concepto = new concepto_partida();
      $this->divisa = new divisa();
      $this->impuesto = new impuesto();
      $this->ejercicio = new ejercicio();
      $this->ppage = $this->page->get('contabilidad_asientos');
      
      if(isset($_POST['ejercicio']) AND isset($_POST['tipo']) AND isset($_POST['query']))
         $this->new_search();
      else if( isset($_POST['codejercicio']) AND isset($_POST['fecha']) )
      {
         $this->set_default_elements();
         
         $this->asiento->codejercicio = $_POST['codejercicio'];
         $this->asiento->idconcepto = $_POST['idconceptopar'];
         $this->asiento->concepto = $_POST['concepto'];
         $this->asiento->documento = $_POST['documento'];
         $this->asiento->fecha = $_POST['fecha'];
         $this->asiento->tipodocumento = $_POST['tipodocumento'];
         $this->asiento->importe = $_POST['importe'];
         if( $this->asiento->save() )
         {
            $partidas_correctas = TRUE;
            $numlineas = intval($_POST['numlineas']);
            for($i=1; $i<=$numlineas; $i++)
            {
               if($_POST['idsubcuenta_'.$i] != '' AND $partidas_correctas)
               {
                  $partida = new partida();
                  $partida->idasiento = $this->asiento->idasiento;
                  $partida->coddivisa = $_POST['divisa'];
                  $partida->idsubcuenta = $_POST['idsubcuenta_'.$i];
                  $partida->codsubcuenta = $_POST['codsubcuenta_'.$i];
                  $partida->debe = $_POST['debe_'.$i];
                  $partida->haber = $_POST['haber_'.$i];
                  $partida->idconcepto = $this->asiento->idconcepto;
                  $partida->concepto = $this->asiento->concepto;
                  $partida->documento = $this->asiento->documento;
                  $partida->tipodocumento = $this->asiento->tipodocumento;
                  if($_POST['idcontrapartida_'.$i] != '')
                  {
                     $partida->idcontrapartida = $_POST['idcontrapartida_'.$i];
                     $partida->codcontrapartida = $_POST['codcontrapartida_'.$i];
                     $partida->cifnif = $_POST['cifnif_'.$i];
                     $partida->iva = $_POST['iva_'.$i];
                     $partida->baseimponible = $_POST['baseimp_'.$i];
                  }
                  if( !$partida->save() )
                     $partidas_correctas = FALSE;
               }
            }
            if( $partidas_correctas )
               $this->new_message("<a href='".$this->asiento->url()."'>Asiento</a> guardado correctamente!");
            else
            {
               if( $this->asiento->delete() )
                  $this->new_error_msg("¡Error en alguna de las partidas! Se ha borrado el asiento.");
               else
                  $this->new_error_msg("¡Error en alguna de las partidas! Además ha sido imposible borrar el asiento.");
            }
         }
         else
            $this->new_error_msg("¡Imposible guardar el asiento!");
      }
   }
   
   public function version()
   {
      return parent::version().'-5';
   }
   
   private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/contabilidad_nuevo_asiento';
      
      $cache = new fs_cache();
      $this->resultados = $cache->get_array('search_subcuenta_ejercicio_'.$_POST['ejercicio'].'_'.$this->query);
      if( count($this->resultados) < 1 )
      {
         $subc = new subcuenta();
         $this->resultados = $subc->search_by_ejercicio($_POST['ejercicio'], $this->query);
         $cache->set('search_subcuenta_ejercicio_'.$_POST['ejercicio'].'_'.$this->query, $this->resultados);
      }
   }
   
   private function set_default_elements()
   {
      if( isset($_POST['divisa']) )
      {
         $divisa = $this->divisa->get($_POST['divisa']);
         if( $divisa )
            $divisa->set_default();
      }
      
      if( isset($_POST['codejercicio']) )
      {
         $ejercicio = $this->ejercicio->get($_POST['codejercicio']);
         if( $ejercicio )
            $ejercicio->set_default();
      }
   }
}

?>
