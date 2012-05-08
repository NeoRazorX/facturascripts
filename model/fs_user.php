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
require_once 'model/agente.php';
require_once 'model/fs_access.php';
require_once 'model/fs_page.php';

class fs_user extends fs_model
{
   public $nick;
   public $password;
   public $log_key;
   public $logged_on;
   public $codagente;
   public $agente;
   public $admin;
   public $last_login;

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
         $this->last_login = $a['last_login'];
      }
      else
      {
         $this->nick = '';
         $this->password = '';
         $this->log_key = '';
         $this->codagente = NULL;
         $this->admin = FALSE;
         $this->last_login = Date('d-m-Y');
      }
      $this->logged_on = FALSE;
      $this->agente = NULL;
   }
   
   public function url()
   {
      return 'index.php?page=admin_user&snick='.$this->nick;
   }

   protected function install()
   {
      $agente = new agente();
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
   
   public function get_agente()
   {
      if( isset($this->agente) )
         return $this->agente;
      else
      {
         $agente = new agente();
         $agente = $agente->get($this->codagente);
         if($agente)
         {
            $this->agente = $agente;
            return $this->agente;
         }
         else
            return FALSE;
      }
   }
   
   public function get_agente_fullname()
   {
      $agente = $this->get_agente();
      if($agente)
         return $agente->get_fullname();
      else
         return '-';
   }
   
   public function get_agente_url()
   {
      $agente = $this->get_agente();
      if($agente)
         return $agente->url();
      else
         return $this->url();
   }
   
   public function get_accesses()
   {
      $access = new fs_access();
      return $access->all_from_nick($this->nick);
   }
   
   public function show_last_login()
   {
      return Date('d-m-Y', strtotime($this->last_login));
   }
   
   public function set_nick($n='')
   {
      $n = trim($n);
      if( preg_match("/^[A-Z0-9_]{3,12}$/i", $n) )
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
      if( preg_match("/^[A-Z0-9_]{1,12}$/i", $p) )
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
            $userlist[] = new fs_user($u);
      }
      return $userlist;
   }
   
   public function new_logkey()
   {
      $this->log_key = sha1( strval(rand()) );
      $this->logged_on = TRUE;
      $this->last_login = Date('d-m-Y');
   }
   
   public function exists()
   {
      if( is_null($this->nick) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE nick = '".$this->nick."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET password = '".$this->password."',
                 log_key = '".$this->log_key."', codagente = ".$this->var2str($this->codagente).",
                 admin = ".$this->var2str($this->admin).", last_login = ".$this->var2str($this->last_login).
                 " WHERE nick = '".$this->nick."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (nick,password,log_key,codagente,admin,last_login) VALUES
                 ('".$this->nick."','".$this->password."','".$this->log_key."',".$this->var2str($this->codagente).
                 ",".$this->var2str($this->admin).",".$this->var2str($this->last_login).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE nick = '".$this->nick."';");
   }
}

?>
