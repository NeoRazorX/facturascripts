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

class grupo_epigrafes extends fs_model
{
   public $codejercicio;
   public $descripcion;
   public $codgrupo;
   public $idgrupo; /// pkey
   
   public function __construct($f = FALSE)
   {
      parent::__construct('co_gruposepigrafes');
      if($f)
      {
         $this->idgrupo = $this->intval($f['idgrupo']);
         $this->codgrupo = $f['codgrupo'];
         $this->descripcion = $f['descripcion'];
         $this->codejercicio = $f['codejercicio'];
      }
      else
      {
         $this->idgrupo = NULL;
         $this->codgrupo = NULL;
         $this->descripcion = NULL;
         $this->codejercicio = NULL;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->idgrupo) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
            " WHERE idgrupo = ".$this->var2str($this->idgrupo).";");
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->save() )
         {
            $sql = "UPDATE ".$this->table_name." SET codgrupo = ".$this->var2str($this->codgrupo).",
               descripcion = ".$this->var2str($this->descripcion).", codejercicio = ".$this->var2str($this->codejercicio)."
               WHERE idgrupo = ".$this->var2str($this->idgrupo).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codgrupo,descripcion,codejercicio) VALUES
               (".$this->var2str($this->codgrupo).",".$this->var2str($this->descripcion).",
                ".$this->var2str($this->codejercicio).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name.
         " WHERE idgrupo = ".$this->var2str($this->idgrupo).";");
   }
   
   public function all_from_ejercicio($codejercicio)
   {
      $epilist = array();
      $epigrafes = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE codejercicio = ".$this->var2str($codejercicio).
         " ORDER BY codgrupo ASC;");
      if($epigrafes)
      {
         foreach($epigrafes as $ep)
            $epilist[] = new grupo_epigrafes($ep);
      }
      return $epilist;
   }
}

class epigrafe extends fs_model
{
   public $codejercicio;
   public $descripcion;
   public $codgrupo;
   public $idgrupo;
   public $codepigrafe;
   public $idepigrafe; /// pkey
   
   public function __construct($e = FALSE)
   {
      parent::__construct('co_epigrafes');
      if($e)
      {
         $this->idepigrafe = $this->intval($e['idepigrafe']);
         $this->codepigrafe = $e['codepigrafe'];
         $this->idgrupo = $this->intval($e['idgrupo']);
         $this->descripcion = $e['descripcion'];
         $this->codejercicio = $e['codejercicio'];
      }
      else
      {
         $this->idepigrafe = NULL;
         $this->codepigrafe = NULL;
         $this->idgrupo = NULL;
         $this->descripcion = NULL;
         $this->codejercicio = NULL;
      }
      $this->codgrupo = NULL;
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->idepigrafe) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name.
            " WHERE idepigrafe = ".$this->var2str($this->idepigrafe).";");
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET codepigrafe = ".$this->var2str($this->codepigrafe).",
               idgrupo = ".$this->var2str($this->idgrupo).", descripcion = ".$this->var2str($this->descripcion).",
               codejercicio = ".$this->var2str($this->codejercicio)."
               WHERE idepigrafe = ".$this->var2str($this->idepigrafe).";";
         }
         else
         {
            $sql = "INSERT INTO ".$this->table_name." (codepigrafe,idgrupo,descripcion,codejercicio)
               VALUES (".$this->var2str($this->codepigrafe).",".$this->var2str($this->idgrupo).",
               ".$this->var2str($this->descripcion).",".$this->var2str($this->codejercicio).");";
         }
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name.
            " WHERE idepigrafe = ".$this->var2str($this->idepigrafe).";");
   }
   
   public function all($offset=0)
   {
      $epilist = array();
      $epigrafes = $this->db->select_limit("SELECT * FROM ".$this->table_name.
         " ORDER BY codejercicio DESC, codepigrafe ASC", FS_ITEM_LIMIT, $offset);
      if($epigrafes)
      {
         foreach($epigrafes as $ep)
            $epilist[] = new epigrafe($ep);
      }
      return $epilist;
   }
   
   public function all_from_ejercicio($codejercicio)
   {
      $epilist = array();
      $epigrafes = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE codejercicio = ".$this->var2str($codejercicio).
         " ORDER BY codepigrafe ASC;");
      if($epigrafes)
      {
         foreach($epigrafes as $ep)
            $epilist[] = new epigrafe($ep);
      }
      return $epilist;
   }
}

?>
