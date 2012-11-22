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

require_once 'base/fs_db.php';
require_once 'base/fs_button.php';
require_once 'model/fs_user.php';
require_once 'model/fs_page.php';
require_once 'model/fs_access.php';
require_once 'model/empresa.php';

class fs_controller
{
   protected $db;
   private $uptime;
   private $errors;
   private $messages;
   public $user;
   public $page;
   public $ppage;
   private $admin_page;
   public $default_page;
   protected $menu;
   public $template;
   public $css_file;
   public $custom_search;
   public $query;
   public $buttons;
   public $empresa;
   
   public function __construct($name='', $title='home', $folder='', $admin=FALSE, $shmenu=TRUE)
   {
      $tiempo = explode(' ', microtime());
      $this->uptime = $tiempo[1] + $tiempo[0];
      $this->admin_page = $admin;
      $this->errors = array();
      $this->messages = array();
      $this->db = new fs_db();
      $this->set_css_file();
      
      if( $this->db->connect() )
      {
         $this->user = new fs_user();
         $this->page = new fs_page( array('name'=>$name, 'title'=>$title, 'folder'=>$folder,
             'version'=>$this->version(), 'show_on_menu'=>$shmenu) );
         $this->ppage = FALSE;
         $this->empresa = new empresa();
         
         $this->template = 'index';
         if( isset($_GET['logout']) )
         {
            $this->template = 'login';
            $this->log_out();
         }
         else if( !$this->log_in() )
            $this->template = 'login';
         else if( $this->user->have_access_to($this->page->name, $this->admin_page) )
         {
            if($name == '')
            {
               $this->new_error_msg('¡Página no encontrada!');
               $this->prevent_default_page();
            }
            else
            {
               /// ¿Quieres que sea tu página de inicio? ¿O ya lo es?
               if( isset($_GET['default_page']) )
                  $this->set_default_page();
               else if( !isset($_COOKIE['default_page']) )
                  $this->default_page = FALSE;
               else if($_COOKIE['default_page'] == $this->page->name)
                  $this->default_page = TRUE;
               
               $this->buttons = array();
               
               $this->custom_search = FALSE;
               if( isset($_POST['query']) )
                  $this->query = $_POST['query'];
               else if( isset($_GET['query']) )
                  $this->query = $_GET['query'];
               else
                  $this->query = '';
               
               $this->template = $name;
               $this->process();
            }
         }
         else
         {
            $this->new_error_msg("Acceso denegado.");
            $this->prevent_default_page();
         }
      }
      else
      {
         $this->template = 'no_db';
         $this->new_error_msg('¡Imposible conectar con la base de datos!');
      }
   }
   
   public function close()
   {
      $this->db->close();
   }
   
   public function new_error_msg($msg=FALSE)
   {
      if( $msg )
         $this->errors[] = $msg;
   }
   
   public function get_errors()
   {
      if( isset($this->empresa) )
         return array_merge($this->errors, $this->empresa->get_errors());
      else
         return $this->errors;
   }
   
   public function new_message($msg=FALSE)
   {
      if( $msg )
         $this->messages[] = $msg;
   }
   
   public function get_messages()
   {
      return $this->messages;
   }
   
   public function url()
   {
      return $this->page->url();
   }
   
   private function log_in()
   {
      if( isset($_POST['user']) AND isset($_POST['password']) )
      {
         $user = $this->user->get($_POST['user']);
         if($user)
         {
            if($user->password == sha1($_POST['password']) OR (FS_DEMO AND $_POST['password'] == 'demo'))
            {
               $user->new_logkey();
               if( $user->save() )
               {
                  setcookie('user', $user->nick, time()+FS_COOKIES_EXPIRE);
                  setcookie('logkey', $user->log_key, time()+FS_COOKIES_EXPIRE);
                  $this->user = $user;
                  $this->load_menu();
               }
            }
            else
               $this->new_error_msg('Contraseña incorrecta!');
         }
         else
         {
            $this->new_error_msg('El usuario no existe!');
            $this->user->clean_cache();
         }
      }
      else if( isset($_COOKIE['user']) AND isset($_COOKIE['logkey']) )
      {
         $user = $this->user->get($_COOKIE['user']);
         if($user)
         {
            if($user->log_key == $_COOKIE['logkey'])
            {
               $user->logged_on = TRUE;
               $this->user = $user;
               $this->load_menu();
            }
            else
            {
               $this->new_message('¡Cookie no válida!');
               $this->log_out();
            }
         }
         else
         {
            $this->new_message('¡El usuario no existe!');
            $this->log_out();
            $this->user->clean_cache();
         }
      }
      return $this->user->logged_on;
   }
   
   private function log_out()
   {
      setcookie('logkey', '', time()-FS_COOKIES_EXPIRE);
   }
   
   public function duration()
   {
      $tiempo = explode(" ", microtime());
      return (number_format($tiempo[1] + $tiempo[0] - $this->uptime, 3) . ' s');
   }
   
   public function selects()
   {
      return $this->db->get_selects();
   }
   
   public function transactions()
   {
      return $this->db->get_transactions();
   }
   
   public function get_db_history()
   {
      return $this->db->get_history();
   }
   
   protected function load_menu($reload=FALSE)
   {
      $this->menu = $this->user->get_menu($reload);
      
      /// actualizamos los datos de la página
      foreach($this->menu as $m)
      {
         if($m->name == $this->page->name AND $m != $this->page)
         {
            $this->page->save();
            break;
         }
      }
   }
   
   public function folders()
   {
      $folders = array();
      foreach($this->menu as $m)
      {
         if($m->folder!='' AND $m->show_on_menu AND !in_array($m->folder, $folders) )
            $folders[] = $m->folder;
      }
      return $folders;
   }
   
   public function pages($f='')
   {
      $pages = array();
      foreach($this->menu as $p)
      {
         if($f == $p->folder AND $p->show_on_menu AND !in_array($p, $pages) )
            $pages[] = $p;
      }
      return $pages;
   }
   
   protected function process()
   {
      
   }
   
   public function version()
   {
      return '0.9.12';
   }
   
   public function select_default_page()
   {
      if( $this->user->logged_on )
      {
         $url = FALSE;
         
         if( isset($_COOKIE['default_page']) )
         {
            $page = $this->page->get($_COOKIE['default_page']);
            if($page)
               $url = 'index.php?page=' . $_COOKIE['default_page'];
            else
               setcookie('default_page', '', time()-FS_COOKIES_EXPIRE);
         }
         
         if( !$url )
         {
            $url = 'index.php?page=admin_pages';
            foreach($this->menu as $p)
            {
               if($p->show_on_menu)
               {
                  $url = $p->url() . '&show_dpa=TRUE';
                  break;
               }
            }
         }
         
         Header('location: '.$url);
      }
   }
   
   private function set_default_page()
   {
      if($_GET['default_page'] == 'TRUE')
      {
         setcookie('default_page', $this->page->name, time()+FS_COOKIES_EXPIRE);
         $this->default_page = TRUE;
      }
      else
      {
         setcookie('default_page', '', time()-FS_COOKIES_EXPIRE);
         $this->default_page = FALSE;
      }
   }
   
   private function prevent_default_page()
   {
      if( isset($_COOKIE['default_page']) )
      {
         if($_COOKIE['default_page'] == $this->page->name)
            setcookie('default_page', '', time()-FS_COOKIES_EXPIRE);
      }
   }
   
   public function show_default_page_advice()
   {
      return isset($_GET['show_dpa']);
   }
   
   private function set_css_file()
   {
      if( isset($_GET['css_file']) )
      {
         if( file_exists('view/css/'.$_GET['css_file']) )
         {
            $this->css_file = $_GET['css_file'];
            setcookie('css_file', $_GET['css_file'], time()+FS_COOKIES_EXPIRE);
         }
         else
         {
            $this->new_error_msg("Archivo CSS no encontrado.");
            $this->css_file = 'base.css';
         }
      }
      else if( isset($_COOKIE['css_file']) )
      {
         if( file_exists('view/css/'.$_COOKIE['css_file']) )
            $this->css_file = $_COOKIE['css_file'];
         else
         {
            $this->new_error_msg("Archivo CSS no encontrado.");
            $this->css_file = 'base.css';
            setcookie('css_file', $this->css_file, time()+FS_COOKIES_EXPIRE);
         }
      }
      else
         $this->css_file = 'base.css';
   }
   
   public function is_admin_page()
   {
      return $this->admin_page;
   }
}

?>