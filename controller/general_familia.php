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
         $this->buttons[] = new fs_button('b_eliminar_familia', 'eliminar', '#', 'remove', 'img/remove.png', '-');
         
         if( isset($_POST['multiplicar']) )
         {
            $art = new articulo();
            $art->multiplicar_precios($this->familia->codfamilia, floatval($_POST['multiplicar']));
         }
         
         if( isset($_GET['offset']) )
            $this->offset = intval($_GET['offset']);
         else
            $this->offset = 0;
         $this->articulos = $this->familia->get_articulos($this->offset);
      }
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
}

?>
