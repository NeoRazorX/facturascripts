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

/**
 * Un grupo de clientes, que puede estar asociado a una tarifa.
 */
class grupo_clientes extends fs_model
{
   public $codgrupo;
   public $nombre;
   public $codtarifa;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('gruposclientes');
      
      if($g)
      {
         $this->codgrupo = $g['codgrupo'];
         $this->nombre = $g['nombre'];
         $this->codtarifa = $g['codtarifa'];
      }
      else
      {
         $this->codgrupo = NULL;
         $this->nombre = NULL;
         $this->codtarifa = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get_new_codigo()
   {
      $sql = "SELECT MAX(".$this->db->sql_to_int('codgrupo').") as cod FROM ".$this->table_name.";";
      $cod = $this->db->select($sql);
      if($cod)
         return 1 + intval($cod[0]['cod']);
      else
         return 1;
   }
   
   public function get($cod)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codgrupo = ".$this->var2str($cod).";");
      if($data)
         return new grupo_clientes($data[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codgrupo) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codgrupo = ".$this->var2str($this->codgrupo).";");
   }
   
   public function test()
   {
      $this->nombre = $this->no_html($this->nombre);
      return TRUE;
   }
   
   public function save()
   {
      $this->test();
      
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).", "
            . "codtarifa = ".$this->var2str($this->codtarifa)." WHERE codgrupo = ".$this->var2str($this->codgrupo).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codgrupo,nombre,codtarifa) VALUES "
            . "(".$this->var2str($this->codgrupo).",".$this->var2str($this->nombre).",".$this->var2str($this->codtarifa).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codgrupo = ".$this->var2str($this->codgrupo).";");
   }
   
   public function all()
   {
      $glist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codgrupo ASC;");
      if($data)
      {
         foreach($data as $d)
            $glist[] = new grupo_clientes($d);
      }
      
      return $glist;
   }
}
