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

class admin_pages extends fs_controller
{
   public $paginas;
   
   public function __construct()
   {
      parent::__construct('admin_pages', 'Páginas', 'admin', TRUE, TRUE);
   }
   
   protected function process()
   {
      $show_error_demo = TRUE;
      
      if( isset($_POST['modpages']) )
      {
         foreach($this->all_pages() as $p)
         {
            if( !$p->exists ) /// la está en la base de datos pero ya no existe el controlador
            {
               if( $p->delete() )
                  $this->new_message('Se ha eliminado automáticamnte la página '.$p->name.
                          ' ya que no tiene un controlador asociado en la carpeta controller.');
            }
            else if( !isset($_POST['enabled']) ) /// ninguna página marcada
            {
               if($p->name == $this->page->name)
                  $this->new_error_msg("No puedes desactivar esta página (".$p->name.").");
               else if( FS_DEMO )
               {
                  if($show_error_demo)
                  {
                     $this->new_error_msg('En el modo <b>demo</b> no se pueden desactivar páginas.');
                     $show_error_demo = FALSE;
                  }
               }
               else if( !$p->delete() )
                  $this->new_error_msg('Imposible eliminar la página '.$p->name.'.');
            }
            else if( !$p->enabled AND in_array($p->name, $_POST['enabled']) ) /// página no activa marcada para activar
            {
               require_once 'controller/'.$p->name.'.php';
               $new_fsc = new $p->name(); /// cargamos el controlador asociado
               $new_fsc->page->save();
               unset($new_fsc);
            }
            else if( $p->enabled AND !in_array($p->name, $_POST['enabled']) ) /// págine activa no marcada (desactivar)
            {
               if($p->name == $this->page->name)
                  $this->new_error_msg("No puedes desactivar esta página.");
               else if( FS_DEMO )
               {
                  if($show_error_demo)
                  {
                     $this->new_error_msg('En el modo <b>demo</b> no se pueden desactivar páginas.');
                     $show_error_demo = FALSE;
                  }
               }
               else if( !$p->delete() )
                  $this->new_error_msg('Imposible eliminar la página '.$p->name.'.');
            }
         }
         
         $this->new_message('Datos guardados correctamente.');
      }
      
      $this->paginas = $this->all_pages();
      $this->load_menu(TRUE);
   }
   
   private function all_pages()
   {
      $pages = array();
      
      /// añadimos las páginas que están en el directorio
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
   
   public function version()
   {
      return parent::version().'-4';
   }
}

?>
