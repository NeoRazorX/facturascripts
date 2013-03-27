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
require_once 'model/articulo.php';

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
   
   public function get($cod)
   {
      $tarifa = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codtarifa = ".$this->var2str($cod).";");
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
         $tarifas = $this->db->select("SELECT * FROM ".$this->table_name.
                 " ORDER BY codtarifa ASC;");
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

class tarifa_articulo extends fs_model
{
   public $id;
   public $referencia;
   public $codtarifa;
   public $nombre;
   public $pvp;
   public $descuento;
   public $iva;
   
   public function __construct($t = FALSE)
   {
      parent::__construct('articulostarifas');
      if( $t )
      {
         $this->id = $this->intval( $t['id'] );
         $this->referencia = $t['referencia'];
         $this->codtarifa = $t['codtarifa'];
         $this->descuento = floatval($t['descuento']);
      }
      else
      {
         $this->id = NULL;
         $this->referencia = NULL;
         $this->codtarifa = NULL;
         $this->descuento = 0;
      }
      $this->nombre = NULL;
      $this->pvp = 0;
      $this->iva = 0;
   }
   
   protected function install()
   {
      new articulo();
      new tarifa();
      return '';
   }
   
   public function show_descuento()
   {
      return number_format($this->descuento, 2, '.', '');
   }
   
   public function show_pvp($coma=TRUE)
   {
      if( $coma )
         return number_format($this->pvp*(100-$this->descuento)/100, 2, '.', ' ');
      else
         return number_format($this->pvp*(100-$this->descuento)/100, 2, '.', '');
   }
   
   public function show_pvp_iva($coma=TRUE)
   {
      if( $coma )
         return number_format($this->pvp*(100-$this->descuento)/100*(100+$this->iva)/100, 2, '.', ' ');
      else
         return number_format($this->pvp*(100-$this->descuento)/100*(100+$this->iva)/100, 2, '.', '');
   }
   
   public function set_pvp_iva($p)
   {
      $pvpi = floatval($p);
      if($this->pvp > 0)
         $this->descuento = 100 - 10000*$pvpi/($this->pvp*(100+$this->iva));
      else
         $this->descuento = 0;
   }
   
   public function get($id)
   {
      $tarifa = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE id = ".$this->var2str($id).";");
      if( $tarifa )
         return new tarifa_articulo($tarifa[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET referencia = ".$this->var2str($this->referencia).",
            codtarifa = ".$this->var2str($this->codtarifa).",
            descuento = ".$this->var2str($this->descuento)."
            WHERE id = ".$this->var2str($this->id).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (referencia,codtarifa,descuento) VALUES
            (".$this->var2str($this->referencia).",".$this->var2str($this->codtarifa).",
            ".$this->var2str($this->descuento).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name.
              " WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from_articulo($ref)
   {
      $tarlist = array();
      $tarifas = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE referencia = ".$this->var2str($ref).";");
      if( $tarifas )
      {
         foreach($tarifas as $t)
            $tarlist[] = new tarifa_articulo($t);
      }
      return $tarlist;
   }
}

?>