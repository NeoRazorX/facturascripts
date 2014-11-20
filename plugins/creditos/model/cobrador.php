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

class cobrador extends fs_model
{
   public $idcobrador;
   public $nombre;
   public $telefono;
   
   public function __construct($g = FALSE)
   {
      parent::__construct('cobradores', 'plugins/creditos/');
      
      if($g)
      {
         $this->idcobrador = $g['idcobrador'];
         $this->nombre = $g['nombre'];
         $this->telefono = $g['telefono'];
      }
      else
      {
         $this->idcobrador = NULL;
         $this->nombre = "";
         $this->telefono = "";
      }
   }
   
   protected function install() 
   {        
      return "INSERT INTO cobradores (nombre,telefono) VALUES ('Cobrador Inicial','');";
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM cobradores WHERE idcobrador = ".$this->var2str($id).";");
      if($data)
      {
         return new cobrador($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->idcobrador) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select("SELECT * FROM cobradores WHERE idcobrador = ".$this->var2str($this->idcobrador).";");
      }
   }
   
   public function nuevo_numero()
   {
      $data = $this->db->select("SELECT max(idcobrador) AS num FROM cobradores;");
      if($data)
         return intval($data[0]['num']) + 1;
      else
         return 1;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE cobradores SET nombre = ".$this->var2str($this->nombre).
                 ", telefono = ". $this->var2str($this->telefono).
                 " WHERE idcobrador = ".$this->var2str($this->idcobrador).";";
      }
      else
      {
         $sql = "INSERT INTO cobradores (idcobrador,nombre,telefono) VALUES ("
                 .$this->var2str($this->idcobrador).","
                 .$this->var2str($this->nombre).","
                 .$this->var2str($this->telefono).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("delete FROM cobradores WHERE idcobrador = ".$this->var2str($this->idcobrador).";");
   }
   
   public function listar()
   {
      $listag = array();
      
      $data = $this->db->select("SELECT * FROM cobradores;");
      if($data)
      {
         foreach($data AS $d)
         {
            $listag[] = new cobrador($d);
         }
      }
      
      return $listag;
   }
   
   public function buscar($texto = '')
   {
      $listag = array();
      
      $data = $this->db->select("SELECT * FROM cobradores WHERE nombre LIKE '%".$texto."%';");
      if($data)
      {
         foreach($data AS $d)
         {
            $listag[] = new cobrador($d);
         }
      }
      
      return $listag;
   }
   
   public function all()
   {
      $todos = array();

      $data = $this->db->select("SELECT * FROM cobradores");
      if($data)
      {
         foreach($data AS $d)
             $todos[] = new cobrador($d);
      }

      return $todos;
   }

 }
