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
 * Una cuenta bancaria de un cliente.
 */
class cuenta_banco extends fs_model
{
   public $codcuenta; /// pkey
   public $descripcion;
   public $iban;
   
   public function __construct($c = FALSE)
   {
      parent::__construct('cuentasbanco');
      
      if($c)
      {
         $this->codcuenta = $c['codcuenta'];
         $this->descripcion = $c['descripcion'];
         $this->iban = $c['iban'];
      }
      else
      {
         $this->codcuenta = NULL;
         $this->descripcion = NULL;
         $this->iban = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get($cod)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codcuenta = ".$this->var2str($cod).";");
      if($data)
         return new cuenta_banco($data[0]);
      else
         return FALSE;
   }
   
   private function get_new_codigo()
   {
      $sql = "SELECT MAX(".$this->db->sql_to_int('codcuenta').") as cod FROM ".$this->table_name.";";
      $cod = $this->db->select($sql);
      if($cod)
         return 1 + intval($cod[0]['cod']);
      else
         return 1;
   }
   
   public function exists()
   {
      if( is_null($this->codcuenta) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codcuenta = ".$this->var2str($this->codcuenta).";");
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion).", "
               . "iban = ".$this->var2str($this->iban)." WHERE codcuenta = ".$this->var2str($this->codcuenta)
               ." ;";
         }
         else
         {
            $this->codcuenta = $this->get_new_codigo();
            
            $sql = "INSERT INTO ".$this->table_name." (codcuenta,descripcion,iban) VALUES "
               . "(".$this->var2str($this->codcuenta).","
               . "".$this->var2str($this->descripcion).",".$this->var2str($this->iban).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codcuenta = ".$this->var2str($this->codcuenta).";");
   }
   
   public function all_from_empresa()
   {
      $clist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY descripcion ASC;");
      if($data)
      {
         foreach($data as $d)
            $clist[] = new cuenta_banco($d);
      }
      
      return $clist;
   }
}
