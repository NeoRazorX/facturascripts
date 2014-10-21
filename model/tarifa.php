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
 * Una tarifa para los artículos.
 */
class tarifa extends fs_model
{
   public $codtarifa;
   public $nombre;
   public $incporcentual;
   public $inclineal;
   
   public function __construct($t = FALSE)
   {
      parent::__construct('tarifas');
      if( $t )
      {
         $this->codtarifa = $t['codtarifa'];
         $this->nombre = $t['nombre'];
         $this->incporcentual = floatval( $t['incporcentual'] );
         $this->inclineal = floatval( $t['inclineal'] );
      }
      else
      {
         $this->codtarifa = NULL;
         $this->nombre = NULL;
         $this->incporcentual = 0;
         $this->inclineal = 0;
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      return '';
   }
   
   public function dtopor()
   {
      return 0-$this->incporcentual;
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
      $cod = $this->db->select("SELECT MAX(".$this->db->sql_to_int('codtarifa').") as cod FROM ".$this->table_name.";");
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
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codtarifa = ".$this->var2str($this->codtarifa).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codtarifa = trim($this->codtarifa);
      $this->nombre = $this->no_html($this->nombre);
      
      if( !preg_match("/^[A-Z0-9]{1,6}$/i", $this->codtarifa) )
         $this->new_error_msg("Código de tarifa no válido.");
      else if( strlen($this->nombre) < 1 OR strlen($this->nombre) > 50 )
         $this->new_error_msg("Nombre de tarifa no válido.");
      else
         $status = TRUE;
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).",
               incporcentual = ".$this->var2str($this->incporcentual).",
               inclineal = ".$this->var2str($this->inclineal)."
               WHERE codtarifa = ".$this->var2str($this->codtarifa).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codtarifa,nombre,incporcentual,inclineal)
               VALUES (".$this->var2str($this->codtarifa).",".$this->var2str($this->nombre).",
               ".$this->var2str($this->incporcentual).",".$this->var2str($this->inclineal).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codtarifa = ".$this->var2str($this->codtarifa).";");
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
