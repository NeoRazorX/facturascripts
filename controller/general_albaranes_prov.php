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

require_once 'model/albaran_proveedor.php';

class general_albaranes_prov extends fs_controller
{
   public $resultados;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('general_albaranes_prov', 'Albaranes de proveedor', 'general', FALSE, TRUE);
   }
   
   protected function process()
   {
      $albaran = new albaran_proveedor();
      $this->custom_search = TRUE;
      
      $npage = $this->page->get('general_nuevo_albaran');
      if($npage)
         $this->buttons[] = new fs_button('b_nuevo_albaran', 'nuevo albarán', $npage->url());
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      if( isset($_GET['delete']) )
      {
         $alb1 = new albaran_proveedor();
         $alb1 = $alb1->get($_GET['delete']);
         if($alb1)
         {
            if( $alb1->delete() )
               $this->new_message("Albarán ".$alb1->codigo." borrado correctamente.");
            else
               $this->new_error_msg("¡Imposible borrar el albarán ".$alb1->codigo."! ".$alb1->error_msg);
         }
         else
            $this->new_error_msg("¡Albarán no encontrado!");
      }
      
      if($this->query != '')
         $this->resultados = $albaran->search($this->query, $this->offset);
      else
         $this->resultados = $albaran->all($this->offset);
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
