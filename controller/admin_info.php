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

require_once 'base/fs_printer.php';
require_model('fs_var.php');

class admin_info extends fs_controller
{
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Información del sistema', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->show_fs_toolbar = FALSE;
      
      $fsvar = new fs_var();
      $cron_vars = $fsvar->array_get( array('cron_exists' => FALSE, 'cron_lock' => FALSE, 'cron_error' => FALSE) );
      
      if( isset($_GET['fix']) )
      {
         $cron_vars['cron_error'] = FALSE;
         $cron_vars['cron_lock'] = FALSE;
         $fsvar->array_save($cron_vars);
      }
      
      if( !$cron_vars['cron_exists'] )
      {
         $this->new_advice('Nunca se ha ejecutado el cron, te perderás algunas características interesantes de FacturaScripts.');
      }
      else if( $cron_vars['cron_error'] )
      {
         $this->new_error_msg('Parece que ha habido un error con el cron. Haz clic <a href="'.$this->url().'&fix=TRUE">aquí</a> para corregirlo.');
      }
      else if( $cron_vars['cron_lock'] )
      {
         $this->new_advice('Se está ejecutando el cron.');
      }
      
      if( isset($_GET['clean_cache']) )
      {
         /// borramos los archivos php del directorio tmp
         foreach( scandir(getcwd().'/tmp') as $f)
         {
            if( substr($f, -4) == '.php' )
               unlink('tmp/'.$f);
         }
         
         if( $this->cache->clean() )
         {
            $this->new_message("Cache limpiada correctamente.");
         }
      }
      
      if(FS_LCD != '')
      {
         $fpt = new fs_printer(FS_LCD);
         $fpt->add( chr(12).$fpt->center_text('The cake is', 20) );
         $fpt->add( $fpt->center_text('a lie!', 20) );
         $fpt->imprimir();
      }
   }
   
   public function linux()
   {
      return (php_uname('s') == 'Linux');
   }
   
   public function uname()
   {
      return php_uname();
   }
   
   public function php_version()
   {
      return phpversion();
   }
   
   public function cache_version()
   {
      return $this->cache->version();
   }
   
   public function sys_uptime()
   {
      system('uptime');
   }
   
   public function sys_df()
   {
      system('df -h');
   }
   
   public function sys_free()
   {
      system('free -m');
   }
   
   public function fs_db_name()
   {
      return FS_DB_NAME;
   }
   
   public function fs_db_version()
   {
      return $this->db->version();
   }
   
   public function get_locks()
   {
      return $this->db->get_locks();
   }
   
   public function get_db_tables()
   {
      return $this->db->list_tables();
   }
   
   public function get_fs_log()
   {
      $fslog = new fs_log();
      return $fslog->all();
   }
}
