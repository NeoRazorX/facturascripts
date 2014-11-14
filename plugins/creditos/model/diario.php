<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Salvador Merino  salvaweb.co@gmail.com
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

class diario extends fs_model
{
   public $iddiario;
   public $descripcion;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('diarios', 'plugins/creditos/');
      
      if($g)
      {
         $this->iddiario = $g['iddiario'];
         $this->descripcion = $g['descripcion'];
      }
      else
      {
         $this->iddiario = NULL;
         $this->descripcion = "";
      }
   }
   
   protected function install() 
   {        
      return "INSERT INTO diarios (iddiario,descripcion) VALUES
            (1,'Diario Inicial');";
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM diarios WHERE iddiario = ".$this->var2str($id).";");
      if($data)
      {
         return new diario($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->iddiario) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM diarios WHERE iddiario = ".$this->var2str($this->iddiario).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("SELECT max(iddiario) AS num FROM diarios;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE diarios SET descripcion = ".$this->var2str($this->descripcion).
                 " WHERE iddiario = ".$this->var2str($this->iddiario).";";
      }
      else
      {
         $sql = "INSERT INTO diarios (iddiario,descripcion) VALUES ("
                 .$this->var2str($this->iddiario).","
                 .$this->var2str($this->descripcion).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete FROM diarios WHERE iddiario = ".$this->var2str($this->iddiario).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("SELECT * FROM diarios;");
      if($data)
      {
         foreach($data AS $d)
         {
            $listag[] = new diario($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("SELECT * FROM diarios WHERE descripcion LIKE '%".$texto."%';");
      if($data)
      {
         foreach($data AS $d)
         {
            $listag[] = new diario($d);
         }
      }
      
      return $listag;
   }
   
   public function all()
   {
      $todos = array();

      $data = $this->db->select("SELECT * FROM diarios");
      if($data)
      {
         foreach($data AS $d)
             $todos[] = new diario($d);
      }

      return $todos;
   }

 }
