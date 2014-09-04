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

/**
 * Permite que un plugin añada elementos a una vista existente,
 * sin necesidad de un reemplazo completo.
 */
class fs_extension extends fs_model
{
   public $from;
   
   /**
    * Clave primaria.
    * @var type 
    */
   public $id;
   public $name;
   public $plugin;
   public $text;
   public $to;
   public $type;
   
   public function __construct($e = FALSE)
   {
      parent::__construct('fs_extensions');
      
      if($e)
      {
         $this->from = $e['page_from'];
         $this->id = intval($e['id']);
         $this->name = $e['name'];
         $this->plugin = $e['plugin'];
         $this->text = $e['text'];
         $this->to = $e['page_to'];
         $this->type = $e['type'];
      }
      else
      {
         $this->from = NULL;
         $this->id = NULL;
         $this->name = NULL;
         $this->plugin = NULL;
         $this->text = NULL;
         $this->to = NULL;
         $this->type = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($id).";");
      if($data)
      {
         return new fs_extension($data[0]);
      }
      else
         return FALSE;
   }
   
   public function get_by($from, $to)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE page_from = ".$this->var2str($from)." AND page_to = ".$this->var2str($to).";");
      if($data)
      {
         return new fs_extension($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET plugin = ".$this->var2str($this->plugin).",
                 page_from = ".$this->var2str($this->from).", page_to = ".$this->var2str($this->to).",
                 type = ".$this->var2str($this->type).", text = ".$this->var2str($this->text).",
                 name = ".$this->var2str($this->name)." WHERE id = ".$this->var2str($this->id).";";
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (plugin,page_from,page_to,type,text,name) VALUES
                 (".$this->var2str($this->plugin).",".$this->var2str($this->from).",".$this->var2str($this->to).",
                 ".$this->var2str($this->type).",".$this->var2str($this->text).",".$this->var2str($this->name).");";
         
         if( $this->db->exec($sql) )
         {
            $this->id = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_to($to)
   {
      $elist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE page_to = ".$this->var2str($to)." ORDER BY page_from ASC;");
      if($data)
      {
         foreach($data as $d)
            $elist[] = new fs_extension($d);
      }
      
      return $elist;
   }
   
   public function all_4_type($tipo)
   {
      $elist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE type = ".$this->var2str($tipo)." ORDER BY page_from ASC;");
      if($data)
      {
         foreach($data as $d)
            $elist[] = new fs_extension($d);
      }
      
      return $elist;
   }
}