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

class pais extends fs_model
{
   public $codiso;
   public $bandera;
   public $nombre;
   public $codpais;
   
   public function __construct($p=FALSE)
   {
      parent::__construct('paises');
      if($p)
      {
         $this->codpais = $p['codpais'];
         $this->nombre = $p['nombre'];
         $this->bandera = $p['bandera'];
         $this->codiso = $p['codiso'];
      }
      else
      {
         $this->codpais = '';
         $this->nombre = '';
         $this->bandera = NULL;
         $this->codiso = NULL;
      }
   }
   
   public function url()
   {
      return 'index.php?page=admin_paises#'.$this->codpais;
   }


   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codpais = '".$this->codpais."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre = '".$this->nombre."' WHERE codpais = '".$this->codpais."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codpais,nombre) VALUES ('".$this->codpais."','".$this->nombre."');";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codpais = '".$this->codpais."';");
   }
   
   public function all()
   {
      $listap = array();
      $paises = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codpais ASC;");
      if($paises)
      {
         foreach($paises as $p)
         {
            $po = new pais($p);
            $listap[] = $po;
         }
      }
      return $listap;
   }
}

?>
