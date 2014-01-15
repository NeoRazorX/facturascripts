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

class admin_pages extends fs_controller
{
   public $paginas;
   public $demo_warnign_showed;
   
   public function __construct()
   {
      parent::__construct('admin_pages', 'Páginas', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->demo_warnign_showed = FALSE;
      
      $this->buttons[] = new fs_button('b_plugins', 'Plugins', 'index.php?page=admin_plugins');
      
      if( isset($_POST['modpages']) )
      {
         foreach($this->all_pages() as $p)
         {
            if( !$p->exists ) /// la página está en la base de datos pero ya no existe el controlador
            {
               if( $p->delete() )
               {
                  $this->new_message('Se ha eliminado automáticamnte la página '.$p->name.
                          ' ya que no tiene un controlador asociado en la carpeta controller.');
               }
            }
            else if( !isset($_POST['enabled']) ) /// ninguna página marcada
            {
               $this->disable_page($p);
            }
            else if( !$p->enabled AND in_array($p->name, $_POST['enabled']) ) /// página no activa marcada para activar
            {
               $this->enable_page($p);
            }
            else if( $p->enabled AND !in_array($p->name, $_POST['enabled']) ) /// págine activa no marcada (desactivar)
            {
               $this->disable_page($p);
            }
         }
         
         $this->new_message('Datos guardados correctamente.');
         $this->new_message('Ahora es el momento de <a href="index.php?page=admin_empresa">
            introducir los datos de tu empresa</a>, si todavía no lo has hecho.');
      }
      else
      {
         $this->check_php();
         $this->new_advice('Desde aquí se activan y desactivan todas las páginas de FacturaScripts.'
                 . ' <a target="_blank" href="http://www.facturascripts.com/community/item.php?id=5203ccc1b38d447c66000001">¿Necesitas ayuda?</a>');
      }
      
      $this->paginas = $this->all_pages();
      $this->load_menu(TRUE);
   }
   
   private function all_pages()
   {
      $pages = array();
      $page_names = array();
      
      /// añadimos las páginas de los plugins
      foreach($this->plugins() as $plugin)
      {
         foreach( scandir('plugins/'.$plugin.'/controller') as $f )
         {
            if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) )
            {
               $p = new fs_page();
               $p->name = substr($f, 0, -4);
               $p->exists = TRUE;
               $p->show_on_menu = FALSE;
               
               if( !in_array($p->name, $page_names) )
               {
                  $pages[] = $p;
                  $page_names[] = $p->name;
               }
            }
         }
      }
      
      /// añadimos las páginas que están en el directorio controller
      foreach(scandir('controller') as $f)
      {
         if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) )
         {
            $p = new fs_page();
            $p->name = substr($f, 0, -4);
            $p->exists = TRUE;
            $p->show_on_menu = FALSE;
            
            if( !in_array($p->name, $page_names) )
            {
               $pages[] = $p;
               $page_names[] = $p->name;
            }
         }
      }
      
      /// completamos los datos de las páginas con los datos de la base de datos
      foreach($this->page->all() as $p)
      {
         $encontrada = FALSE;
         foreach($pages as $i => $value)
         {
            if($p->name == $value->name)
            {
               $pages[$i] = $p;
               $pages[$i]->enabled = TRUE;
               $pages[$i]->exists = TRUE;
               $encontrada = TRUE;
               break;
            }
         }
         if( !$encontrada )
         {
            $p->enabled = TRUE;
            $pages[] = $p;
         }
      }
      
      return $pages;
   }
   
   private function plugins()
   {
      $plugins = array();
      
      if( file_exists('tmp/enabled_plugins') )
      {
         foreach(scandir('tmp/enabled_plugins') as $f)
         {
            if( is_string($f) AND strlen($f) > 0 AND !is_dir($f) )
            {
               if( file_exists('plugins/'.$f) )
                  $plugins[] = $f;
               else
                  unlink('tmp/enabled_plugins/'.$f);
            }
         }
      }
      
      return $plugins;
   }
   
   private function check_php()
   {
      if( floatval( substr(phpversion(), 0, 3) ) < 5.3 )
         $this->new_error_msg('FacturaScripts necesita de php 5.3 o superior,'
                 . ' y tú tienes php '.phpversion());
   }
   
   private function enable_page($page)
   {
      /// primero buscamos en los plugins
      $found = FALSE;
      foreach($this->plugins() as $plugin)
      {
         if( file_exists('plugins/'.$plugin.'/controller/'.$page->name.'.php') )
         {
            require_once 'plugins/'.$plugin.'/controller/'.$page->name.'.php';
            $new_fsc = new $page->name();
            $found = TRUE;
            
            if( !$new_fsc->page->save() )
               $this->new_error_msg("Imposible guardar la página ".$page->name);
            
            unset($new_fsc);
            break;
         }
      }
      
      if( !$found )
      {
         require_once 'controller/'.$page->name.'.php';
         $new_fsc = new $page->name(); /// cargamos el controlador asociado
         
         if( !$new_fsc->page->save() )
            $this->new_error_msg("Imposible guardar la página ".$page->name);
         
         unset($new_fsc);
      }
   }
   
   private function disable_page($page)
   {
      if(FS_DEMO)
      {
         if( !$this->demo_warnign_showed )
         {
            $this->new_error_msg('En el modo <b>demo</b> no se pueden desactivar páginas.');
            $this->demo_warnign_showed = TRUE;
         }
      }
      else if($page->name == $this->page->name)
      {
         $this->new_error_msg("No puedes desactivar esta página (".$page->name.").");
      }
      else if( !$page->delete() )
      {
         $this->new_error_msg('Imposible eliminar la página '.$page->name.'.');
      }
   }
}

?>