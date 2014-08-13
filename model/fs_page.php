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

require_once 'base/fs_model.php';

/**
 * Elemento del menú de FacturaScripts.
 */
class fs_page extends fs_model
{
   public $name;
   public $title;
   public $folder;
   public $version;
   public $show_on_menu;
   public $exists;
   public $enabled;
   
   public $extra_url;
   
   /**
    * Cuando un usuario no tiene asignada una página por defecto, se selecciona
    * la primera página importante a la que tiene acceso.
    */
   public $important;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('fs_pages');
      if($p)
      {
         $this->name = $p['name'];
         $this->title = $p['title'];
         $this->folder = $p['folder'];
         
         $this->version = NULL;
         if( isset($p['version']) )
            $this->version = $p['version'];
         
         $this->show_on_menu = $this->str2bool($p['show_on_menu']);
         $this->important = $this->str2bool($p['important']);
      }
      else
      {
         $this->name = NULL;
         $this->title = NULL;
         $this->folder = NULL;
         $this->version = NULL;
         $this->show_on_menu = TRUE;
         $this->important = FALSE;
      }
      
      $this->exists = FALSE;
      $this->enabled = FALSE;
      $this->extra_url = '';
   }
   
   public function __clone()
   {
      $page = new fs_page();
      $page->name = $this->name;
      $page->title = $this->title;
      $page->folder = $this->folder;
      $page->version = $this->version;
      $page->show_on_menu = $this->show_on_menu;
      $page->important = $this->important;
   }
   
   protected function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (name,title,folder,version,show_on_menu)
         VALUES ('admin_pages','páginas','admin',NULL,TRUE);";
   }
   
   public function url()
   {
      if( is_null($this->name) )
         return 'index.php?page=admin_pages';
      else
         return 'index.php?page='.$this->name.$this->extra_url;
   }
   
   public function is_default()
   {
      return ( $this->name == $this->default_items->default_page() );
   }
   
   public function showing()
   {
      return ( $this->name == $this->default_items->showing_page() );
   }
   
   public function exists()
   {
      if( is_null($this->name) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE name = ".$this->var2str($this->name).";");
   }
   
   public function get($name)
   {
      $p = $this->db->select("SELECT * FROM ".$this->table_name." WHERE name = ".$this->var2str($name).";");
      if($p)
         return new fs_page($p[0]);
      else
         return FALSE;
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      $this->clean_cache();
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET title = ".$this->var2str($this->title).",
            folder = ".$this->var2str($this->folder).", version = ".$this->var2str($this->version).",
            show_on_menu = ".$this->var2str($this->show_on_menu).",
            important = ".$this->var2str($this->important)."
            WHERE name = ".$this->var2str($this->name).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (name,title,folder,version,show_on_menu,important)
            VALUES (".$this->var2str($this->name).",".$this->var2str($this->title).",
            ".$this->var2str($this->folder).",".$this->var2str($this->version).",
            ".$this->var2str($this->show_on_menu).",".$this->var2str($this->important).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE name = ".$this->var2str($this->name).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_fs_page_all');
   }
   
   public function all()
   {
      $pagelist = $this->cache->get_array('m_fs_page_all');
      if( !$pagelist )
      {
         $pages = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY lower(folder) ASC, lower(title) ASC;");
         if($pages)
         {
            foreach($pages as $p)
               $pagelist[] = new fs_page($p);
         }
         $this->cache->set('m_fs_page_all', $pagelist);
      }
      return $pagelist;
   }
}
