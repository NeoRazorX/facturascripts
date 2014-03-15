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

require_once 'base/fs_model.php';

class especies extends fs_model
{
   public $id_especie; /// pkey
   public $nombre_especie;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('fbm_especies', 'plugins/veterinaria/');
      if($p)
      {
         $this->id_especie = $p['id_especie'];
         $this->nombre_especie = $p['nombre_especie'];
      }
      else
      {
         $this->id_especie = '';
         $this->nombre_especie = '';
      }
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->id_especie) )
         return 'index.php?page=veterinaria_especies';
      else
         return 'index.php?page=veterinaria_especies#'.$this->id_especie;
   }
   
   public function get($cod)
   {
      $especies = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id_especie = ".$this->var2str($cod).";");
      if($especies)
         return new especies($especies[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id_especie) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE id_especie = ".$this->var2str($this->id_especie).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre_especie = ".$this->var2str($this->nombre_especie)
            ." WHERE id_especie = ".$this->var2str($this->id_especie).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (nombre_especie) VALUES (".$this->var2str($this->nombre_especie).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id_especie = ".$this->var2str($this->id_especie).";");
   }
   
   public function all()
   {
      $listae = array();
      $especies = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY id_especie ASC;");
      if($especies)
      {
         foreach($especies as $p)
            $listae[] = new especies($p);
      }
      return $listae;
   }
}

?>