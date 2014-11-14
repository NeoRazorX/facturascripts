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

class ruta extends fs_model
{
   public $idruta;
   public $descripcion;
   public $idcobrador;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('rutas', 'plugins/creditos/');
      
      if($g)
      {
         $this->idruta = $g['idruta'];
         $this->descripcion = $g['descripcion'];
         $this->idcobrador = $g['idcobrador'];
      }
      else
      {
         $this->idruta = NULL;
         $this->descripcion = "";
         $this->idcobrador = "";
      }
   }
   
   protected function install() 
   {
      return "INSERT INTO rutas (idruta,descripcion) VALUES
            (1,'Ruta Inicial');";
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM rutas WHERE idruta = ".$this->var2str($id).";");
      if($data)
      {
         return new ruta($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idruta) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM rutas WHERE idruta = ".$this->var2str($this->idruta).";");
      }
   }
   
   public function test() {
      ;
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("SELECT max(idruta) AS num FROM rutas;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE rutas SET descripcion = ".$this->var2str($this->descripcion).
                 ", idcobrador = ".$this->var2str($this->idcobrador).
                 " WHERE idruta = ".$this->var2str($this->idruta).";";
      }
      else
      {
         $sql = "INSERT INTO rutas (idruta,descripcion,idcobrador) VALUES ("
                 .$this->var2str($this->idruta).","
                 .$this->var2str($this->descripcion).","
                 .$this->var2str($this->idcobrador).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete FROM rutas WHERE idruta = ".$this->var2str($this->idruta).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("SELECT * FROM rutas;");
      if($data)
      {
         foreach($data AS $d)
         {
            $listag[] = new ruta($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("SELECT * FROM rutas WHERE descripcion LIKE '%".$texto."%';");
      if($data)
      {
         foreach($data AS $d)
         {
            $listag[] = new ruta($d);
         }
      }
      
      return $listag;
   }
   
   public function all()
   {
      $todos = array();

      $data = $this->db->select("SELECT * FROM rutas");
      if($data)
      {
         foreach($data AS $d)
             $todos[] = new ruta($d);
      }

      return $todos;
   }

 }
