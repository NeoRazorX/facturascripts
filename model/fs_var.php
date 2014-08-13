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
 * Una clase genércia para consultar o almacenar en la base de datos
 * pares clave/valor.
 */
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
   
   /**
    * Devuelve la variable $cod o FALSE en caso de que no se encuentre.
    * @param type $cod
    * @return \fs_var|boolean
    */
   public function get($cod)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE name = ".$this->var2str($cod).";");
      if($data)
      {
         return new fs_var($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->name) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE name = ".$this->var2str($this->name).";");
      }
   }
   
   public function test()
   {
      if( is_null($this->name) )
      {
         return FALSE;
      }
      else if( strlen($this->name) > 1 AND strlen($this->name) < 20  )
      {
         return TRUE;
      }
      else
         return FALSE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $comillas = '';
         if( strtolower(FS_DB_TYPE) == 'mysql' )
            $comillas = '`';
         
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
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE name = ".$this->var2str($this->name).";");
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
   
   /**
    * Rellena un array con los resultados de la base de datos para cada clave,
    * es decir, apara el array('clave1' => false, 'clave2' => false) busca
    * en la tabla las claves clave1 y clave2 y asigna los valores almacenados
    * en la base de datos.
    * 
    * @param type $array
    */
   public function array_get($array)
   {
      foreach($array as $i => $value)
      {
         $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE name = ".$this->var2str($i).";");
         if($data)
         {
            $array[$i] = $data[0]['varchar'];
         }
         else
            $array[$i] = FALSE;
      }
      
      return $array;
   }
   
   /**
    * Guarda en la base de datos los pares clave, valor de un array simple.
    * 
    * @param type $array
    */
   public function array_save($array)
   {
      $done = TRUE;
      
      foreach($array as $i => $value)
      {
         if($value === FALSE)
         {
            $fv0 = $this->get($i);
            if($fv0)
               $fv0->delete();
         }
         else
         {
            $fv0 = new fs_var( array('name' => $i, 'varchar' => $value) );
            
            if( !$fv0->save() )
               $done = FALSE;
         }
      }
      
      return $done;
   }
}
