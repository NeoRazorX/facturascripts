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

class desparasitacion extends fs_model
{
   public $nombre;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('fbm_desparasitaciones', 'plugins/veterinaria/');
      if($p)
      {
         $this->nombre = $p['nombre'];
      }
      else
      {
         $this->nombre = NULL;
      }
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      return 'index.php?page=veterinaria_desparasitaciones';
   }
   
   public function get($cod)
   {
      $desparasitacion = $this->db->select("SELECT * FROM ".$this->table_name." WHERE nombre = ".$this->var2str($cod).";");
      if($desparasitacion)
         return new desparasitacion($desparasitacion[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->nombre) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE nombre = ".$this->var2str($this->nombre).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre)
            ." WHERE nombre = ".$this->var2str($this->nombre).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (nombre) VALUES (".$this->var2str($this->nombre).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE nombre = ".$this->var2str($this->nombre).";");
   }
   
   public function all()
   {
      $listad = array();
      $desparasitaciones = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY nombre ASC;");
      if($desparasitaciones)
      {
         foreach($desparasitaciones as $p)
            $listad[] = new desparasitacion($p);
      }
      return $listad;
   }
}

?>