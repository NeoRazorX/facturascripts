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

class contabilidad_asientos extends fs_controller
{
   public $asiento;
   public $resultados;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('contabilidad_asientos', 'Asientos', 'contabilidad', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->asiento = new asiento();
      $this->custom_search = TRUE;
      
      $naurl = $this->page->get('contabilidad_nuevo_asiento');
      if($naurl)
         $this->buttons[] = new fs_button ('b_nuevo_asiento', 'Nuevo', $naurl->url());
      
      $this->buttons[] = new fs_button('b_renumerar', 'renumerar', $this->url().'&renumerar=TRUE', '', 'img/tools.png');
      
      if( isset($_GET['delete']) )
      {
         $asiento = $this->asiento->get($_GET['delete']);
         if($asiento)
         {
            if( $asiento->delete() )
               $this->new_message("Asiento eliminado correctamente.");
            else
               $this->new_error_msg("¡Imposible eliminar el asiento!");
         }
         else
            $this->new_error_msg("¡Asiento no encontrado!");
      }
      else if( isset($_GET['renumerar']) )
      {
         if( $this->asiento->renumerar() )
            $this->new_message("Asientos renumerados.");
      }
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      if($this->query)
         $this->resultados = $this->asiento->search($this->query, $this->offset);
      else
         $this->resultados = $this->asiento->all($this->offset);
   }
   
   public function version() {
      return parent::version().'-3';
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
