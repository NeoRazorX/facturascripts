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
 * Relaciona a un proveedor con una subcuenta para cada ejercicio
 */
class subcuenta_proveedor extends fs_model
{
   public $codproveedor;
   public $codsubcuenta;
   public $codejercicio;
   public $idsubcuenta;
   public $id; /// pkey
   
   public function __construct($s=FALSE)
   {
      parent::__construct('co_subcuentasprov');
      if($s)
      {
         $this->codproveedor = $s['codproveedor'];
         $this->codsubcuenta = $s['codsubcuenta'];
         $this->codejercicio = $s['codejercicio'];
         $this->idsubcuenta = $this->intval($s['idsubcuenta']);
         $this->id = $this->intval($s['id']);
      }
      else
      {
         $this->codproveedor = NULL;
         $this->codsubcuenta = NULL;
         $this->codejercicio = NULL;
         $this->idsubcuenta = NULL;
         $this->id = NULL;
      }
   }
   
   protected function install()
   {
      return "";
   }
   
   public function get_subcuenta()
   {
      $subc = new subcuenta();
      return $subc->get($this->idsubcuenta);
   }
   
   public function get($pro, $idsc)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codproveedor = ".$this->var2str($pro)."
         AND idsubcuenta = ".$this->var2str($idsc).";");
      if($data)
         return new subcuenta_proveedor($data[0]);
      else
         return FALSE;
   }
   
   public function get2($id)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($id).";");
      if($data)
         return new subcuenta_proveedor($data[0]);
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
         $sql = "UPDATE ".$this->table_name." SET codproveedor = ".$this->var2str($this->codproveedor).",
            codsubcuenta = ".$this->var2str($this->codsubcuenta).",
            codejercicio = ".$this->var2str($this->codejercicio).",
            idsubcuenta = ".$this->var2str($this->idsubcuenta)."
            WHERE id = ".$this->var2str($this->id).";";
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codproveedor,codsubcuenta,codejercicio,idsubcuenta)
            VALUES (".$this->var2str($this->codproveedor).",".$this->var2str($this->codsubcuenta).",
            ".$this->var2str($this->codejercicio).",".$this->var2str($this->idsubcuenta).");";
         $resultado = $this->db->exec($sql);
         if($resultado)
         {
            $newid = $this->db->lastval();
            if($newid)
               $this->id = intval($newid);
         }
         return $resultado;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from_proveedor($codprov)
   {
      $sclist = array();
      $subcs = $this->db->select("SELECT * FROM ".$this->table_name."
         WHERE codproveedor = ".$this->var2str($codprov)." ORDER BY codejercicio DESC;");
      if($subcs)
      {
         foreach($subcs as $s)
            $sclist[] = new subcuenta_proveedor($s);
      }
      return $sclist;
   }
}
