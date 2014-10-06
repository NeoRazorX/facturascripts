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
 * Define que un usuario tiene acceso a una página concreta.
 */
class fs_access extends fs_model
{
   public $fs_user;
   public $fs_page;
   
   public function __construct($a=FALSE)
   {
      parent::__construct('fs_access');
      if($a)
      {
         $this->fs_user = $a['fs_user'];
         $this->fs_page = $a['fs_page'];
      }
      else
      {
         $this->fs_user = NULL;
         $this->fs_page = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->fs_page) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE fs_user = ".$this->var2str($this->fs_user).
                 " AND fs_page = ".$this->var2str($this->fs_page).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         return TRUE;
      }
      else
      {
         return $this->db->exec("INSERT INTO ".$this->table_name." (fs_user,fs_page) VALUES
            (".$this->var2str($this->fs_user).",".$this->var2str($this->fs_page).");");
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE fs_user = ".$this->var2str($this->fs_user).
              " AND fs_page = ".$this->var2str($this->fs_page).";");
   }
   
   public function all_from_nick($n='')
   {
      $accesslist = array();
      $access = $this->db->select("SELECT * FROM ".$this->table_name." WHERE fs_user = ".$this->var2str($n).";");
      if($access)
      {
         foreach($access as $a)
            $accesslist[] = new fs_access($a);
      }
      return $accesslist;
   }
}
