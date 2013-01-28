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
      if( isset($_POST['modpages']) )
      {
         foreach($this->all() as $p)
         {
            if( !isset($_POST['enabled']) )
            {
               if($p->name == $this->page->name)
                  $this->new_error_msg("No puedes desactivar esta página (".$p->name.")");
               else
               {
                  $pag = $this->page->get($p->name);
                  if($pag)
                     $pag->delete();
               }
            }
            else if( !$p->enabled AND in_array($p->name, $_POST['enabled']) )
            {
               require_once 'controller/'.$p->name.'.php';
               $new_fsc = new $p->name();
               $new_fsc->page->save();
               unset($new_fsc);
            }
            else if( $p->enabled AND !in_array($p->name, $_POST['enabled']) )
            {
               if($p->name == $this->page->name)
                  $this->new_error_msg("No puedes desactivar esta página");
               else
               {
                  $pag = $this->page->get($p->name);
                  if($pag)
                     $pag->delete();
                  else
                     $this->new_error_msg("Error al desactivar la página ".$p->name);
               }
            }
         }
         
         $this->new_message('Datos guardados correctamente.');
      }
      
      $this->load_menu(TRUE);
   }

   public function all()
   {
      $pages = array();
      foreach(scandir('controller') as $f)
      {
         if(is_string($f) AND strlen($f) > 0 AND !is_dir($f))
         {
            $p = new fs_page();
            $p->name = substr($f, 0, -4);
            $p->exists = TRUE;
            $p->show_on_menu = FALSE;
            $pages[] = $p;
         }
      }
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
   
   public function version()
   {
      return parent::version().'-3';
   }
}

?>
