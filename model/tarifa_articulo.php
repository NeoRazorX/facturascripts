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
require_model('articulo.php');
require_model('tarifa.php');

/**
 * El precio concreto de un artículo en una tarifa determinada.
 */
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
      
      /// estas variables se rellenan desde articulo::get_tarifas()
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
   
   public function url()
   {
      return 'index.php?page=ventas_articulos#tarifas';
   }
   
   public function pvp()
   {
      return $this->pvp*(100-$this->descuento)/100;
   }
   
   public function pvp_iva()
   {
      return $this->pvp*(100-$this->descuento)/100*(100+$this->iva)/100;
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
      $tarifa = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($id).";");
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
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from_articulo($ref)
   {
      $tarlist = array();
      $tarifas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref).";");
      if( $tarifas )
      {
         foreach($tarifas as $t)
            $tarlist[] = new tarifa_articulo($t);
      }
      return $tarlist;
   }
}
