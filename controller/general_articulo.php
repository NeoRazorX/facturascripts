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

require_once 'model/articulo.php';
require_once 'model/familia.php';
require_once 'model/impuesto.php';
require_once 'model/paquete.php';

class general_articulo extends fs_controller
{
   public $articulo;
   public $familia;
   public $impuesto;
   public $cache_paquete;

   public function __construct()
   {
      parent::__construct('general_articulo', 'Articulo', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_articulos');
      
      if( isset($_POST['referencia']) )
      {
         $this->page->title = $_POST['referencia'];
         $this->articulo = new articulo();
         $this->articulo = $this->articulo->get($_POST['referencia']);
         $this->articulo->set_descripcion($_POST['descripcion']);
         $this->articulo->codfamilia = $_POST['codfamilia'];
         $this->articulo->codbarras = $_POST['codbarras'];
         $this->articulo->equivalencia = $_POST['equivalencia'];
         $this->articulo->destacado = isset($_POST['destacado']);
         $this->articulo->bloqueado = isset($_POST['bloqueado']);
         $this->articulo->controlstock = isset($_POST['controlstock']);
         $this->articulo->secompra = isset($_POST['secompra']);
         $this->articulo->sevende = isset($_POST['sevende']);
         if( $_POST['pvp_iva'] != '' )
            $this->articulo->set_pvp_iva($_POST['pvp_iva']);
         else
            $this->articulo->set_pvp($_POST['pvp']);
         $this->articulo->codimpuesto = $_POST['codimpuesto'];
         $this->articulo->observaciones = $_POST['observaciones'];
         $this->articulo->stockmin = $_POST['stockmin'];
         $this->articulo->stockmax = $_POST['stockmax'];
         if( $this->articulo->save() )
            $this->new_message("Datos del articulo modificados correctamente");
         else
            $this->new_error_msg("¡Error al guardar el articulo!".$this->articulo->error_msg);
      }
      else if( isset($_GET['ref']) )
      {
         $this->page->title = $_GET['ref'];
         $this->articulo = new articulo();
         $this->articulo = $this->articulo->get($_GET['ref']);
      }
      
      if($this->articulo)
      {
         $this->familia = $this->articulo->get_familia();
         $this->impuesto = new impuesto();
         $this->cache_paquete = new cache_paquete();
      }
   }
   
   public function url()
   {
      if($this->articulo)
         return $this->articulo->url();
      else
         return $this->page->url();
   }
}

?>
