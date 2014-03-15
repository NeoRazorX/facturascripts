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

class razas extends fs_model
{
   public $id_raza; /// pkey
   public $nombre_raza;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('fbm_razas','plugins/veterinaria/');
      if($p)
      {
         $this->id_raza = $p['id_raza'];
         $this->nombre_raza = $p['nombre_raza'];
      }
      else
      {
         $this->id_raza = '';
         $this->nombre_raza = '';
      }
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->id_raza) )
         return 'index.php?page=veterinaria_razas';
      else
         return 'index.php?page=veterinaria_razas#'.$this->id_raza;
   }
   
   public function get($cod)
   {
      $raza = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id_raza = ".$this->var2str($cod).";");
      if($raza)
         return new razas($raza[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id_raza) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE id_raza = ".$this->var2str($this->id_raza).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre_raza = ".$this->var2str($this->nombre_raza)
            ." WHERE id_raza = ".$this->var2str($this->id_raza).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (nombre_raza)VALUES (".$this->var2str($this->nombre_raza).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id_raza = ".$this->var2str($this->id_raza).";");
   }
   
   public function all()
   {
      $listar=array();
      $razas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY id_raza ASC;");
      if($razas)
      {
         foreach($razas as $p)
            $listar[] = new razas($p);
      }
      return $listar;
   }
}

?>