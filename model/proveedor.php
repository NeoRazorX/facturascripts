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

class proveedor extends fs_model
{
   public $codproveedor;
   public $nombre;
   public $nombrecomercial;
   public $cifnif;
   public $telefono1;
   public $telefono2;
   public $fax;
   public $email;
   public $web;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('proveedores');
      if($p)
      {
         $this->codproveedor = $p['codproveedor'];
         $this->nombre = $p['nombre'];
         $this->nombrecomercial = $p['nombrecomercial'];
         $this->cifnif = $p['cifnif'];
         $this->telefono1 = $p['telefono1'];
         $this->telefono2 = $p['telefono2'];
         $this->fax = $p['fax'];
         $this->email = $p['email'];
         $this->web = $p['web'];
      }
      else
      {
         $this->codproveedor = '';
         $this->nombre = '';
         $this->nombrecomercial = '';
         $this->cifnif = '';
         $this->telefono1 = '';
         $this->telefono2 = '';
         $this->fax = '';
         $this->email = '';
         $this->web = '';
      }
   }
   
   public function url()
   {
      return "index.php?page=general_proveedor&cod=".$this->codproveedor;
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codproveedor = '".$this->codproveedor."';");
   }
   
   public function save()
   {
      
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codproveedor = '".$this->codproveedor."';");
   }
   
   public function get($cod)
   {
      $prov = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codproveedor = '".$cod."';");
      if($prov)
         return new proveedor($prov[0]);
      else
         return FALSE;
   }
   
   public function all($offset=0)
   {
      $provelist = array();
      $proveedores = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY codproveedor ASC",
                                             FS_ITEM_LIMIT, $offset);
      if($proveedores)
      {
         foreach($proveedores as $p)
         {
            $provelist[] = new proveedor($p);
         }
      }
      return $provelist;
   }
}

?>
