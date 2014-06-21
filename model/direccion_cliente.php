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
 * Una dirección de un cliente. Puede tener varias.
 */
class direccion_cliente extends fs_model
{
   public $id;
   public $codcliente;
   public $codpais;
   public $apartado;
   public $provincia;
   public $ciudad;
   public $codpostal;
   public $direccion;
   public $domenvio;
   public $domfacturacion;
   public $descripcion;
   
   public function __construct($d=FALSE)
   {
      parent::__construct('dirclientes');
      if($d)
      {
         $this->id = $this->intval($d['id']);
         $this->codcliente = $d['codcliente'];
         $this->codpais = $d['codpais'];
         $this->apartado = $d['apartado'];
         $this->provincia = $d['provincia'];
         $this->ciudad = $d['ciudad'];
         $this->codpostal = $d['codpostal'];
         $this->direccion = $d['direccion'];
         $this->domenvio = $this->str2bool($d['domenvio']);
         $this->domfacturacion = $this->str2bool($d['domfacturacion']);
         $this->descripcion = $d['descripcion'];
      }
      else
      {
         $this->id = NULL;
         $this->codcliente = NULL;
         $this->codpais = NULL;
         $this->apartado = NULL;
         $this->provincia = NULL;
         $this->ciudad = NULL;
         $this->codpostal = NULL;
         $this->direccion = NULL;
         $this->domenvio = TRUE;
         $this->domfacturacion = TRUE;
         $this->descripcion = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function get($id)
   {
      $dir = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($id).";");
      if($dir)
         return new direccion_cliente($dir[0]);
      else
         return FALSE;
   }

   public function exists()
   {
      if( is_null($this->id) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function test()
   {
      $this->apartado = $this->no_html($this->apartado);
      $this->ciudad = $this->no_html($this->ciudad);
      $this->codpostal = $this->no_html($this->codpostal);
      $this->descripcion = $this->no_html($this->descripcion);
      $this->direccion = $this->no_html($this->direccion);
      $this->provincia = $this->no_html($this->provincia);
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET codcliente = ".$this->var2str($this->codcliente).",
               codpais = ".$this->var2str($this->codpais).", apartado = ".$this->var2str($this->apartado).",
               provincia = ".$this->var2str($this->provincia).", ciudad = ".$this->var2str($this->ciudad).",
               codpostal = ".$this->var2str($this->codpostal).", direccion = ".$this->var2str($this->direccion).",
               domenvio = ".$this->var2str($this->domenvio).", domfacturacion = ".$this->var2str($this->domfacturacion).",
               descripcion = ".$this->var2str($this->descripcion)." WHERE id = ".$this->var2str($this->id).";";
            return $this->db->exec($sql);
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codcliente,codpais,apartado,provincia,ciudad,codpostal,direccion,
               domenvio,domfacturacion,descripcion) VALUES (".$this->var2str($this->codcliente).",".$this->var2str($this->codpais).",
               ".$this->var2str($this->apartado).",".$this->var2str($this->provincia).",".$this->var2str($this->ciudad).",
               ".$this->var2str($this->codpostal).",".$this->var2str($this->direccion).",".$this->var2str($this->domenvio).",
               ".$this->var2str($this->domfacturacion).",".$this->var2str($this->descripcion).");";
            $resultado = $this->db->exec($sql);
            if($resultado)
            {
               $newid = $this->db->lastval();
               if($newid)
                  $this->id = intval($newid);
            }
            return $resultado;
         }
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from_cliente($cod)
   {
      $dirlist = array();
      $dirs = $this->db->select("SELECT * FROM ".$this->table_name.
              " WHERE codcliente = ".$this->var2str($cod).";");
      if($dirs)
      {
         foreach($dirs as $d)
            $dirlist[] = new direccion_cliente($d);
      }
      return $dirlist;
   }
}
