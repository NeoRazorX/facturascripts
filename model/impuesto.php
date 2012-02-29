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

class impuesto extends fs_model
{
   public $codimpuesto;
   public $descripcion = '';
   public $iva;
   
   public function __construct($i=FALSE)
   {
      parent::__construct('impuestos');
      if($i)
      {
         $this->codimpuesto = $i['codimpuesto'];
         $this->descripcion = $i['descripcion'];
         $this->iva = floatval($i['iva']);
      }
      else
      {
         $this->codimpuesto = '';
         $this->descripcion = '';
         $this->iva = 0;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codimpuesto = '".$this->codimpuesto."';");
   }
   
   public function save()
   {
      
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codimpuesto = '".$this->codimpuesto."';");
   }
   
   public function all()
   {
      $impuestolist = array();
      $impuestos = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codimpuesto ASC;");
      if($impuestos)
      {
         foreach($impuestos as $i)
         {
            $io = new impuesto($i);
            $impuestolist[] = $io;
         }
      }
      return $impuestolist;
   }
}

?>
