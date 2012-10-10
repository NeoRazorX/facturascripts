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

require_once 'model/asiento.php';

class contabilidad_asiento extends fs_controller
{
   public $asiento;

   public function __construct() {
      parent::__construct('contabilidad_asiento', 'Asiento', 'contabilidad', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('contabilidad_asientos');
      
      if( isset($_GET['id']) )
      {
         $this->asiento = new asiento();
         $this->asiento = $this->asiento->get($_GET['id']);
      }
      
      if( $this->asiento )
      {
         $this->page->title = 'Asiento: '.$this->asiento->numero;
         
         $url = $this->asiento->factura_url();
         if($url != '')
            $this->buttons[] = new fs_button('b_ver_factura', $this->asiento->tipodocumento, $url, 'button', 'img/zoom.png');
         $this->buttons[] = new fs_button('b_eliminar_asiento', 'eliminar', '#', 'remove', 'img/remove.png');
         
         $this->asiento->full_test();
      }
      else
         $this->new_error_msg("Asiento no encontrado.");
   }
   
   public function version() {
      return parent::version().'-3';
   }
   
   public function url()
   {
      if( $this->asiento )
         return $this->asiento->url();
      else
         return $this->ppage->url();
   }
}

?>
