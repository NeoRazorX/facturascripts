<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2014  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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
 * Recibos de proveedores.
 */
class recibo_proveedor extends fs_model
{
   public $recibo;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('recibospro', 'plugins/recibos_para_pagos_y_cobros/');
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->idrecibo) )
         return 'index.php?page=recibos_proveedores';
      else
         return 'index.php?page=recibo_proveedor&id='.$this->idrecibo;
   }
   
   public function proveedor_url()
   {
      if( is_null($this->codproveedor) )
         return "index.php?page=recibos_proveedor";
      else
         return "index.php?page=recibos_proveedor&proveedor=".$this->codproveedor;
   }
   
   public function get($id)
   {
      $recibo = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idrecibo = ".$this->var2str($id).";");
      if($recibo)
         return new recibo_proveedor($recibo[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod)
   {
      $recibo = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = ".$this->var2str($cod).";");
      if($recibo)
         return new recibo_proveedor($recibo[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idrecibo) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idrecibo = ".$this->var2str($this->idrecibo).";");
   }
   
   public function new_idrecibo()
   {
      $newid = $this->db->nextval($this->table_name.'_idrecibo_seq');
      if($newid)
         $this->idrecibo = intval($newid);
   }
   
   public function test()
   {
      /* Todavía no implementado */
   }
   
   public function full_test($duplicados = TRUE)
   {
      /* Todavía no implementado */
   }
   
   public function save()
   {
      /* Todavía no implementado, no se conocen los campos */
   }
   
   public function delete()
   {
      /* Todavía no implementado */
   }
   
   private function clean_cache()
   {
      /* Todavía no implementado */
   }
   
   public function all($offset=0, $limit=FS_ITEM_LIMIT)
   {
      /* Falta utilizar offset y limit */
      $reciboslist = array();
      
      $sql = "SELECT * FROM ".$this->table_name." ORDER BY codigo DESC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $reciboslist[] = new recibo_proveedor($d);
      }
      
      return $reciboslist;
   }
   
   public function all_from_proveedor($codproveedor, $offset=0)
   {
      /* Falta utilizar offset */
      $reciboslist = array();
      
      $sql = "SELECT * FROM ".$this->table_name." WHERE codproveedor = ".$this->var2str($codproveedor)." ORDER BY codigo DESC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $reciboslist[] = new recibo_proveedor($d);
      }
      
      return $reciboslist;
   }
   
   public function search($query, $offset=0)
   {
      /* Todavía no implementado */
   }
}
