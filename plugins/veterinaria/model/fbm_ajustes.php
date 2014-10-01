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

class fbm_ajustes extends fs_model
{
   public $id;
   public $tipo;
   public $nombre;
   public $dias;
   
   public function __construct($a=FALSE)
   {
      parent::__construct('fbm_ajustes', 'plugins/veterinaria/');
      if($a)
      {
         $this->id = $this->intval($a['id']);
         $this->tipo = $a['tipo'];
         $this->nombre = $a['nombre'];
         $this->dias = $this->intval($a['dias']);
      }
      else
      {
         $this->id = null;
         $this->tipo = NULL;
         $this->nombre = NULL;
         $this->dias = 0;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($id).";");
      if($data)
         return new fbm_ajustes($data[0]);
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
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET tipo = ".$this->var2str($this->tipo).",
            nombre = ".$this->var2str($this->nombre).", dias = ".$this->var2str($this->dias)."
            WHERE id = ".$this->var2str($this->id).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (tipo,nombre,dias) VALUES (".$this->var2str($this->tipo).",
                 ".$this->var2str($this->nombre).",".$this->var2str($this->dias).");";
         
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
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from_tipo($tipo='analitica')
   {
      $lista = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE tipo = ".$this->var2str($tipo)." ORDER BY nombre ASC;");
      if($data)
      {
         foreach($data as $d)
            $lista[] = new fbm_ajustes($d);
      }
      
      return $lista;
   }
}
