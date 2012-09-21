<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2012  Carlos Garcia Gomez  neorazorx@gmail.com
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

class tarifa extends fs_model
{
   public $codtarifa;
   public $nombre;
   public $incporcentual;
   
   public function __construct($t = FALSE)
   {
      parent::__construct('tarifas');
      if( $t )
      {
         $this->codtarifa = $t['codtarifa'];
         $this->nombre = $t['nombre'];
         $this->incporcentual = floatval( $t['incporcentual'] );
      }
      else
      {
         $this->codtarifa = NULL;
         $this->nombre = NULL;
         $this->incporcentual = 0;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get($cod)
   {
      $tarifa = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codtarifa = ".$this->var2str($cod).";");
      if($tarifa)
         return new tarifa( $tarifa[0] );
      else
         return FALSE;
   }
   
   public function get_new_codigo()
   {
      $cod = $this->db->select("SELECT MAX(codtarifa::integer) as cod FROM ".$this->table_name.";");
      if($cod)
         return sprintf('%06s', (1 + intval($cod[0]['cod'])));
      else
         return '000001';
   }
   
   public function exists()
   {
      if( is_null($this->codtarifa) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE codtarifa = ".$this->var2str($this->codtarifa).";");
   }
   
   public function save()
   {
      $this->clean_cache();
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).",
            incporcentual = ".$this->var2str($this->incporcentual)."
            WHERE codtarifa = ".$this->var2str($this->codtarifa).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codtarifa,nombre,incporcentual) VALUES
            (".$this->var2str($this->codtarifa).",".$this->var2str($this->nombre).",
            ".$this->var2str($this->incporcentual).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name."
         WHERE codtarifa = ".$this->var2str($this->codtarifa).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_tarifa_all');
   }
   
   public function all()
   {
      $tarlist = $this->cache->get_array('m_tarifa_all');
      if( !$tarlist )
      {
         $tarifas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codtarifa ASC;");
         if($tarifas)
         {
            foreach($tarifas as $t)
               $tarlist[] = new tarifa($t);
         }
         $this->cache->set('m_tarifa_all', $tarlist);
      }
      return $tarlist;
   }
}

?>