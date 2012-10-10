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

require_once 'model/subcuenta.php';

class contabilidad_subcuenta extends fs_controller
{
   public $subcuenta;
   public $cuenta;
   public $ejercicio;
   public $resultados;
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
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         $this->resultados = $this->subcuenta->get_partidas($this->offset);
      }
      else
         $this->new_error_msg("Subcuenta no encontrada.");
   }
   
   public function version() {
      return parent::version().'-2';
   }
   
   public function url()
   {
      if( $this->subcuenta )
         return $this->subcuenta->url();
      else
         return $this->ppage->url();
   }

   public function anterior_url()
   {
      $url = '';
      if($this->query!='' AND $this->offset>'0')
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT);
      else if($this->query=='' AND $this->offset>'0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT);
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      return $url;
   }
}

?>
