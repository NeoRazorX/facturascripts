<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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

class clan_familiar extends fs_model
{
   public $codclan;
   public $nombre;
   
   public function __construct($c = FALSE)
   {
      parent::__construct('clanes', 'plugins/supermercado/');
      if($c)
      {
         $this->codclan = $c['codclan'];
         $this->nombre = $c['nombre'];
      }
      else
      {
         $this->codclan = NULL;
         $this->nombre = NULL;
      }
   }
   
   protected function install()
   {
      
   }
   
   public function get($cod)
   {
      $c = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codclan = ".$this->var2str($cod).";");
      if($c)
         return new clan_familiar($c[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codigo) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE codclan = ".$this->var2str($this->codclan).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre)."
               WHERE codclan = ".$this->var2str($this->codclan).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codclan,nombre)
               VALUES (".$this->var2str($this->codclan).",".$this->var2str($this->nombre).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name.
              " WHERE codclan = ".$this->var2str($this->codclan).";");
   }
   
   public function all()
   {
      $clanlist = array();
      $clanes = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY nombre ASC;");
      if($clanes)
      {
         foreach($clanes as $c)
            $clanlist[] = new clan_familiar($c);
      }
      return $clanlist;
   }
}

?>