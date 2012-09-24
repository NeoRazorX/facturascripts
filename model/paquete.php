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
require_once 'base/fs_model.php';
require_once 'model/articulo.php';

class cache_paquete
{
   private $cache;
   public $articulos;
   
   public function __construct()
   {
      $this->cache = new fs_cache();
      $this->articulos = $this->cache->get_array('cache_paquetes');
   }
   
   public function __destruct()
   {
      $this->cache->set('cache_paquetes', $this->articulos);
   }
   
   public function add_url($ref)
   {
      return "index.php?page=general_paquetes&add2cache=".$ref;
   }
   
   public function add($ref)
   {
      $articulo = new articulo();
      $articulo = $articulo->get($ref);
      if($articulo)
      {
         $paq = new paquete();
         if(!in_array($articulo, $this->articulos) AND !$paq->get($ref))
            $this->articulos[] = $articulo;
      }
   }
   
   public function clean()
   {
      $this->articulos = array();
   }
}

class subpaquete extends fs_model
{
   public $id;
   public $referenciapaq;
   public $grupo;
   public $referencia;
   public $existe;
   
   public function __construct($s=FALSE)
   {
      parent::__construct('subpaquetes');
      if($s)
      {
         $this->id = $this->intval($s['id']);
         $this->referenciapaq = $s['referenciapaq'];
         $this->grupo = $this->intval($s['grupo']);
         $this->referencia = $s['referencia'];
         $this->existe = TRUE;
      }
      else
      {
         $this->id = NULL;
         $this->referenciapaq = NULL;
         $this->grupo = 1;
         $this->referencia = NULL;
         $this->existe = FALSE;
      }
   }
   
   protected function install()
   {
      $articulo = new articulo();
      return "";
   }
   
   public function get($id)
   {
      if( isset($id) )
      {
         $subpack = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($id).";");
         if($subpack)
            return new subpaquete($subpack[0]);
         else
            return FALSE;
      }
      else
         return FALSE;
   }
   
   public function get_paquete()
   {
      $pack = new paquete();
      return $pack->get($this->referenciapaq);
   }
   
   public function get_articulo()
   {
      $articulo = new articulo();
      return $articulo->get($this->referencia);
   }
   
   public function all_from_paquete($ref)
   {
      $subpaqlist = array();
      $subpaquetes = $this->db->select("SELECT * FROM ".$this->table_name."
         WHERE referenciapaq = ".$this->var2str($ref)." ORDER BY referencia ASC;");
      if($subpaquetes)
      {
         foreach($subpaquetes as $s)
            $subpaqlist[] = new subpaquete($s);
      }
      return $subpaqlist;
   }

   public function exists()
   {
      if( is_null($this->id) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function save()
   {
      if( $this->exists() )
         return TRUE;
      else
         return $this->db->exec("INSERT INTO ".$this->table_name." (referenciapaq,grupo,referencia)
            VALUES (".$this->var2str($this->referenciapaq).",".$this->var2str($this->grupo).",
            ".$this->var2str($this->referencia).");");
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
}

class paquete extends fs_model
{
   public $referencia;
   public $grupos;
   public $subpaquetes;
   public $articulo;

   public function __construct($p=FALSE)
   {
      parent::__construct('paquetes');
      if($p)
      {
         $this->referencia = $p['referencia'];
         $subpaqs = $this->get_subpaquetes();
         $this->grupos = 1;
         foreach($subpaqs as $s)
         {
            if($s->grupo > $this->grupos)
               $this->grupos = $s->grupo;
         }
         $this->subpaquetes = $subpaqs;
         $this->articulo = new articulo();
         $this->articulo = $this->articulo->get($this->referencia);
      }
      else
      {
         $this->referencia = NULL;
         $this->grupos = 1;
         $this->subpaquetes = array();
      }
   }

   protected function install()
   {
      $this->clean_cache();
      return "";
   }
   
   public function url()
   {
      if( isset($this->referencia) )
         return "index.php?page=general_paquete&ref=".$this->referencia;
      else
         return "index.php?page=general_paquetes";
   }

   public function get($ref)
   {
      if( isset($ref) )
      {
         $pack = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref).";");
         if($pack)
            return new paquete($pack[0]);
         else
            return FALSE;
      }
      else
         return FALSE;
   }
   
   public function get_subpaquetes()
   {
      $subpaq = new subpaquete();
      return $subpaq->all_from_paquete($this->referencia);
   }
   
   public function get_subgrupos_for($grupo=1)
   {
      $grupolist = array();
      $articulos = array($this->referencia);
      foreach($this->subpaquetes as $s)
      {
         if($s->grupo == $grupo)
            $grupolist[] = $s;
         $articulos[] = $s->referencia;
      }
      $cachep = new cache_paquete();
      foreach($cachep->articulos as $a)
      {
         if( !in_array($a->referencia, $articulos) )
         {
            $subpaq = new subpaquete();
            $subpaq->referenciapaq = $this->referencia;
            $subpaq->grupo = $grupo;
            $subpaq->referencia = $a->referencia;
            $grupolist[] = $subpaq;
         }
      }
      return $grupolist;
   }
   
   public function get_grupos()
   {
      return range(1, $this->grupos);
   }
   
   public function set_grupos($g)
   {
      $this->grupos = intval($g);
      if($this->grupos < 1)
         $this->grupos = 1;
   }
   
   public function exists()
   {
      if( is_null($this->referencia) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE referencia = ".$this->var2str($this->referencia).";");
   }
   
   public function save()
   {
      $this->clean_cache();
      if( $this->exists() )
         return TRUE;
      else
         return $this->db->exec("INSERT INTO ".$this->table_name." (referencia)
            VALUES (".$this->var2str($this->referencia).");");
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name."
         WHERE referencia = ".$this->var2str($this->referencia).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_paquete_all');
   }
   
   public function all()
   {
      $paqlist = $this->cache->get_array('m_paquete_all');
      if( !$paqlist )
      {
         $paqs = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY referencia ASC;");
         if($paqs)
         {
            foreach($paqs as $p)
               $paqlist[] = new paquete($p);
         }
         $this->cache->set('m_paquete_all', $paqlist);
      }
      return $paqlist;
   }
}

?>
