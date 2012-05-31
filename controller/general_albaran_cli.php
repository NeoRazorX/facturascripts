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

require_once 'model/albaran_cliente.php';

class general_albaran_cli extends fs_controller
{
   public $albaran;
   public $agente;
   
   public function __construct()
   {
      parent::__construct('general_albaran_cli', 'Albaran de cliente', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_albaranes_cli');
      
      if(isset($_GET['id']))
      {
         $this->albaran = new albaran_cliente();
         $this->albaran = $this->albaran->get($_GET['id']);
         if($this->albaran)
         {
            $this->page->title = $this->albaran->codigo;
            $this->agente = $this->albaran->get_agente();
            if( !$this->albaran->ptefactura )
               $this->buttons[] = new fs_button('b_ver_factura', 'factura', $this->albaran->factura_url(), 'button', 'img/zoom.png');
         }
      }
   }
   
   public function url()
   {
      if($this->albaran)
         return $this->albaran->url();
      else
         return $this->page->url();
   }
}

?>
