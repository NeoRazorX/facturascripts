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

require_model('albaran_cliente.php');

class general_albaranes_cli extends fs_controller
{
   public $buscar_lineas;
   public $lineas;
   public $resultados;
   public $offset;

   public function __construct()
   {
      parent::__construct(__CLASS__, FS_ALBARANES.' de cliente', 'general', FALSE, TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_POST['buscar_lineas']) )
         $this->buscar_lineas();
      else
      {
         $albaran = new albaran_cliente();
         $this->custom_search = TRUE;
         
         $npage = $this->page->get('nuevo_albaran_cliente');
         if($npage)
            $this->buttons[] = new fs_button_img('b_nuevo_albaran', 'Nuevo', 'add.png', $npage->url().'#nuevo');
         
         $agpage = $this->page->get('general_agrupar_albaranes_cli');
         if($agpage)
            $this->buttons[] = new fs_button('b_agrupar_albaranes', 'Agrupar', $agpage->url());
         
         $this->buttons[] = new fs_button_img('b_buscar_lineas', 'Lineas', 'zoom.png');
         
         if( !isset($_GET['ptefactura']) )
            $this->buttons[] = new fs_button('b_pendientes', 'Pendientes', $this->url()."&amp;ptefactura=TRUE");
         
         if( isset($_POST['delete']) )
         {
            $this->delete_albaran();
         }
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         if($this->query)
            $this->resultados = $albaran->search($this->query, $this->offset);
         else if( isset($_GET['ptefactura']) )
         {
            $this->new_advice('Estos son los '.FS_ALBARANES.' pendientes de facturar. Haz clic <a class="link" href="'.$this->url().
                 '">aquí</a> para volver a la vista normal.');
            $this->resultados = $albaran->all_ptefactura($this->offset);
         }
         else
            $this->resultados = $albaran->all($this->offset);
      }
   }
   
   public function anterior_url()
   {
      $url = '';
      $extra = '';
      
      if( isset($_GET['ptefactura']) )
         $extra = '&ptefactura=TRUE';
      
      if($this->query!='' AND $this->offset>'0')
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT).$extra;
      else if($this->query=='' AND $this->offset>'0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT).$extra;
      
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      $extra = '';
      
      if( isset($_GET['ptefactura']) )
         $extra = '&ptefactura=TRUE';
      
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&query=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT).$extra;
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT).$extra;
      
      return $url;
   }
   
   public function buscar_lineas()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax/general_lineas_albaranes_cli';
      
      $this->buscar_lineas = $_POST['buscar_lineas'];
      $linea = new linea_albaran_cliente();
      $this->lineas = $linea->search($this->buscar_lineas);
   }
   
   private function delete_albaran()
   {
      $alb1 = new albaran_cliente();
      $alb1 = $alb1->get($_POST['delete']);
      if($alb1)
      {
         /// ¿Actualizamos el stock de los artículos?
         if( isset($_POST['stock']) )
         {
            $articulo = new articulo();
            
            foreach($alb1->get_lineas() as $linea)
            {
               $art0 = $articulo->get($linea->referencia);
               if($art0)
               {
                  $art0->sum_stock($alb1->codalmacen, $linea->cantidad);
                  $art0->save();
               }
            }
         }
         
         if( $alb1->delete() )
            $this->new_message(FS_ALBARAN." ".$alb1->codigo." borrado correctamente.");
         else
            $this->new_error_msg("¡Imposible borrar el ".FS_ALBARAN."!");
      }
      else
         $this->new_error_msg("¡".FS_ALBARAN." no encontrado!");
   }
}
