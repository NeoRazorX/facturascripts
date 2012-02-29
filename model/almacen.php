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

class almacen extends fs_model
{
   public $observaciones;
   public $contacto;
   public $fax;
   public $telefono;
   public $codpais;
   public $provincia;
   public $idprovincia;
   public $poblacion;
   public $apartado;
   public $codpostal;
   public $direccion;
   public $nombre;
   public $codalmacen;
   
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
         $this->idprovincia = $a['idprovincia'];
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
         $this->idprovincia = NULL;
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
      return '';
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codalmacen = '".$this->codalmacen."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre = '".$this->nombre."', poblacion = '".$this->poblacion."',
                 direccion = '".$this->direccion."', codpostal = '".$this->codpostal."', telefono = '".$this->telefono."',
                 fax = '".$this->fax."', contacto = '".$this->contacto."' WHERE codalmacen = '".$this->codalmacen."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codalmacen,nombre,poblacion,direccion,codpostal,telefono,fax,contacto) VALUES
                 ('".$this->codalmacen."','".$this->nombre."','".$this->poblacion."','".$this->direccion."','".$this->codpostal."',
                 '".$this->telefono."','".$this->fax."','".$this->contacto."');";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codalmacen = '".$this->codalmacen."';");
   }
   
   public function all()
   {
      $listaa = array();
      $almacenes = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codalmacen ASC;");
      if($almacenes)
      {
         foreach($almacenes as $a)
         {
            $ao = new almacen($a);
            $listaa[] = $ao;
         }
      }
      return $listaa;
   }
   
   public function get($cod='')
   {
      $almacen = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codalmacen = '".$this->codalmacen."';");
      if($almacen)
         return new almacen($almacen[0]);
      else
         return FALSE;
   }
}

?>
