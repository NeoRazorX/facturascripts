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

require_once 'base/fs_cache.php';
require_once 'model/articulo.php';
require_once 'model/familia.php';

class new_fs_controller extends fs_controller
{
   public $resultados;
   public $query;
   public $offset;
   private $cache;

   public function __construct()
   {
      parent::__construct('general_articulos', 'Artículos', 'general', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_divtitle = TRUE;
      
      $this->cache = new fs_cache();
      $articulos = new articulo();
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      if( isset($_GET['text']) )
      {
         $this->resultados = $articulos->search($_GET['text'], '', $this->offset);
         $this->query = $_GET['text'];
         $this->query2history();
      }
      else
      {
         $this->resultados = $articulos->all($this->offset);
         $this->query = '';
      }
   }
   
   public function anterior_url()
   {
      $url = '';
      if($this->query!='' AND $this->offset>'0')
         $url = $this->url()."&text=".$this->query."&offset=".($this->offset-FS_ITEM_LIMIT);
      else if($this->query=='' AND $this->offset>'0')
         $url = $this->url()."&offset=".($this->offset-FS_ITEM_LIMIT);
      return $url;
   }
   
   public function siguiente_url()
   {
      $url = '';
      if($this->query!='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&text=".$this->query."&offset=".($this->offset+FS_ITEM_LIMIT);
      else if($this->query=='' AND count($this->resultados)==FS_ITEM_LIMIT)
         $url = $this->url()."&offset=".($this->offset+FS_ITEM_LIMIT);
      return $url;
   }
   
   public function query2history()
   {
      $searches = $this->cache->get_array('articulos_searches');
      $encontrada = FALSE;
      foreach($searches as &$s)
      {
         if($s[0] == $this->query)
         {
            $s[1] += 1;
            $encontrada = TRUE;
            break;
         }
      }
      if(!$encontrada)
         $searches[] = array($this->query ,1);
      $this->cache->set('articulos_searches', $searches);
   }
   
   public function query_searches()
   {
      $aux = $this->cache->get('articulos_searches');
      if($aux)
         return $aux;
      else
         return array();
   }
   
   public function get_familias()
   {
      $fam = new familia();
      return $fam->all();
   }
}

?>
