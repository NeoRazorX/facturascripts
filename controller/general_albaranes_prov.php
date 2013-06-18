<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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
   public $buscar_lineas;
   public $lineas;
   public $resultados;
   public $offset;
   
   public function __construct()
   {
      parent::__construct('general_albaranes_prov', 'Albaranes de proveedor', 'general', FALSE, TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_POST['buscar_lineas']) )
         $this->buscar_lineas();
      else
      {
         $albaran = new albaran_proveedor();
         $this->custom_search = TRUE;
         
         $npage = $this->page->get('general_nuevo_albaran');
         if($npage)
            $this->buttons[] = new fs_button('b_nuevo_albaran', 'nuevo', $npage->url());
         
         $agpage = $this->page->get('general_agrupar_albaranes_pro');
         if($agpage)
            $this->buttons[] = new fs_button('b_agrupar_albaranes', 'agrupar',
                    $agpage->url(), '', 'img/tools.png');
         
         $this->buttons[] = new fs_button('b_buscar_lineas', 'lineas', '#', '', 'img/zoom.png');
         
         if( isset($_GET['delete']) )
         {
            $alb1 = new albaran_proveedor();
            $alb1 = $alb1->get($_GET['delete']);
            if($alb1)
            {
               if( $alb1->delete() )
                  $this->new_message("Albarán ".$alb1->codigo." borrado correctamente.");
               else
                  $this->new_error_msg("¡Imposible borrar el albarán!");
            }
            else
               $this->new_error_msg("¡Albarán no encontrado!");
         }
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         if($this->query != '')
            $this->resultados = $albaran->search($this->query, $this->offset);
         else
            $this->resultados = $albaran->all($this->offset);
      }
   }
   
   public function version()
   {
      return parent::version().'-6';
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
   
   public function buscar_lineas()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_lineas_albaranes_prov';
      
      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_albaran_proveedor();
      $this->lineas = $linea->search($this->buscar_lineas);
   }
}

?>
