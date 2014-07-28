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

class admin_plugins extends fs_controller
{
   public $unstables;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Plugins', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->ppage = $this->page->get('admin_pages');
      $this->unstables = isset($_GET['unstable']);
      
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
   
   public function plugins()
   {
      $plugins = array();
      
      if( !file_exists('tmp/enabled_plugins') )
         mkdir('tmp/enabled_plugins');
      
      foreach( scandir(getcwd().'/plugins') as $f)
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
            
            if( $this->unstables == file_exists('plugins/'.$f.'/unstable') )
               $plugins[] = $plugin;
         }
      }
      
      return $plugins;
   }
   
   private function enable_plugin($name)
   {
      if( touch('tmp/enabled_plugins/'.$name) )
      {
         $GLOBALS['plugins'][] = $name;
         
         /// activamos las páginas del plugin
         $page_list = array();
         foreach( scandir(getcwd().'/plugins/'.$name.'/controller') as $f)
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
         
         $this->new_message('Módulo <b>'.$name.'</b> activado correctamente.');
         $this->new_message('Se han activado automáticamente las siguientes páginas: '.join(', ', $page_list) . '.');
         $this->load_menu(TRUE);
         
         /// limpiamos la caché
         $this->cache->clean();
      }
      else
         $this->new_error_msg('Imposible activar el módulo <b>'.$name.'</b>.');
   }
   
   private function disable_plugin($name)
   {
      if( unlink('tmp/enabled_plugins/'.$name) )
      {
         $this->new_message('Módulo <b>'.$name.'</b> desactivado correctamente.');
         
         foreach($GLOBALS['plugins'] as $i => $value)
         {
            if($value == $name)
            {
               unset($GLOBALS['plugins'][$i]);
               break;
            }
         }
      }
      else
         $this->new_error_msg('Imposible desactivar el módulo <b>'.$name.'</b>.');
      
      /*
       * Desactivamos las páginas que ya no existen
       */
      foreach($this->page->all() as $p)
      {
         $encontrada = FALSE;
         
         if( file_exists(getcwd().'/controller/'.$p->name.'.php') )
         {
            $encontrada = TRUE;
         }
         else
         {
            foreach($GLOBALS['plugins'] as $plugin)
            {
               if( file_exists(getcwd().'/plugins/'.$plugin.'/controller/'.$p->name.'.php') AND $name != $plugin)
               {
                  $encontrada = TRUE;
                  break;
               }
            }
         }
         
         if( !$encontrada )
         {
            if( $p->delete() )
            {
               $this->new_message('Se ha eliminado automáticamnte la página '.$p->name);
            }
         }
      }
      
      /// borramos los archivos temporales del motor de plantillas
      foreach( scandir(getcwd().'/tmp') as $f)
      {
         if( substr($f, -4) == '.php' )
            unlink('tmp/'.$f);
      }
      
      /// limpiamos la caché
      $this->cache->clean();
   }
}
