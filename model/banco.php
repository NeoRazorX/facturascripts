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
require_model('subcuenta.php');

/**
 * Entidad bancaria.
 */
class banco extends fs_model
{
   public $entidad;
   public $nombre;
   public $codproveedor;
   
   public function __construct($b=FALSE)
   {
      parent::__construct('bancos');
      if($b)
      {
         $this->entidad = $b['entidad'];
         $this->nombre = $b['nombre'];
         $this->codproveedor = $b['codproveedor'];
      }
      else
      {
         $this->entidad = NULL;
         $this->nombre = NULL;
         $this->codproveedor = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      if( is_null($this->entidad) )
         return 'index.php?page=contabilidad_bancos';
      else
         return 'index.php?page=contabilidad_banco&entidad='.$this->entidad;
   }
   
   public function get($en)
   {
      $banco = $this->db->select("SELECT * FROM ".$this->table_name." WHERE entidad = ".$this->var2str($en).";");
      if($banco)
         return new banco($banco[0]);
      else
         return FALSE;
   }
   
   public function get_sucursales()
   {
      $suc = new sucursal();
      return $suc->all_from_entidad($this->entidad);
   }
   
   public function exists()
   {
      if( is_null($this->entidad) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE entidad = ".$this->var2str($this->entidad).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->entidad = $this->no_html($this->entidad);
      $this->nombre = $this->no_html($this->nombre);
      
      if( !preg_match("/^[A-Z0-9]{1,4}$/i", $this->entidad) )
      {
         $this->new_error_msg("Código de entidad no válido.");
      }
      else if( strlen($this->nombre) < 1 OR strlen($this->nombre) > 100 )
      {
         $this->new_error_msg("Nombre no válido.");
      }
      else
         $status = TRUE;
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).",
               codproveedor = ".$this->var2str($this->codproveedor)." WHERE entidad = ".$this->var2str($this->entidad).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (entidad,nombre,codproveedor) VALUES
               (".$this->var2str($this->entidad).",".$this->var2str($this->nombre).",
                  ".$this->var2str($this->codproveedor).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE entidad = ".$this->var2str($this->entidad).";");
   }
   
   public function all()
   {
      $listab = array();
      $bancos = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY nombre ASC;");
      if($bancos)
      {
         foreach($bancos as $b)
            $listab[] = new banco($b);
      }
      return $listab;
   }
}

/**
 * La sucursal de un banco. Está relacionada con un único banco.
 */
class sucursal extends fs_model
{
   public $observaciones;
   public $contacto;
   public $fax;
   public $telefono;
   public $codpais;
   public $provincia;
   public $poblacion;
   public $apartado;
   public $codpostal;
   public $direccion;
   public $nombre;
   public $agencia;
   public $entidad;
   public $id; /// pkey
   
   public function __construct($s=FALSE)
   {
      parent::__construct('sucursales');
      if($s)
      {
         $this->id = intval($s['id']);
         $this->entidad = $s['entidad'];
         $this->agencia = $s['agencia'];
         $this->nombre = $s['nombre'];
         $this->direccion = $s['direccion'];
         $this->codpostal = $s['codpostal'];
         $this->apartado = $s['apartado'];
         $this->poblacion = $s['poblacion'];
         $this->provincia = $s['provincia'];
         $this->codpais = $s['codpais'];
         $this->telefono = $s['telefono'];
         $this->fax = $s['fax'];
         $this->contacto = $s['contacto'];
         $this->observaciones = $s['observaciones'];
      }
      else
      {
         $this->id = NULL;
         $this->entidad = NULL;
         $this->agencia = NULL;
         $this->nombre = NULL;
         $this->direccion = NULL;
         $this->codpostal = NULL;
         $this->apartado = NULL;
         $this->poblacion = NULL;
         $this->provincia = NULL;
         $this->codpais = NULL;
         $this->telefono = NULL;
         $this->fax = NULL;
         $this->contacto = NULL;
         $this->observaciones = NULL;
      }
   }
   
   protected function install()
   {
      return '';
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
      $status = FALSE;
      
      $this->agencia = $this->no_html($this->agencia);
      $this->nombre = $this->no_html($this->nombre);
      $this->direccion = $this->no_html($this->direccion);
      $this->poblacion = $this->no_html($this->poblacion);
      $this->provincia = $this->no_html($this->provincia);
      $this->contacto = $this->no_html($this->contacto);
      $this->observaciones = $this->no_html($this->observaciones);
      
      if( !is_numeric($this->codpostal) )
      {
         $status = FALSE;
      }
      else if( !is_numeric($this->apartado) )
      {
         $status = FALSE;
      }
      else
         $status = TRUE;
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "";
         }
         else
         {
            $sql = "";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from_entidad($en)
   {
      $listasuc = array();
      $sucursales = $this->db->select("SELECT * FROM ".$this->table_name." WHERE entidad = ".$this->var2str($en).";");
      if($sucursales)
      {
         foreach($sucursales as $s)
            $listasuc[] = new sucursal($s);
      }
      return $listasuc;
   }
}
