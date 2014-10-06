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
 * Permite relacionar cuentas especiales (VENTAS, por ejemplo)
 * con la cuenta o subcuenta real.
 */
class cuenta_especial extends fs_model
{
   public $idcuentaesp; /// pkey
   public $descripcion;
   
   public function __construct($c = FALSE)
   {
      parent::__construct('co_cuentasesp');
      if($c)
      {
         $this->idcuentaesp = $c['idcuentaesp'];
         $this->descripcion = $c['descripcion'];
      }
      else
      {
         $this->idcuentaesp = NULL;
         $this->descripcion = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get($id)
   {
      $cuentae = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idcuentaesp = ".$this->var2str($id).";");
      if($cuentae)
         return new cuenta_especial($cuentae[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idcuentaesp) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
            " WHERE idcuentaesp = ".$this->var2str($this->idcuentaesp).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion)."
            WHERE idcuentaesp = ".$this->var2str($this->idcuentaesp).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (idcuentaesp,descripcion)
            VALUES (".$this->var2str($this->idcuentaesp).",".$this->var2str($this->descripcion).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idcuentaesp = ".$this->var2str($this->idcuentaesp).";");
   }
   
   public function all()
   {
      $culist = array();
      $cuentas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY idcuentaesp ASC;");
      if($cuentas)
      {
         foreach($cuentas as $c)
            $culist[] = new cuenta_especial($c);
      }
      return $culist;
   }
}
