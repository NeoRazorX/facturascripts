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

require_once 'base/fs_model.php';

class fs_page extends fs_model
{
   public $name;
   public $title;
   public $folder;
   public $version;
   public $show_on_menu;
   public $exists;
   public $enabled;

   public function __construct($p=FALSE)
   {
      parent::__construct('fs_pages');
      if($p)
      {
         $this->name = $p['name'];
         $this->title = $p['title'];
         $this->folder = $p['folder'];
         if( isset($p['version']) )
            $this->version = $p['version'];
         else
            $this->version = NULL;
         $this->show_on_menu = ($p['show_on_menu'] == 't');
      }
      else
      {
         $this->name = NULL;
         $this->title = NULL;
         $this->folder = NULL;
         $this->version = NULL;
         $this->show_on_menu = TRUE;
      }
      $this->exists = FALSE;
      $this->enabled = FALSE;
   }
   
   protected function install()
   {
      return "INSERT INTO ".$this->table_name." (name,title,folder,version,show_on_menu)
              VALUES ('admin_pages','páginas','admin',NULL,TRUE);";
   }
   
   public function url()
   {
      if( is_null($this->name) )
         return 'index.php?page=admin_pages';
      else
         return 'index.php?page='.$this->name;
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

   public function save()
   {
      $this->clean_cache();
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET title = ".$this->var2str($this->title).",
            folder = ".$this->var2str($this->folder).", version = ".$this->var2str($this->version).",
            show_on_menu = ".$this->var2str($this->show_on_menu)." WHERE name = ".$this->var2str($this->name).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (name,title,folder,version,show_on_menu) VALUES
            (".$this->var2str($this->name).",".$this->var2str($this->title).",".$this->var2str($this->folder).",
            ".$this->var2str($this->version).",".$this->var2str($this->show_on_menu).");";
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
         $pages = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY name ASC;");
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

?>
