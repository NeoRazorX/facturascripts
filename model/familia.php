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

require_once 'base/fs_model.php';
require_once 'model/articulo.php';

class familia extends fs_model
{
   public $codfamilia;
   public $descripcion;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('familias');
      if($f)
      {
         $this->codfamilia = $f['codfamilia'];
         $this->descripcion = $f['descripcion'];
      }
      else
      {
         $this->codfamilia = NULL;
         $this->descripcion = '';
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      return '';
   }
   
   public function url()
   {
      if( is_null($this->codfamilia) )
         return "index.php?page=general_familias";
      else
         return "index.php?page=general_familia&cod=".$this->codfamilia;
   }
   
   public function is_default()
   {
      return ( $this->codfamilia == $this->default_items->codfamilia() );
   }
   
   public function get($cod)
   {
      $f = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codfamilia = ".$this->var2str($cod).";");
      if($f)
         return new familia($f[0]);
      else
         return FALSE;
   }

   public function get_articulos($offset=0, $limit=FS_ITEM_LIMIT)
   {
      $articulo = new articulo();
      return $articulo->all_from_familia($this->codfamilia, $offset, $limit);
   }
   
   public function exists()
   {
      if( is_null($this->codfamilia) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE codfamilia = ".$this->var2str($this->codfamilia).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codfamilia = trim($this->codfamilia);
      $this->descripcion = $this->no_html($this->descripcion);
      
      if( !preg_match("/^[A-Z0-9_]{1,4}$/i", $this->codfamilia) )
         $this->new_error_msg("Código de familia no válido. Deben ser entre 1 y 4 caracteres alfanuméricos.");
      else if( strlen($this->descripcion) < 1 OR strlen($this->descripcion) > 100 )
         $this->new_error_msg("Descripción de familia no válida.");
      else
         $status = TRUE;
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         if( $this->exists() )
            $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion)."
               WHERE codfamilia = ".$this->var2str($this->codfamilia).";";
         else
            $sql = "INSERT INTO ".$this->table_name." (codfamilia,descripcion) VALUES
               (".$this->var2str($this->codfamilia).",".$this->var2str($this->descripcion).");";
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name."
         WHERE codfamilia = ".$this->var2str($this->codfamilia).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_familia_all');
   }
   
   public function all()
   {
      $famlist = $this->cache->get_array('m_familia_all');
      if( !$famlist )
      {
         $familias = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY descripcion ASC;");
         if($familias)
         {
            foreach($familias as $f)
               $famlist[] = new familia($f);
         }
         $this->cache->set('m_familia_all', $famlist);
      }
      return $famlist;
   }
   
   public function search($query)
   {
      $famlist = array();
      $query = $this->no_html( strtolower($query) );
      
      $familias = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE lower(descripcion) ~~ '%".$query."%' ORDER BY descripcion ASC;");
      if($familias)
      {
         foreach($familias as $f)
            $famlist[] = new familia($f);
      }
      
      return $famlist;
   }
}

?>
