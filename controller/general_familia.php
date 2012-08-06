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

class general_familia extends fs_controller
{
   public $familia;
   public $articulos;
   public $offset;

   public function __construct()
   {
      parent::__construct('general_familia', 'Familia', 'general', FALSE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('general_familias');
      
      if( isset($_POST['cod']) )
      {
         $this->familia = new familia();
         $this->familia = $this->familia->get($_POST['cod']);
         $this->familia->descripcion = $_POST['descripcion'];
         if( $this->familia->save() )
            $this->new_message("Datos modificados correctamente");
      }
      else if( isset($_GET['cod']) )
      {
         $this->familia = new familia();
         $this->familia = $this->familia->get($_GET['cod']);
      }
      
      if($this->familia)
      {
         $this->page->title = $this->familia->codfamilia;
         $this->buttons[] = new fs_button('b_herramientas_familia', 'herramientas', '#', '', 'img/tools.png', '*');
         
         if( $this->page->get('general_cargar_familia') )
            $this->buttons[] = new fs_button('b_load_familia', 'cargar', '#');
         
         $this->buttons[] = new fs_button('b_download_familia', 'descargar', $this->url().'&download=TRUE', '', 'img/save.png', '*');
         $this->buttons[] = new fs_button('b_eliminar_familia', 'eliminar', '#', 'remove', 'img/remove.png', '-');
         
         if( isset($_POST['multiplicar']) )
         {
            $art = new articulo();
            $art->multiplicar_precios($this->familia->codfamilia, floatval($_POST['multiplicar']));
         }
         else if( isset($_GET['download']) )
            $this->download();
         else if( isset($_POST['archivo']) )
            $this->upload();
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         $this->articulos = $this->familia->get_articulos($this->offset);
      }
   }
   
   public function version() {
      return parent::version().'-1';
   }
   
   public function url()
   {
      if($this->familia)
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
      $this->template = FALSE;
      header( "content-type: text/plain; charset=UTF-8" );
      echo "REF;PVP;DESC;CODBAR;\n";
      $num = 0;
      $articulos = $this->familia->get_articulos($num);
      while(count($articulos) > 0)
      {
         foreach($articulos as $a)
            echo $a->referencia.';'.$a->pvp.';'.$this->change_dot($a->descripcion).';'.$a->codbarras.";\n";
         unset($articulos);
         $num += FS_ITEM_LIMIT;
         $articulos = $this->familia->get_articulos($num);
      }
   }
   
   private function change_dot($var)
   {
      return str_replace(';', '', $var);
   }
   
   private function upload()
   {
      if( is_uploaded_file($_FILES['farchivo']['tmp_name']) )
      {
         if( !file_exists("tmp/familias") )
            mkdir("tmp/familias");
         else if( file_exists("tmp/familias/".$this->familia->codfamilia.'.csv') )
            unlink("tmp/familias/".$this->familia->codfamilia.'.csv');
         
         copy($_FILES['farchivo']['tmp_name'], "tmp/familias/".$this->familia->codfamilia.'.csv');
         
         $page = $this->page->get("general_cargar_familia");
         if($page)
            header('location: '.$page->url().'&fam='.$this->familia->codfamilia.'&reboot=TRUE');
         else
            $this->new_error_msg("No tienes permiso para acceder a general_cargar_familia");
      }
      else
         $this->new_error_msg("¡Imposible cargar el archivo!");
   }
}

?>
