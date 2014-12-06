<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Carlos Garcia Gomez  neorazorx@gmail.com
 * Copyright (C) 2014  Valentín González    valengon@gmail.com
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
require_model('agente.php');
require_model('ejercicio.php');
require_once 'model/fs_access.php';
require_once 'model/fs_page.php';

/**
 * Usuario de facturaScripts. Puede estar asociado a un agente.
 */
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
   public $last_login_time;
   public $last_ip;
   public $last_browser;
   public $fs_page;
   public $codejercicio;
   public $template;

   private $menu;

   public function __construct($a = FALSE)
   {
      parent::__construct('fs_users', 'plugins/plantilla_web/');
      if($a)
      {
         $this->nick = $a['nick'];
         $this->password = $a['password'];
         $this->log_key = $a['log_key'];
         $this->codagente = intval($a['codagente']);

         if($this->codagente < 1)
            $this->codagente = NULL;

         $this->admin = $this->str2bool($a['admin']);
         $this->last_login = Date('d-m-Y', strtotime($a['last_login']));

         if( is_null($a['last_login_time']) )
            $this->last_login_time = '00:00:00';
         else
            $this->last_login_time = $a['last_login_time'];

         $this->last_ip = $a['last_ip'];
         $this->last_browser = $a['last_browser'];
         $this->fs_page = $a['fs_page'];
         $this->codejercicio = $a['codejercicio'];
         
         if( isset($a['template']) )
         {
            $this->template = $a['template'];
         }
         else
            $this->template = 'bootstrap-yeti.css';
      }
      else
      {
         $this->nick = NULL;
         $this->password = NULL;
         $this->log_key = NULL;
         $this->codagente = NULL;
         $this->admin = FALSE;
         $this->last_login = NULL;
         $this->last_login_time = NULL;
         $this->last_ip = NULL;
         $this->last_browser = NULL;
         $this->fs_page = NULL;
         $this->codejercicio = NULL;
         $this->template = 'bootstrap-yeti.css';
      }
      $this->logged_on = FALSE;
      $this->agente = NULL;
   }

   protected function install()
   {
      $this->clean_cache(TRUE);

      /// Esta tabla tiene claves ajenas a agentes, fs_pages y ejercicios
      new agente();
      new fs_page();
      new ejercicio();

      $this->new_error_msg('Se ha creado el usuario <b>admin</b> con la contraseña <b>admin</b>.');
      if( $this->db->select("SELECT * FROM agentes WHERE codagente = '1';") )
      {
         return "INSERT INTO ".$this->table_name." (nick,password,log_key,codagente,admin)
            VALUES ('admin','".sha1('admin')."',NULL,'1',TRUE);";
      }
      else
      {
         return "INSERT INTO ".$this->table_name." (nick,password,log_key,codagente,admin)
            VALUES ('admin','".sha1('admin')."',NULL,NULL,TRUE);";
      }
   }

   public function url()
   {
      if( is_null($this->nick) )
      {
         return 'index.php?page=admin_users';
      }
      else
         return 'index.php?page=admin_user&snick='.$this->nick;
   }

   public function get_agente()
   {
      if( isset($this->agente) )
      {
         return $this->agente;
      }
      else if( is_null($this->codagente) )
      {
         return FALSE;
      }
      else
      {
         $agente = new agente();
         $agente0 = $agente->get($this->codagente);
         if($agente0)
         {
            $this->agente = $agente0;
            return $this->agente;
         }
         else
         {
            $this->codagente = NULL;
            $this->save();
            return FALSE;
         }
      }
   }

   public function get_agente_fullname()
   {
      $agente = $this->get_agente();
      if($agente)
      {
         return $agente->get_fullname();
      }
      else
         return '-';
   }

   public function get_agente_url()
   {
      $agente = $this->get_agente();
      if($agente)
      {
         return $agente->url();
      }
      else
         return '#';
   }

   public function get_menu($reload=FALSE)
   {
      if( !isset($this->menu) OR $reload)
      {
         $this->menu = array();
         $page = new fs_page();

         if( $this->admin )
         {
            $this->menu = $page->all();
         }
         else
         {
            $access = new fs_access();
            $access_list = $access->all_from_nick($this->nick);
            foreach($page->all() as $p)
            {
               foreach($access_list as $a)
               {
                  if($p->name == $a->fs_page)
                  {
                     $this->menu[] = $p;
                     break;
                  }
               }
            }
         }
      }
      return $this->menu;
   }

   public function get_template()
   {
        $templates = array( "bootstrap-cerulean.css" => "Cerulean", "bootstrap-cosmo.css" => "Cosmo", "bootstrap-cyborg.css" => "Cyborg", "bootstrap-darkly.css" => "Darkly", "bootstrap-flatly.css" => "Flatly", "bootstrap-journal.css" => "Journal", "bootstrap-lumen.css" => "Lumen", "bootstrap-paper.css" => "Paper", "bootstrap-readable.css" => "Readable", "bootstrap-sandstone.css" => "SandStone", "bootstrap-simplex.css" => "Simplex", "bootstrap-slate.css" => "Slate", "bootstrap-spacelab.css" => "SpaceLab", "bootstrap-superhero.css" => "SuperHero", "bootstrap-united.css" => "United", "bootstrap-yeti.css" => "Yeti", "bootstrap-gplus.css" => "GPlus Style", "bootstrap-holo.css" => "Holo Style", "bootstrap-flatty.css" => "Flatty Style", "bootstrap-white_flatty.css" => "White Flatty Style", "bootstrap-blackwhite.css" => "BlackWhite Style", "bootstrap-kanda.css" => "Kanda Style", "bootstrap-adminlte.css" => "AdminLTE Style" );
        $template = array();
        foreach( $templates as $key => $value )
        {
            $tabla = array(
            'tempvalor' => $key,
            'template' => $value
            );
            $template[] = $tabla;
        }
        return $template;
   }

   public function have_access_to($page_name, $admin_page=FALSE)
   {
      if( $this->admin )
      {
         $status = TRUE;
      }
      else
      {
         $status = FALSE;
         foreach($this->get_menu() as $m)
         {
            if($m->name == $page_name)
            {
               /// los no administradores no pueden acceder a páginas de administración
               $status = !$admin_page;
               break;
            }
         }
      }

      return $status;
   }

   public function get_accesses()
   {
      $access = new fs_access();
      return $access->all_from_nick($this->nick);
   }

   public function show_last_login()
   {
      return Date('d-m-Y', strtotime($this->last_login)).' '.$this->last_login_time;
   }

   public function set_password($p='')
   {
      $p = strtolower( trim($p) );
      if( strlen($p) > 1 AND strlen($p) <= 12 )
      {
         $this->password = sha1($p);
         return TRUE;
      }
      else
      {
         $this->new_error_msg('La contraseña debe contener entre 1 y 12 caracteres.');
         return FALSE;
      }
   }

   /*
    * Modifica y guarda la fecha de login si tiene una diferencia de más de una hora
    * con la fecha guardada, así se evita guardar en cada consulta
    */
   public function update_login()
   {
      $ltime = strtotime($this->last_login.' '.$this->last_login_time);
      if( time() - $ltime > 3600 )
      {
         $this->last_login = Date('d-m-Y');
         $this->last_login_time = Date('H:i:s');
         $this->save();
      }
   }

   public function new_logkey()
   {
      if( is_null($this->log_key) OR !FS_DEMO )
         $this->log_key = sha1( strval(rand()) );

      $this->logged_on = TRUE;
      $this->last_login = Date('d-m-Y');
      $this->last_login_time = Date('H:i:s');
      $this->last_ip = $_SERVER['REMOTE_ADDR'];

      try {
         $this->last_browser = $_SERVER['HTTP_USER_AGENT'];
      }
      catch (Exception $e) {
         $this->last_browser = $e;
      }
   }

   public function get($n = '')
   {
      $u = $this->db->select("SELECT * FROM ".$this->table_name." WHERE nick = ".$this->var2str($n).";");
      if($u)
      {
         return new fs_user($u[0]);
      }
      else
         return FALSE;
   }

   public function exists()
   {
      if( is_null($this->nick) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE nick = ".$this->var2str($this->nick).";");
   }

   public function test()
   {
      $this->nick = trim($this->nick);

      if( !preg_match("/^[A-Z0-9_]{3,12}$/i", $this->nick) )
      {
         $this->new_error_msg("Nick no válido. Debe tener entre 3 y 12 caracteres,
            valen números o letras, pero no la Ñ ni acentos.");
         return FALSE;
      }
      else
         return TRUE;
   }

   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET password = ".$this->var2str($this->password).",
               log_key = ".$this->var2str($this->log_key).", codagente = ".$this->var2str($this->codagente).",
               admin = ".$this->var2str($this->admin).", last_login = ".$this->var2str($this->last_login).",
               last_ip = ".$this->var2str($this->last_ip).", last_browser = ".$this->var2str($this->last_browser).",
               last_login_time = ".$this->var2str($this->last_login_time).",
               fs_page = ".$this->var2str($this->fs_page).", codejercicio = ".$this->var2str($this->codejercicio).",
               template = ".$this->var2str($this->template)."
               WHERE nick = ".$this->var2str($this->nick).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (nick,password,log_key,codagente,admin,
               last_login,last_login_time,last_ip,last_browser,fs_page,codejercicio,template) VALUES
               (".$this->var2str($this->nick).",".$this->var2str($this->password).",
               ".$this->var2str($this->log_key).",".$this->var2str($this->codagente).",
               ".$this->var2str($this->admin).",".$this->var2str($this->last_login).",
               ".$this->var2str($this->last_login_time).",".$this->var2str($this->last_ip).",
               ".$this->var2str($this->last_browser).",".$this->var2str($this->fs_page).",
               ".$this->var2str($this->codejercicio).",".$this->var2str($this->template).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }

   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE nick = ".$this->var2str($this->nick).";");
   }

   public function clean_cache($full=FALSE)
   {
      $this->cache->delete('m_fs_user_all');

      if($full)
         $this->clean_checked_tables();
   }

   public function all()
   {
      $userlist = $this->cache->get_array('m_fs_user_all');

      if(!$userlist)
      {
         $users = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY nick ASC;");
         if($users)
         {
            foreach($users as $u)
               $userlist[] = new fs_user($u);
         }
         $this->cache->set('m_fs_user_all', $userlist);
      }

      return $userlist;
   }
}
