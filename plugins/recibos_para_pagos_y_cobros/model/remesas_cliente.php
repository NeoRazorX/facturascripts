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
 * Remesas de clientes.
 */
class remesas_cliente extends fs_model
{
   public $remesa;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('remesas', 'plugins/recibos_para_pagos_y_cobros/');
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->idremesa) )
         return 'index.php?page=remesas_clientes';
      else
         return 'index.php?page=remesas_cliente&id='.$this->idremesa;
   }
   
   public function cliente_url()
   {
      if( is_null($this->codcliente) )
         return "index.php?page=remesas_clientes";
      else
         return "index.php?page=remesas_cliente&cliente=".$this->codcliente;
   }
   
   public function get($id)
   {
      $remesa = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idremesa = ".$this->var2str($id).";");
      if($remesa)
         return new remesa_cliente($remesa[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idremesa) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE idremesa = ".$this->var2str($this->idremesa).";");
   }
   
   public function new_idremesa()
   {
      $newid = $this->db->nextval($this->table_name.'_idremesa_seq');
      if($newid)
         $this->idremesa = intval($newid);
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
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET 
            codcuenta = ".$this->var2str($this->codcuenta).", 
            coddivisa = ".$this->var2str($this->coddivisa).", 
            codsubcuenta = ".$this->var2str($this->codsubcuenta).", 
            estado = ".$this->var2str($this->estado).", 
            fecha = ".$this->var2str($this->fecha).", 
            idsubcuenta = ".$this->var2str($this->idsubcuenta).", 
            nogenerarasiento = ".$this->var2str($this->nogenerarasiento).",
            total = ".$this->var2str($this->total)."
            WHERE idremesa = ".$this->var2str($this->idremesa).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codcuenta, coddivisa, codsubuenta, 
         estado, fecha, idremesa, idsubcuenta, nogenerarasiento, total) VALUES (
            ".$this->var2str($this->codcuenta).",".$this->var2str($this->coddivisa).",
            ".$this->var2str($this->codsubuenta).",".$this->var2str($this->estado).",
            ".$this->var2str($this->fecha).",".$this->var2str($this->idremesa).",
            ".$this->var2str($this->idsubcuenta).",".$this->var2str($this->nogenerarasiento).",
            ".$this->var2str($this->total).");";
         
         if( $this->db->exec($sql) )
         {
            $this->remesa = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
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
      $remesaslist = array();
      
      $sql = "SELECT * FROM ".$this->table_name." ORDER BY codigo DESC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $remesaslist[] = new remesas_cliente($d);
      }
      
      return $remesaslist;
   }
   
   public function all_from_cliente($codcliente, $offset=0)
   {
      /* Hay que filtrar en reciboscli y remesas para obtener las remesas 
       * en las que esta el cliente */
   }
   
   public function search($query, $offset=0)
   {
      /* Todavía no implementado */
   }
}
