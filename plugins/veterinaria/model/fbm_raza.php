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

class fbm_raza extends fs_model
{
   public $id; /// pkey
   public $especie;
   public $nombre;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('fbm_razas','plugins/veterinaria/');
      if($p)
      {
         $this->id = $p['id_raza'];
         $this->especie = $p['especie'];
         $this->nombre = $p['nombre'];
      }
      else
      {
         $this->id = NULL;
         $this->especie = NULL;
         $this->nombre = NULL;
      }
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      return 'index.php?page=veterinaria_razas';
   }
   
   public function get($id)
   {
      $raza = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id_raza = ".$this->var2str($id).";");
      if($raza)
      {
         return new fbm_raza($raza[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE id_raza = ".$this->var2str($this->id).";");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET especie = ".$this->var2str($this->especie).", "
            . "nombre = ".$this->var2str($this->nombre)." WHERE id_raza = ".$this->var2str($this->id).";";
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (especie,nombre) VALUES (".$this->var2str($this->especie).",".$this->var2str($this->nombre).");";
         
         if( $this->db->exec($sql) )
         {
            $this->id = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id_raza = ".$this->var2str($this->id).";");
   }
   
   public function all()
   {
      $listar = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY especie ASC, nombre ASC;");
      if($data)
      {
         foreach($data as $d)
            $listar[] = new fbm_raza($d);
      }
      
      return $listar;
   }
}
