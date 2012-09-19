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
require_once 'model/paquete.php';

class general_paquetes extends fs_controller
{
   public $paquete;
   public $cache_paquete;
   public $results;

   public function __construct()
   {
      parent::__construct('general_paquetes', 'Paquetes', 'general', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->paquete = new paquete();
      $this->cache_paquete = new cache_paquete();
      $this->buttons[] = new fs_button('b_nuevo_paquete', 'nuevo');
      
      if( $this->query != '' )
         $this->new_search();
      else if( isset($_GET['add2cache']) )
         $this->cache_paquete->add($_GET['add2cache']);
      else if( isset($_GET['cleancache']) )
         $this->cache_paquete->clean();
      else if( isset($_GET['fillcache']) )
      {
         $art = new articulo();
         foreach($art->all(0, 100) as $a)
            $this->cache_paquete->add($a->referencia);
      }
   }
   
   public function version() {
      return parent::version().'-3';
   }
   
   private function new_search()
   {
      $art = new articulo();
      $cache = new fs_cache();
      $this->results = $cache->get_array('search_articulo_'.$this->query, 600);
      if( count($this->results) < 1 )
      {
         $this->results = $art->search($this->query);
         $cache->set('search_articulo_'.$this->query, $this->results);
      }
   }
}

?>
