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

class new_fs_controller extends fs_controller
{
   public function __construct()
   {
      parent::__construct('admin_pages', 'Páginas', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      if( isset($_GET['enable']) )
      {
         $folder = explode('_', $_GET['enable']);
         $p = new fs_page( array('name'=>$_GET['enable'],
                                 'title'=>$_GET['enable'],
                                 'folder'=>$folder[0],
                                 'show_on_menu'=>TRUE) );
         $p->save();
         $this->new_error_msg($p->error_msg);
         $this->load_menu();
      }
      else if( isset($_GET['disable']) )
      {
         $p = new fs_page( array('name'=>$_GET['disable'],
                                 'title'=>'',
                                 'folder'=>'',
                                 'show_on_menu'=>TRUE) );
         $p->delete();
         $this->new_error_msg($p->error_msg);
         $this->load_menu();
      }
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
         if( !is_dir($f) )
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
