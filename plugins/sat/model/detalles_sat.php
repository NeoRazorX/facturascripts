<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2014  Francisco Javier Trujillo   javier.trujillo.jimenez@gmail.com
 * Copyright (C) 2014  Carlos Garcia Gomez         neorazorx@gmail.com
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

class detalles_sat extends fs_model
{
   public $id;
   public $descripcion;
   public $nsat;
   public $fecha;
   
   public function __construct($s = FALSE)
   {
      parent::__construct('detalles_sat', 'plugins/sat/');
      
      if($s)
      {
         $this->id = intval($s['id']);
         $this->descripcion = $s['descripcion'];
         $this->nsat = intval($s['nsat']);
         $this->fecha = date('d-m-Y', strtotime($s['fecha']));
      }
      else
      {
         $this->id = NULL;
         $this->descripcion = '';
         $this->nsat = NULL;
         $this->fecha = date('d-m-Y');
      }
   }
   
   public function install()
   {
      return '';
   }
   
   
   
   public function get($id)
   {
      $sql = "SELECT id,descripcion,nsat,fecha
         FROM detalles_sat
         WHERE id = ".$this->var2str($id).";";
      $data = $this->db->select($sql);
      if($data)
         return new detalles_sat($data[0]);
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
      {
         return $this->db->select("SELECT * FROM detalles_sat WHERE id = ".$this->var2str($this->nsat).";");
      }
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      
      /// realmente no quería comprobar nada, simplemente eliminar el html de las variables
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE detalles_sat SET descripcion = ".$this->var2str($this->descripcion).",
               fecha = ".$this->var2str($this->fecha).", nsat = ".$this->var2str($this->nsat).""
               . " WHERE id = ".$this->var2str($this->id).";";
            
            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO detalles_sat (descripcion,fecha,nsat) VALUES (".$this->var2str($this->descripcion).",
               ".$this->var2str($this->fecha).",".$this->var2str($this->nsat).");";
            
            if( $this->db->exec($sql) )
            {
               $this->id = $this->db->lastval();
               return TRUE;
            }
            else
               return FALSE;
         }
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      
   }
   
   public function all()
   {
      $detalleslist = array();
      
      $sql = "SELECT detalles_sat.id, detalles_sat.descripcion,detalles_sat.nsat, detalles_sat.fecha
         FROM registros_sat, detalles_sat
         WHERE detalles_sat.nsat = registros_sat.nsat ORDER BY fecha ASC, id ASC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $detalleslist[] = new detalles_sat($d);
      }
      
      return $detalleslist;
   }
   
   public function all_from_sat($sat)
   {
      $detalleslist = array();
      
      $sql = "SELECT detalles_sat.id, detalles_sat.descripcion,detalles_sat.nsat, detalles_sat.fecha
         FROM registros_sat, detalles_sat
         WHERE detalles_sat.nsat = registros_sat.nsat AND detalles_sat.nsat = $sat ORDER BY fecha ASC, id ASC;";
      $data = $this->db->select($sql);
      if($data)
      {
         foreach($data as $d)
            $detalleslist[] = new detalles_sat($d);
      }
      
      return $detalleslist;
   }
   
}
