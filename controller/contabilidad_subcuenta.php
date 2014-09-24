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

require_model('divisa.php');
require_model('partida.php');
require_model('subcuenta.php');
require_once 'extras/libromayor.php';

class contabilidad_subcuenta extends fs_controller
{
   public $cuenta;
   public $divisa;
   public $ejercicio;
   public $resultados;
   public $subcuenta;
   public $offset;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Subcuenta', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->divisa = new divisa();
      
      $subcuenta = new subcuenta();
      $this->subcuenta = FALSE;
      
      if( isset($_GET['id']) )
      {
         $this->subcuenta = $subcuenta->get($_GET['id']);
      }
      
      if($this->subcuenta)
      {
         /// configuramos la página previa
         $this->ppage = $this->page->get('contabilidad_cuenta');
         $this->ppage->title = 'Cuenta: '.$this->subcuenta->codcuenta;
         $this->ppage->extra_url = '&id='.$this->subcuenta->idcuenta;
         
         $this->page->title = 'Subcuenta: '.$this->subcuenta->codsubcuenta;
         $this->cuenta = $this->subcuenta->get_cuenta();
         $this->ejercicio = $this->subcuenta->get_ejercicio();
         
         $this->offset = 0;
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         
         $this->resultados = $this->subcuenta->get_partidas($this->offset);
         
         if( isset($_POST['puntear']) )
            $this->puntear();
         
         if( isset($_GET['genlm']) )
         {
            /// generamos el PDF del libro mayor si no existe
            $libro_mayor = new libro_mayor();
            $libro_mayor->libro_mayor($this->subcuenta);
         }
         
         if( file_exists('tmp/'.FS_TMP_NAME.'libro_mayor/'.$this->subcuenta->idsubcuenta.'.pdf') )
         {
            $this->buttons[] = new fs_button_img('b_libro_mayor', 'Libro mayor', 'print.png',
               'tmp/'.FS_TMP_NAME.'libro_mayor/'.$this->subcuenta->idsubcuenta.'.pdf', FALSE, TRUE);
         }
         else
         {
            $this->buttons[] = new fs_button('b_libro_mayor', 'Generar libro mayor', $this->url().'&genlm=TRUE');
         }
         
         $this->buttons[] = new fs_button_img('b_eliminar', 'Eliminar', 'trash.png', '#', TRUE);
         
         /// comprobamos la subcuenta
         $this->subcuenta->test();
      }
      else
      {
         $this->new_error_msg("Subcuenta no encontrada.");
         $this->ppage = $this->page->get('contabilidad_cuentas');
      }
   }
   
   public function url()
   {
      if( !isset($this->subcuenta) )
      {
         return parent::url();
      }
      else if($this->subcuenta)
      {
         return $this->subcuenta->url();
      }
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
      if($_POST['descripcion'] != $this->subcuenta->descripcion)
      {
         $this->subcuenta->descripcion = $_POST['descripcion'];
         $this->subcuenta->coddivisa = $_POST['coddivisa'];
         $this->subcuenta->save();
      }
      
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
