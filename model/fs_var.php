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

class fs_var extends fs_model
{
   public $name; /// pkey
   public $varchar;
   
   public function __construct($f=FALSE)
   {
      parent::__construct('fs_vars');
      if($f)
      {
         $this->name = $f['name'];
         $this->varchar = $f['varchar'];
      }
      else
      {
         $this->name = NULL;
         $this->varchar = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( isset($this->name) )
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE name = ".$this->var2str($this->name).";");
      else
         return FALSE;
   }
   
   public function test()
   {
      if( is_null($this->name) )
         return FALSE;
      else if( strlen($this->name) > 1 AND strlen($this->name) < 20  )
         return TRUE;
      else
         return FALSE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( strtolower(FS_DB_TYPE) == 'mysql' )
            $comillas = '`';
         else
            $comillas = '';
         
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET ".$comillas."varchar".$comillas." = ".$this->var2str($this->varchar).
                    " WHERE name = ".$this->var2str($this->name).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (name,".$comillas."varchar".$comillas.") VALUES
               (".$this->var2str($this->name).",".$this->var2str($this->varchar).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name.
                 " WHERE name = ".$this->var2str($this->name).";");
   }
   
   public function all()
   {
      $vlist = array();
      $vars = $this->db->select("SELECT * FROM ".$this->table_name.";");
      if($vars)
      {
         foreach($vars as $v)
            $vlist[] = new fs_var($v);
      }
      return $vlist;
   }
   
   public function multi_get($names)
   {
      $vlist = array();
      
      $insql = '';
      foreach($names as $n)
      {
         if($insql == '')
            $insql = $this->var2str($n);
         else
            $insql .= ','.$this->var2str($n);
      }
      
      $vars = $this->db->select("SELECT * FROM ".$this->table_name." WHERE name IN (".$insql.");");
      if($vars)
      {
         foreach($vars as $v)
            $vlist[] = new fs_var($v);
      }
      return $vlist;
   }
   
   public function multi_save($data)
   {
      $done = TRUE;
      
      foreach($data as $i => $value)
      {
         $var = new fs_var();
         $var->name = $i;
         $var->varchar = $value;
         if( !$var->save() )
         {
            $this->new_error_msg("Error al guardar '".$var->name."'");
            $done = FALSE;
         }
      }
      
      return $done;
   }
}

?>