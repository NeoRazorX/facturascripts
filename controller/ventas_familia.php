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

require_model('articulo.php');
require_model('familia.php');
require_model('impuesto.php');

class ventas_familia extends fs_controller
{
   public $articulos;
   public $familia;
   public $impuesto;
   public $offset;
   public $pag_importar;

   public function __construct()
   {
      parent::__construct(__CLASS__, 'Familia', 'ventas', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('ventas_familias');
      
      /// comprobamos si el usuario tiene acceso a la página de importar familia
      $this->pag_importar = FALSE;
      if( $this->user->have_access_to('importar_familia', FALSE) )
         $this->pag_importar = $this->page->get('importar_familia');
      
      if( isset($_POST['cod']) )
      {
         $this->familia = new familia();
         $this->familia = $this->familia->get($_POST['cod']);
         $this->familia->descripcion = $_POST['descripcion'];
         if( $this->familia->save() )
            $this->new_message("Datos modificados correctamente");
         else
            $this->new_error_msg("Imposible modificar los datos.");
      }
      else if( isset($_GET['cod']) )
      {
         $this->familia = new familia();
         $this->familia = $this->familia->get($_GET['cod']);
      }
      
      if( $this->familia AND isset($_POST['stats']) )
      {
         $this->template = 'ajax/ventas_familia_stats';
         $this->familia->stats();
      }
      else if($this->familia)
      {
         $this->page->title = $this->familia->codfamilia;
         $this->impuesto = new impuesto();
         
         $this->buttons[] = new fs_button('b_stats_familia', 'Estadísticas');
         $this->buttons[] = new fs_button('b_herramientas_familia', 'Herramientas');
         
         if( $this->pag_importar )
            $this->buttons[] = new fs_button('b_importar_familia', 'Importar');
         
         $this->buttons[] = new fs_button('b_download_familia', 'Exportar', $this->url().'&download=TRUE');
         $this->buttons[] = new fs_button_img('b_eliminar_familia', 'Eliminar', 'trash.png', '#', TRUE);
         
         if( isset($_POST['multiplicar']) )
         {
            $art = new articulo();
            $art->multiplicar_precios($this->familia->codfamilia, $_POST['multiplicar']);
         }
         else if( isset($_GET['download']) )
            $this->download();
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         
         $this->articulos = $this->familia->get_articulos($this->offset);
      }
      else
         $this->new_error_msg("Familia no encontrada.");
   }
   
   public function url()
   {
      if( !isset($this->familia) )
         return parent::url();
      else if($this->familia)
         return $this->familia->url();
      else
         return $this->page->url();
   }

   public function anterior_url()
   {
      $url = '';
      if($this->offset > '0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      if(count($this->articulos)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      return $url;
   }
   
   private function download()
   {
      /// desactivamos el motor de plantillas
      $this->template = FALSE;
      
      header( "content-type: text/plain; charset=UTF-8" );
      header('Content-Disposition: attachment; filename="familia_'.$this->familia->codfamilia.'.csv"');
      
      echo "REF;PVP;DESC;CODBAR;\n";
      $num = 0;
      $articulos = $this->familia->get_articulos($num);
      while(count($articulos) > 0)
      {
         foreach($articulos as $a)
            echo $a->referencia.';'.$a->pvp.';'.str_replace(';', '', $a->descripcion).';'.$a->codbarras.";\n";
         unset($articulos);
         $num += FS_ITEM_LIMIT;
         $articulos = $this->familia->get_articulos($num);
      }
   }
}
