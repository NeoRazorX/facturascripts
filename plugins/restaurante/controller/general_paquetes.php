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
require_model('paquete.php');

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
      $this->buttons[] = new fs_button_img('b_nuevo_paquete', 'Nuevo');
      
      if( $this->cache->error() )
      {
         $this->new_error_msg( 'Memcache está deshabilitado y es necesario para continuar. '.
                 $this->cache->error_msg() );
      }
      
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
   
   private function new_search()
   {
      /// cambiamos la plantilla HTML
      $this->template = 'ajax_paquetes';
      
      $art = new articulo();
      $this->results = $art->search($this->query);
   }
}

?>
