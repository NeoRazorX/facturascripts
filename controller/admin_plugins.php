<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013  Carlos Garcia Gomez  neorazorx@gmail.com
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

class admin_plugins extends fs_controller
{
   public function __construct()
   {
      parent::__construct('admin_plugins', 'Plugins', 'admin', TRUE, FALSE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('admin_pages');
      
      if(FS_DEMO)
      {
         $this->new_error_msg('En el modo demo no se pueden activar/desactivar plugins.
            Sería muy molesto para los demás visitantes.');
      }
      else if( isset($_GET['enable']) )
      {
         $this->enable_plugin($_GET['enable']);
      }
      else if( isset($_GET['disable']) )
      {
         $this->disable_plugin($_GET['disable']);
      }
   }
   
   public function version()
   {
      return parent::version().'-1';
   }
   
   public function plugins()
   {
      $plugins = array();
      
      if( !file_exists('tmp/enabled_plugins') )
         mkdir('tmp/enabled_plugins');
      
      foreach(scandir('plugins') as $f)
      {
         if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) )
         {
            $plugin = array(
                'name' => $f,
                'enabled' => file_exists('tmp/enabled_plugins/'.$f),
                'description' => 'Sin descripción'
            );
            
            if( file_exists('plugins/'.$f.'/description') )
               $plugin['description'] = file_get_contents('plugins/'.$f.'/description');
            
            $plugins[] = $plugin;
         }
      }
      
      return $plugins;
   }
   
   private function enable_plugin($name)
   {
      if( touch('tmp/enabled_plugins/'.$name) )
      {
         $this->new_message('Módulo <b>'.$name.'</b> activado correctamente.');
         
         /// activamos las páginas del plugin
         $page_list = array();
         foreach(scandir('plugins/'.$name.'/controller') as $f)
         {
            if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) )
            {
               $page_name = substr($f, 0, -4);
               $page_list[] = $page_name;
               
               require_once 'plugins/'.$name.'/controller/'.$f;
               $new_fsc = new $page_name();
               
               if( !$new_fsc->page->save() )
                  $this->new_error_msg("Imposible guardar la página ".$page_name);
               
               unset($new_fsc);
            }
         }
         
         $this->new_message('Se han activado automáticamente las siguientes páginas: '.
                 join(', ', $page_list) . '.');
         $this->load_menu(TRUE);
      }
      else
         $this->new_error_msg('Imposible activar el módulo <b>'.$name.'</b>.');
   }
   
   private function disable_plugin($name)
   {
      if( unlink('tmp/enabled_plugins/'.$name) )
      {
         $this->new_message('Módulo <b>'.$name.'</b> desactivado correctamente.');
      }
      else
         $this->new_error_msg('Imposible desactivar el módulo <b>'.$name.'</b>.');
   }
}

?>