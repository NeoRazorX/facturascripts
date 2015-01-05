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
      $this->show_fs_toolbar = FALSE;
      $this->unstables = isset($_GET['unstable']);
      
      if(FS_DEMO)
      {
         $this->new_error_msg('En el modo demo no se pueden activar/desactivar plugins. Sería muy molesto para los demás visitantes.');
      }
      else if( isset($_GET['enable']) )
      {
         $this->enable_plugin($_GET['enable']);
      }
      else if( isset($_GET['disable']) )
      {
         $this->disable_plugin($_GET['disable']);
      }
      else if( isset($_GET['delete']) )
      {
         if( $this->delTree('plugins/'.$_GET['delete']) )
         {
            $this->new_message('Plugin '.$_GET['delete'].' eliminado correctamente.');
         }
         else
            $this->new_error_msg('Imposible eliminar el plugin '.$_GET['delete']);
      }
      else if( isset($_POST['install']) )
      {
         if( is_uploaded_file($_FILES['fplugin']['tmp_name']) )
         {
            $zip = new ZipArchive();
            if( $zip->open($_FILES['fplugin']['tmp_name']) )
            {
               $zip->extractTo('plugins/');
               $zip->close();
               $this->new_message('Plugin '.$_FILES['fplugin']['name'].' instalado correctamente. Ya puedes activarlo.');
            }
            else
               $this->new_error_msg('Archivo no encontrado.');
         }
      }
   }
   
   public function plugins()
   {
      $plugins = array();
      
      if( !file_exists('tmp/enabled_plugins') )
         mkdir('tmp/enabled_plugins');
      
      foreach( scandir(getcwd().'/plugins') as $f)
      {
         if( is_dir('plugins/'.$f) AND $f != '.' AND $f != '..')
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
      if( !file_exists('tmp/enabled_plugins/'.$name) )
      {
         if( touch('tmp/enabled_plugins/'.$name) )
         {
            $GLOBALS['plugins'][] = $name;
            
            if( file_exists(getcwd().'/plugins/'.$name.'/controller') )
            {
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
               
               $this->new_message('Se han activado automáticamente las siguientes páginas: '.join(', ', $page_list) . '.');
            }
            
            $this->new_message('Plugin <b>'.$name.'</b> activado correctamente.');
            $this->load_menu(TRUE);
            
            /// limpiamos la caché
            $this->cache->clean();
         }
         else
            $this->new_error_msg('Imposible activar el plugin <b>'.$name.'</b>.');
      }
   }
   
   private function disable_plugin($name)
   {
      if( file_exists('tmp/enabled_plugins/'.$name) )
      {
         if( unlink('tmp/enabled_plugins/'.$name) )
         {
            $this->new_message('Plugin <b>'.$name.'</b> desactivado correctamente.');
            
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
            $this->new_error_msg('Imposible desactivar el plugin <b>'.$name.'</b>.');
         
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
                  $this->new_message('Se ha eliminado automáticamente la página '.$p->name);
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
   
   public function delTree($dir)
   {
      $files = array_diff(scandir($dir), array('.','..'));
      foreach ($files as $file)
      {
         (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
      }
      return rmdir($dir);
   }
}
