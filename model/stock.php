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

/**
 * La cantidad en inventario de un artículo en un almacén concreto.
 */
class stock extends fs_model
{
   public $idstock;
   public $codalmacen;
   public $referencia;
   public $nombre;
   public $cantidad;
   public $reservada;
   public $disponible;
   public $pterecibir;
   public $stockmin;
   public $stockmax;
   public $cantidadultreg;
   
   public function __construct($s=FALSE)
   {
      parent::__construct('stocks');
      if($s)
      {
         $this->idstock = $this->intval($s['idstock']);
         $this->codalmacen = $s['codalmacen'];
         $this->referencia = $s['referencia'];
         $this->nombre = $s['nombre'];
         $this->cantidad = floatval($s['cantidad']);
         $this->reservada = floatval($s['reservada']);
         $this->disponible = floatval($s['disponible']);
         $this->pterecibir = floatval($s['pterecibir']);
         $this->stockmin = floatval($s['stockmin']);
         $this->stockmax = floatval($s['stockmax']);
         $this->cantidadultreg = floatval($s['cantidadultreg']);
      }
      else
      {
         $this->idstock = NULL;
         $this->codalmacen = NULL;
         $this->referencia = NULL;
         $this->nombre = '';
         $this->cantidad = 0;
         $this->reservada = 0;
         $this->disponible = 0;
         $this->pterecibir = 0;
         $this->stockmin = 0;
         $this->stockmax = 0;
         $this->cantidadultreg = 0;
      }
   }
   
   protected function install()
   {
      new articulo();
      return '';
   }
   
   public function set_cantidad($c=0)
   {
      $c = floatval($c);
      
      $this->cantidad = 0;
      if($c > 0)
      {
         $this->cantidad = $c;
      }
      
      $this->disponible = $this->cantidad - $this->reservada;
   }
   
   public function sum_cantidad($c=0)
   {
      $c = floatval($c);
      
      $this->cantidad += $c;
      if($this->cantidad < 0)
      {
         $this->cantidad = 0;
      }
      
      $this->disponible = $this->cantidad - $this->reservada;
   }
   
   public function get($id)
   {
      $stock = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idstock = ".$this->var2str($id).";");
      if($stock)
      {
         return new stock($stock[0]);
      }
      else
         return FALSE;
   }
   
   public function get_by_referencia($ref)
   {
      $stock = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref).";");
      if($stock)
      {
         return new stock($stock[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idstock) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idstock = ".$this->var2str($this->idstock).";");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET codalmacen = ".$this->var2str($this->codalmacen).",
            referencia = ".$this->var2str($this->referencia).", nombre = ".$this->var2str($this->nombre).",
            cantidad = ".$this->var2str($this->cantidad).", reservada = ".$this->var2str($this->reservada).",
            disponible = ".$this->var2str($this->disponible).", pterecibir = ".$this->var2str($this->pterecibir).",
            stockmin = ".$this->var2str($this->stockmin).", stockmax = ".$this->var2str($this->stockmax).",
            cantidadultreg = ".$this->var2str($this->cantidadultreg)."
            WHERE idstock = ".$this->var2str($this->idstock).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codalmacen,referencia,nombre,cantidad,reservada,
            disponible,pterecibir,stockmin,stockmax,cantidadultreg) VALUES (".$this->var2str($this->codalmacen).",
            ".$this->var2str($this->referencia).",".$this->var2str($this->nombre).",".$this->var2str($this->cantidad).",
            ".$this->var2str($this->reservada).",".$this->var2str($this->disponible).",
            ".$this->var2str($this->pterecibir).",".$this->var2str($this->stockmin).",
            ".$this->var2str($this->stockmax).",".$this->var2str($this->cantidadultreg).");";
         
         if( $this->db->exec($sql) )
         {
            $this->idstock = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idstock = ".$this->var2str($this->idstock).";");
   }
   
   public function all_from_articulo($ref)
   {
      $stocklist = array();
      $stocks = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref)." ORDER BY codalmacen ASC;");
      if($stocks)
      {
         foreach($stocks as $s)
            $stocklist[] = new stock($s);
      }
      return $stocklist;
   }
   
   public function total_from_articulo($ref)
   {
      $num = 0;
      
      $stocks = $this->db->select("SELECT SUM(cantidad) as total FROM ".$this->table_name." WHERE referencia = ".$this->var2str($ref).";");
      if($stocks)
      {
         $num = floatval($stocks[0]['total']);
      }
      
      return $num;
   }
   
   public function count()
   {
      $num = 0;
      
      $stocks = $this->db->select("SELECT COUNT(*) as total FROM ".$this->table_name.";");
      if($stocks)
      {
         $num = intval($stocks[0]['total']);
      }
      
      return $num;
   }
   
   public function count_by_articulo()
   {
      $num = 0;
      
      $stocks = $this->db->select("SELECT COUNT(DISTINCT referencia) as total FROM ".$this->table_name.";");
      if($stocks)
      {
         $num = intval($stocks[0]['total']);
      }
      
      return $num;
   }
}
