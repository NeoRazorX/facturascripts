<?php
/*
 * This file is part of FacturaSctipts
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
 * Recibo de un cliente.
 */
class recibo_cliente extends fs_model
{
	
   
   public function __construct($f=FALSE)
   {
	   
   }

   protected function install()
   {
      
   }
   
   public function url()
   {
      if( is_null($this->idfactura) )
         return 'index.php?page=recibos_clientes';
      else
         return 'index.php?page=recibo_cliente&id='.$this->idfactura;
   }
   
   public function cliente_url()
   {
      if( is_null($this->codcliente) )
         return "index.php?page=recibos_clientes";
      else
         return "index.php?page=recibos_cliente&cod=".$this->codcliente;
   }
   
   public function get($id)
   {
      $fact = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = ".$this->var2str($id).";");
      if($fact)
         return new factura_proveedor($fact[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod)
   {
      $fact = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codigo = ".$this->var2str($cod).";");
      if($fact)
         return new factura_proveedor($fact[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idfactura) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idfactura = ".$this->var2str($this->idfactura).";");
   }
   
   public function new_idrecibo()
   {
      $newid = $this->db->nextval($this->table_name.'_idfactura_seq');
      if($newid)
         $this->idfactura = intval($newid);
   }
   
   public function test()
   {
		
   }
   
   public function full_test($duplicados = TRUE)
   {
      
   }
   
   public function save()
   {
      
   }
   
   public function delete()
   {
      
   }
   
   private function clean_cache()
   {
      
   }
   
   public function all($offset=0, $limit=FS_ITEM_LIMIT)
   {
      
   }
   
   public function all_from_cliente($codcliente, $offset=0)
   {
      
   }
   
   public function search($query, $offset=0)
   {
      
   }
}
