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

class balance extends fs_model
{
   public $descripcion4ba;
   public $descripcion4;
   public $nivel4;
   public $descripcion3;
   public $orden3;
   public $nivel3;
   public $descripcion2;
   public $nivel2;
   public $descripcion1;
   public $nivel1;
   public $naturaleza;
   public $codbalance; /// pkey
   
   public function __construct($b=FALSE)
   {
      parent::__construct('co_codbalances08');
      if($b)
      {
         $this->codbalance = $b['codbalance'];
         $this->naturaleza = $b['naturaleza'];
         $this->nivel1 = $b['nivel1'];
         $this->descripcion1 = $b['descripcion1'];
         $this->nivel2 = $b['nivel2'];
         $this->descripcion2 = $b['descripcion2'];
         $this->nivel3 = $b['nivel3'];
         $this->descripcion3 = $b['descripcion3'];
         $this->orden3 = $b['orden3'];
         $this->nivel4 = $b['nivel4'];
         $this->descripcion4 = $b['descripcion4'];
         $this->descripcion4ba = $b['descripcion4ba'];
      }
      else
      {
         $this->codbalance = NULL;
         $this->naturaleza = NULL;
         $this->nivel1 = NULL;
         $this->descripcion1 = NULL;
         $this->nivel2 = NULL;
         $this->descripcion2 = NULL;
         $this->nivel3 = NULL;
         $this->descripcion3 = NULL;
         $this->orden3 = NULL;
         $this->nivel4 = NULL;
         $this->descripcion4 = NULL;
         $this->descripcion4ba = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->codbalance) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
                 " WHERE codbalance = ".$this->var2str($this->codbalance).";");
   }
   
   public function test()
   {
      return TRUE;
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
      return $this->db->select("DELETE FROM ".$this->table_name.
         " WHERE codbalance = ".$this->var2str($this->codbalance).";");
   }
}

?>
