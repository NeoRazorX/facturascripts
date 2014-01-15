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
require_model('cuenta.php');

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
   
   public function url()
   {
      if( isset($this->idgrupo) )
         return 'index.php?page=contabilidad_epigrafes&grupo='.$this->idgrupo;
      else
         return 'index.php?page=contabilidad_epigrafes';
   }
   
   public function get_epigrafes()
   {
      $epigrafe = new epigrafe();
      return $epigrafe->all_from_grupo($this->idgrupo);
   }
   
   public function get($id)
   {
      $grupo = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE idgrupo = ".$this->var2str($id).";");
      if($grupo)
         return new grupo_epigrafes($grupo[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod, $eje)
   {
      $grupo = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE codgrupo = ".$this->var2str($cod)." AND codejercicio = ".$this->var2str($eje).";");
      if($grupo)
         return new grupo_epigrafes($grupo[0]);
      else
         return FALSE;
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
      
      if( strlen($this->codejercicio)>0 AND strlen($this->codgrupo)>0 AND strlen($this->descripcion)>0 )
         return TRUE;
      else
      {
         $this->new_error_msg('Faltan datos en el grupo de epígrafes.');
         return FALSE;
      }
   }
   
   public function save()
   {
      if( $this->test() )
      {
         if( $this->exists() )
         {
            $sql = "UPDATE ".$this->table_name." SET codgrupo = ".$this->var2str($this->codgrupo).",
               descripcion = ".$this->var2str($this->descripcion).",
               codejercicio = ".$this->var2str($this->codejercicio)."
               WHERE idgrupo = ".$this->var2str($this->idgrupo).";";
            return $this->db->exec($sql);
         }
         else
         {
            $newid = $this->db->nextval($this->table_name.'_idgrupo_seq');
            if($newid)
            {
               $this->idgrupo = intval($newid);
               $sql = "INSERT INTO ".$this->table_name." (idgrupo,codgrupo,descripcion,codejercicio)
                  VALUES (".$this->var2str($this->idgrupo).",".$this->var2str($this->codgrupo).",
                  ".$this->var2str($this->descripcion).",".$this->var2str($this->codejercicio).");";
               return $this->db->exec($sql);
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
   public $idepigrafe; /// pkey
   public $codepigrafe;
   public $idgrupo;
   public $codgrupo;
   public $codejercicio;
   public $descripcion;
   
   private static $grupos;
   
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
         
         if( !isset(self::$grupos) )
         {
            $ge = new grupo_epigrafes();
            self::$grupos = $ge->all_from_ejercicio( $this->codejercicio );
         }
         
         foreach(self::$grupos as $g)
         {
            if($g->idgrupo == $this->idgrupo)
            {
               $this->codgrupo = $g->codgrupo;
               break;
            }
         }
      }
      else
      {
         $this->idepigrafe = NULL;
         $this->codepigrafe = NULL;
         $this->idgrupo = NULL;
         $this->codgrupo = NULL;
         $this->descripcion = NULL;
         $this->codejercicio = NULL;
      }
   }
   
   protected function install()
   {
      /// forzamos los creación de la tabla de grupos
      $grupo = new grupo_epigrafes();
      return '';
   }
   
   /*
    * Sobreescribimos check_table para poder ejecutar el código necesario
    * para enlazar los epigrafes con su grupo correspondiente, y así solucionar
    * este bug de los tiempos de facturalux
    */
   public function check_table($table_name)
   {
      if( $this->db->table_exists($table_name) )
      {
         $cols = $this->db->get_columns($table_name);
         foreach($cols as $col)
         {
            if($col['column_name'] == 'idgrupo')
            {
               $this->db->exec("UPDATE ".$table_name." SET idgrupo = NULL WHERE idgrupo = '0';");
               break;
            }
         }
      }
      
      parent::check_table($table_name);
   }
   
   public function url()
   {
      if( isset($this->idepigrafe) )
         return 'index.php?page=contabilidad_epigrafes&epi='.$this->idepigrafe;
      else
         return 'index.php?page=contabilidad_epigrafes';
   }
   
   public function get_cuentas()
   {
      $cuenta = new cuenta();
      return $cuenta->full_from_epigrafe($this->idepigrafe);
   }
   
   public function get($id)
   {
      $epis = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE idepigrafe = ".$this->var2str($id).";");
      if($epis)
         return new epigrafe($epis[0]);
      else
         return FALSE;
   }
   
   public function get_by_codigo($cod, $eje)
   {
      $epis = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE codepigrafe = ".$this->var2str($cod)." AND codejercicio = ".$this->var2str($eje).";");
      if($epis)
         return new epigrafe($epis[0]);
      else
         return FALSE;
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
      
      if( strlen($this->codepigrafe)>0 AND strlen($this->descripcion)>0 AND strlen($this->codgrupo)>0 )
         return TRUE;
      else
      {
         $this->new_error_msg('Faltan datos en el epígrafe.');
         return FALSE;
      }
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
            return $this->db->exec($sql);
         }
         else
         {
            $newid = $this->db->nextval($this->table_name.'_idepigrafe_seq');
            if($newid)
            {
               $this->idepigrafe = intval($newid);
               $sql = "INSERT INTO ".$this->table_name." (idepigrafe,codepigrafe,idgrupo,descripcion,codejercicio)
                  VALUES (".$this->var2str($this->idepigrafe).",".$this->var2str($this->codepigrafe).",
                  ".$this->var2str($this->idgrupo).",".$this->var2str($this->descripcion).",
                  ".$this->var2str($this->codejercicio).");";
               return $this->db->exec($sql);
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
   
   public function all_from_grupo($id)
   {
      $epilist = array();
      $epigrafes = $this->db->select("SELECT * FROM ".$this->table_name.
         " WHERE idgrupo = ".$this->var2str($id).
         " ORDER BY codepigrafe ASC;");
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