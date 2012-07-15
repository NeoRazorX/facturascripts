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

class admin_pages extends fs_controller
{
   public function __construct()
   {
      parent::__construct('admin_pages', 'Páginas', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $this->buttons[] = new fs_button('b_activar_todos', 'Activar todas', $this->url()."&enable_all=TRUE");
      
      if( isset($_GET['enable_all']) )
      {
         foreach($this->all() as $p)
         {
            if( !$p->enabled )
            {
               require_once 'controller/'.$p->name.'.php';
               $new_fsc = new $p->name();
               $new_fsc->page->save();
               unset($new_fsc);
            }
         }
      }
      else if( isset($_GET['enable']) )
      {
         if( file_exists('controller/'.$_GET['enable'].'.php') )
         {
            if($_GET['enable'] != $this->page->name)
            {
               require_once 'controller/'.$_GET['enable'].'.php';
               $new_fsc = new $_GET['enable']();
               $new_fsc->page->save();
               unset($new_fsc);
            }
         }
         else
            $this->new_error_msg("La página no existe");
      }
      else if( isset($_GET['disable']) )
      {
         if($_GET['disable'] == $this->page->name)
            $this->new_error_msg("No puedes desactivar esta página");
         else
         {
            $p = new fs_page( array('name'=>$_GET['disable'],
                                    'title'=>'',
                                    'folder'=>'',
                                    'show_on_menu'=>TRUE) );
            $p->delete();
            $this->new_error_msg($p->error_msg);
         }
      }
      $this->load_menu();
   }

   public function all()
   {
      $pages = array();
      foreach($this->page->all() as $p)
      {
         if( !in_array($p, $pages) )
         {
            $p->enabled = TRUE;
            $pages[] = $p;
         }
      }
      foreach(scandir('controller') as $f)
      {
         if(!is_dir($f) AND $f != '')
         {
            $found = FALSE;
            foreach($pages as $p)
            {
               if($p->name == substr($f, 0, -4))
               {
                  $p->exists = TRUE;
                  $found = TRUE;
                  break;
               }
            }
            if( !$found )
            {
               $p = new fs_page();
               $p->name = substr($f, 0, -4);
               $p->exists = TRUE;
               $p->show_on_menu = FALSE;
               $pages[] = $p;
            }
         }
      }
      return $pages;
   }
}

?>
