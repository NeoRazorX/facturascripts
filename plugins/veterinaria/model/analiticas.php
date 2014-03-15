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

class analiticas extends fs_model
{
   public $id_tipo_analitica; /// pkey
   public $nombre_analitica;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('fbm_analiticas', 'plugins/veterinaria/');
      if($p)
      {
         $this->id_tipo_ana = $p['id_tipo_analitica'];
         $this->nombre_ana = $p['nombre_analitica'];
      }
      else
      {
         $this->id_tipo_ana = '';
         $this->nombre_ana = '';
      }
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->id_tipo_vac) )
         return 'index.php?page=veterinaria_analiticas';
      else
         return 'index.php?page=veterinaria_analiticas#'.$this->id_tipo_ana;
   }
   
   public function get($cod)
   {
      $analitica = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id_tipo_analitica = ".$this->var2str($cod).";");
      if($analitica)
         return new analiticas($analitica[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id_tipo_ana) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id_tipo_analitica = ".$this->var2str($this->id_tipo_ana).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre_analitica = ".$this->var2str($this->nombre_ana)
            ." WHERE id_tipo_analitica = ".$this->var2str($this->id_tipo_ana).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (nombre_analitica) VALUES (".$this->var2str($this->nombre_ana).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id_tipo_analitica = ".$this->var2str($this->id_tipo_ana).";");
   }
   
   public function all()
   {
      $listaa = array();
      $analiticas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY id_tipo_analitica ASC;");
      if($analiticas)
      {
         foreach($analiticas as $p)
            $listaa[] = new analiticas($p);
      }
      return $listaa;
   }
}

?>