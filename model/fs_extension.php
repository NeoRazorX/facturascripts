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
   public $name;
   public $from;
   public $to;
   public $type;
   public $text;
   public $params;
   
   public function __construct($e = FALSE)
   {
      parent::__construct('fs_extensions2');
      if($e)
      {
         $this->name = $e['name'];
         $this->from = $e['page_from'];
         $this->to = $e['page_to'];
         $this->type = $e['type'];
         $this->text = $e['text'];
         $this->params = $e['params'];
      }
      else
      {
         $this->name = NULL;
         $this->from = NULL;
         $this->to = NULL;
         $this->type = NULL;
         $this->text = NULL;
         $this->params = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get($name, $from)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE name = ".$this->var2str($name)." AND page_from = ".$this->var2str($from).";");
      if($data)
      {
         return new fs_extension($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->name) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE name = ".$this->var2str($this->name)." AND page_from = ".$this->var2str($this->from).";");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET page_to = ".$this->var2str($this->to).",
            type = ".$this->var2str($this->type).", text = ".$this->var2str($this->text).",
            params = ".$this->var2str($this->params)." WHERE name = ".$this->var2str($this->name).
                 " AND page_from = ".$this->var2str($this->from).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (name,page_from,page_to,type,text,params) VALUES
            (".$this->var2str($this->name).",".$this->var2str($this->from).",".$this->var2str($this->to).",
            ".$this->var2str($this->type).",".$this->var2str($this->text).",".$this->var2str($this->params).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE name = ".$this->var2str($this->name)." AND page_from = ".$this->var2str($this->from).";");
   }
   
   public function all_to($to)
   {
      $elist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE page_to = ".$this->var2str($to)." ORDER BY name ASC;");
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
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE type = ".$this->var2str($tipo)." ORDER BY name ASC;");
      if($data)
      {
         foreach($data as $d)
            $elist[] = new fs_extension($d);
      }
      
      return $elist;
   }
   
   public function all()
   {
      $elist = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY name ASC;");
      if($data)
      {
         foreach($data as $d)
            $elist[] = new fs_extension($d);
      }
      
      return $elist;
   }
}