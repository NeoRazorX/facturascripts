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

class desparasitaciones extends fs_model
{
   public $id_tipo_desp; /// pkey
   public $nombre_desp;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('fbm_desparasitaciones', 'plugins/veterinaria/');
      if($p)
      {
         $this->id_tipo_desp = $p['id_tipo_desp'];
         $this->nombre_desp = $p['nombre_desp'];
      }
      else
      {
         $this->id_tipo_desp = '';
         $this->nombre_desp = '';
      }
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->id_tipo_desp) )
         return 'index.php?page=veterinaria_desparasitaciones';
      else
         return 'index.php?page=veterinaria_desparasitaciones#'.$this->id_tipo_desp;
   }
   
   public function get($cod)
   {
      $desparasitacion = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id_tipo_desp = ".$this->var2str($cod).";");
      if($desparasitacion)
         return new desparasitaciones($desparasitacion[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id_tipo_desp) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id_tipo_desp = ".$this->var2str($this->id_tipo_desp).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre_desp = ".$this->var2str($this->nombre_desp)
            ." WHERE id_tipo_desp = ".$this->var2str($this->id_tipo_desp).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (nombre_desp) VALUES (".$this->var2str($this->nombre_desp).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id_tipo_desp = ".$this->var2str($this->id_tipo_desp).";");
   }
   
   public function all()
   {
      $listad = array();
      $desparasitaciones = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY id_tipo_desp ASC;");
      if($desparasitaciones)
      {
         foreach($desparasitaciones as $p)
            $listad[] = new desparasitaciones($p);
      }
      return $listad;
   }
}

?>