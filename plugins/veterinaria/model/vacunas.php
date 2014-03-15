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

class vacunas extends fs_model
{
   public $id_tipo_vacuna; /// pkey
   public $nombre_vacuna;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('fbm_vacunas','plugins/veterinaria/');
      if($p)
      {
         $this->id_tipo_vac = $p['id_tipo_vacuna'];
         $this->nombre_vac = $p['nombre_vacuna'];
      }
      else
      {
         $this->id_tipo_vac = '';
         $this->nombre_vac = '';
      }
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->id_tipo_vac) )
         return 'index.php?page=veterinaria_vacunas';
      else
         return 'index.php?page=veterinaria_vacunas#'.$this->id_tipo_vac;
   }
   
   public function get($cod)
   {
      $vacuna = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id_tipo_vacuna = ".$this->var2str($cod).";");
      if($vacuna)
         return new vacunas($vacuna[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id_tipo_vac) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id_tipo_vacuna = ".$this->var2str($this->id_tipo_vac).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre_vacuna = ".$this->var2str($this->nombre_vac)
            ." WHERE id_tipo_vacuna = ".$this->var2str($this->id_tipo_vac).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (nombre_vacuna) VALUES (".$this->var2str($this->nombre_vac).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id_tipo_vacuna = ".$this->var2str($this->id_tipo_vac).";");
   }
   
   public function all()
   {
      $listav = array();
      $vacunas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY id_tipo_vacuna ASC;");
      if($vacunas)
      {
         foreach($vacunas as $p)
            $listav[] = new vacunas($p);
      }
      return $listav;
   }
}

?>