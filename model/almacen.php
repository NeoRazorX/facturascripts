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
 * El almacén donde están físicamente los artículos.
 */
class almacen extends fs_model
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
   public $codalmacen; /// pkey
   
   public function __construct($a = FALSE)
   {
      parent::__construct('almacenes');
      if($a)
      {
         $this->observaciones = $a['observaciones'];
         $this->contacto = $a['contacto'];
         $this->fax = $a['fax'];
         $this->telefono = $a['telefono'];
         $this->codpais = $a['codpais'];
         $this->provincia = $a['provincia'];
         $this->poblacion = $a['poblacion'];
         $this->apartado = $a['apartado'];
         $this->codpostal = $a['codpostal'];
         $this->direccion = $a['direccion'];
         $this->nombre = $a['nombre'];
         $this->codalmacen = $a['codalmacen'];
      }
      else
      {
         $this->observaciones = '';
         $this->contacto = '';
         $this->fax = '';
         $this->telefono = '';
         $this->codpais = NULL;
         $this->provincia = NULL;
         $this->poblacion = NULL;
         $this->apartado = NULL;
         $this->codpostal = '';
         $this->direccion = '';
         $this->nombre = '';
         $this->codalmacen = NULL;
      }
   }

   protected function install()
   {
      $this->clean_cache();
      return "INSERT INTO ".$this->table_name." (codalmacen,nombre,poblacion,direccion,codpostal,telefono,fax,contacto)
         VALUES ('ALG','ALMACEN GENERAL','','','','','','');";
   }
   
   public function url()
   {
      if( is_null($this->codalmacen) )
      {
         return 'index.php?page=admin_almacenes';
      }
      else
         return 'index.php?page=admin_almacenes#'.$this->codalmacen;
   }
   
   public function is_default()
   {
      return ( $this->codalmacen == $this->default_items->codalmacen() );
   }
   
   public function get($cod)
   {
      $almacen = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codalmacen = ".$this->var2str($cod).";");
      if($almacen)
      {
         return new almacen($almacen[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->codalmacen) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codalmacen = ".$this->var2str($this->codalmacen).";");
   }
   
   public function test()
   {
      $status = FALSE;
      
      $this->codalmacen = trim($this->codalmacen);
      $this->nombre = $this->no_html($this->nombre);
      $this->provincia = $this->no_html($this->provincia);
      $this->poblacion = $this->no_html($this->poblacion);
      $this->direccion = $this->no_html($this->direccion);
      $this->codpostal = $this->no_html($this->codpostal);
      $this->telefono = $this->no_html($this->telefono);
      $this->fax = $this->no_html($this->fax);
      $this->contacto = $this->no_html($this->contacto);
      
      if( !preg_match("/^[A-Z0-9]{1,4}$/i", $this->codalmacen) )
      {
         $this->new_error_msg("Código de almacén no válido.");
      }
      else if( strlen($this->nombre) < 1 OR strlen($this->nombre) > 100 )
      {
         $this->new_error_msg("Nombre de almacén no válido.");
      }
      else
         $status = TRUE;
      
      return $status;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).",
               codpais = ".$this->var2str($this->codpais).", provincia = ".$this->var2str($this->provincia).",
               poblacion = ".$this->var2str($this->poblacion).", direccion = ".$this->var2str($this->direccion).",
               codpostal = ".$this->var2str($this->codpostal).", telefono = ".$this->var2str($this->telefono).",
               fax = ".$this->var2str($this->fax).", contacto = ".$this->var2str($this->contacto)."
               WHERE codalmacen = ".$this->var2str($this->codalmacen).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codalmacen,nombre,codpais,provincia,
               poblacion,direccion,codpostal,telefono,fax,contacto) VALUES
               (".$this->var2str($this->codalmacen).",".$this->var2str($this->nombre).",
               ".$this->var2str($this->codpais).",".$this->var2str($this->provincia).",
               ".$this->var2str($this->poblacion).",
               ".$this->var2str($this->direccion).",".$this->var2str($this->codpostal).",
               ".$this->var2str($this->telefono).",".$this->var2str($this->fax).",
               ".$this->var2str($this->contacto).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codalmacen = ".$this->var2str($this->codalmacen).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_almacen_all');
   }
   
   public function all()
   {
      $listaa = $this->cache->get_array('m_almacen_all');
      if( !$listaa )
      {
         $almacenes = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codalmacen ASC;");
         if($almacenes)
         {
            foreach($almacenes as $a)
               $listaa[] = new almacen($a);
         }
         $this->cache->set('m_almacen_all', $listaa);
      }
      return $listaa;
   }
}
