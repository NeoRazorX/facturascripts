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
         if($p['show_on_menu'] == 't')
            $this->show_on_menu = TRUE;
         else
            $this->show_on_menu = FALSE;
      }
      else
      {
         $this->name = '';
         $this->title = '';
         $this->folder = '';
         $this->show_on_menu = TRUE;
      }
      $this->exists = FALSE;
      $this->enabled = FALSE;
   }
   
   protected function install()
   {
      return "INSERT INTO ".$this->table_name." (name,title,folder,show_on_menu) VALUES ('admin_pages','páginas','admin',TRUE);";
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE name='".$this->name."';");
   }
   
   public function get($name='')
   {
      $p = $this->db->select("SELECT * FROM ".$this->table_name." WHERE name='".$name."';");
      if($p)
         return new fs_page($p[0]);
      else
         return FALSE;
   }

   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET title = '".$this->title."', folder = '".$this->folder.
                "', show_on_menu = ".$this->bool2str($this->show_on_menu)." WHERE name = '".$this->name."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (name,title,folder,show_on_menu) VALUES
            ('".$this->name."','".$this->title."','".$this->folder."',".$this->bool2str($this->show_on_menu).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE name = '".$this->name."';");
   }
   
   public function all()
   {
      $pagelist = array();
      $pages = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY name ASC;");
      if($pages)
      {
         foreach($pages as $p)
         {
            $fp = new fs_page($p);
            $pagelist[] = $fp;
         }
      }
      return $pagelist;
   }
   
   public function url()
   {
      return 'index.php?page='.$this->name;
   }
}

?>
