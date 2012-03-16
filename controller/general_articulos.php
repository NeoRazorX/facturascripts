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
require_once 'model/impuesto.php';

class general_articulos extends fs_controller
{
   public $resultados;
   public $query;
   public $offset;
   private $cache;
   public $impuesto;
   public $familia;

   public function __construct()
   {
      parent::__construct('general_articulos', 'Artículos', 'general', FALSE, TRUE);
   }
   
   protected function process()
   {
      $this->custom_divtitle = TRUE;
      
      $this->cache = new fs_cache();
      $articulo = new articulo();
      $this->familia = new familia();
      $this->impuesto = new impuesto();
      
      if( isset($_GET['offset']) )
         $this->offset = intval($_GET['offset']);
      else
         $this->offset = 0;
      
      if( isset($_GET['text']) )
      {
         $this->resultados = $articulo->search($_GET['text'], '', $this->offset);
         $this->query = $_GET['text'];
         $this->query2history();
      }
      else if(isset($_POST['referencia']) AND isset($_POST['codfamilia']) AND isset($_POST['codimpuesto']))
      {
         $this->resultados = $articulo->all($this->offset);
         $this->query = '';
         
         $articulo->set_referencia($_POST['referencia']);
         $articulo->descripcion = $_POST['referencia'];
         $articulo->codfamilia = $_POST['codfamilia'];
         $articulo->codimpuesto = $_POST['codimpuesto'];
         if( $articulo->save() )
         {
            $imp = $this->impuesto->get($_POST['codimpuesto']);
            if($imp)
               $imp->set_default();
            header('location: '.$articulo->url());
         }
         else
         {
            $this->new_error_msg("¡Error al crear el articulo!".$articulo->error_msg);
         }
      }
      else if( isset($_GET['delete']) )
      {
         $this->resultados = $articulo->all($this->offset);
         $this->query = '';
         
         $art = $articulo->get($_GET['delete']);
         if($art)
         {
            if( $art->delete() )
               $this->new_message("Articulo ".$art->referencia." eliminado correctamente.");
            else
               $this->new_error_msg("¡Error al eliminarl el articulo!".$art->error_msg);
         }
      }
      else
      {
         $this->resultados = $articulo->all($this->offset);
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
}

?>
