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

require_once 'model/partida.php';
require_once 'model/subcuenta.php';

class contabilidad_subcuenta extends fs_controller
{
   public $cuenta;
   public $ejercicio;
   public $resultados;
   public $subcuenta;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('contabilidad_subcuenta', 'Subcuenta', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('contabilidad_cuentas');
      
      if( isset($_GET['id']) )
      {
         $this->subcuenta = new subcuenta();
         $this->subcuenta = $this->subcuenta->get($_GET['id']);
      }
      
      if($this->subcuenta)
      {
         $this->page->title = 'Subcuenta: '.$this->subcuenta->codsubcuenta;
         $this->cuenta = $this->subcuenta->get_cuenta();
         $this->ejercicio = $this->subcuenta->get_ejercicio();
         
         /// comprobamos la subcuenta
         $this->subcuenta->test();
         
         if( file_exists('tmp/libro_mayor/'.$this->subcuenta->idsubcuenta.'.pdf') )
         {
            $this->buttons[] = new fs_button('b_libro_mayor', 'libro mayor',
               'tmp/libro_mayor/'.$this->subcuenta->idsubcuenta.'.pdf','',
               'img/print.png', 'imprimir', TRUE);
         }
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         $this->resultados = $this->subcuenta->get_partidas($this->offset);
         
         if( isset($_POST['puntear']) )
            $this->puntear();
      }
      else
         $this->new_error_msg("Subcuenta no encontrada.");
   }
   
   public function version()
   {
      return parent::version().'-8';
   }
   
   public function url()
   {
      if( $this->subcuenta )
         return $this->subcuenta->url();
      else
         return $this->ppage->url();
   }
   
   public function paginas()
   {
      $paginas = array();
      $i = 1;
      $num = 0;
      $actual = 1;
      $total = $this->subcuenta->count_partidas();
      /// añadimos todas la página
      while($num < $total)
      {
         $paginas[$i] = array(
             'url' => $this->url().'&offset='.$num,
             'num' => $i,
             'actual' => ($num == $this->offset)
         );
         if( $num == $this->offset )
            $actual = $i;
         $i++;
         $num += FS_ITEM_LIMIT;
      }
      /// ahora descartamos
      foreach($paginas as $j => $value)
      {
         if( ($j>1 AND $j<$actual-3 AND $j%10) OR ($j>$actual+3 AND $j<$i-1 AND $j%10) )
            unset($paginas[$j]);
      }
      return $paginas;
   }
   
   private function puntear()
   {
      $partida = new partida();
      
      foreach($this->resultados as $pa)
      {
         if( isset($_POST['punteada']) )
            $valor = in_array($pa->idpartida, $_POST['punteada']);
         else
            $valor = FALSE;
         
         if($pa->punteada != $valor)
         {
            $pa->punteada = $valor;
            $pa->save();
         }
      }
      
      $this->new_message('Datos guardados correctamente.');
   }
}

?>