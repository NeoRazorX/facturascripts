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

class fs_user extends fs_model
{
   public $nick;
   public $password;
   public $log_key;
   public $logged_on;
   public $codagente;
   public $admin;

   public function __construct($a = FALSE)
   {
      parent::__construct('fs_users');
      if($a)
      {
         $this->nick = $a['nick'];
         $this->password = $a['password'];
         $this->log_key = $a['log_key'];
         $this->codagente = intval($a['codagente']);
         if($this->codagente < 1)
            $this->codagente = NULL;
         if($a['admin'] == 't')
            $this->admin = TRUE;
         else
            $this->admin = FALSE;
      }
      else
      {
         $this->nick = '';
         $this->password = '';
         $this->log_key = '';
         $this->codagente = NULL;
         $this->admin = FALSE;
      }
      $this->logged_on = FALSE;
   }
   
   protected function install()
   {
      return "INSERT INTO ".$this->table_name." (nick,password,log_key,codagente,admin) VALUES ('admin','".sha1('admin')."','',NULL,TRUE);";
   }

   public function get($n = '')
   {
      $u = $this->db->select("SELECT * FROM ".$this->table_name." WHERE nick='".$n."';");
      if($u)
         return new fs_user($u[0]);
      else
         return FALSE;
   }
   
   public function set_nick($n='')
   {
      $n = trim($n);
      if( eregi("^[A-Z0-9_]{3,12}$", $n) )
      {
         $this->nick = $n;
         return TRUE;
      }
      else
      {
         $this->new_error_msg('El nick debe tener entre 3 y 12 caracteres alfanuméricos');
         return FALSE;
      }
   }
   
   public function set_password($p='')
   {
      $p = trim($p);
      if( eregi("^[A-Z0-9_]{1,12}$", $p) )
      {
         $this->password = sha1($p);
         return TRUE;
      }
      else
      {
         $this->new_error_msg('La contraseña debe contener entre 1 y 12 caracteres alfanuméricos');
         return FALSE;
      }
   }

   public function all()
   {
      $userlist = array();
      $users = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY nick ASC;");
      if($users)
      {
         foreach($users as $u)
         {
            $fu = new fs_user($u);
            $userlist[] = $fu;
         }
      }
      return $userlist;
   }
   
   public function new_logkey()
   {
      $this->log_key = sha1( strval(rand()) );
      $this->logged_on = TRUE;
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE nick = '".$this->nick."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET password = '".$this->password."',
                 log_key = '".$this->log_key."', codagente = ".$this->null2str($this->codagente).",
                 admin = ".$this->bool2str($this->admin)." WHERE nick = '".$this->nick."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (nick,password,log_key,codagente,admin) VALUES
                 ('".$this->nick."','".$this->password."','".$this->log_key."',".$this->null2str($this->codagente).
                 ",".$this->bool2str($this->admin).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE nick = '".$this->nick."';");
   }
}

?>
