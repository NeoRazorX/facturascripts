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
   public $recargo;
   
   private static $default_impuesto;

   public function __construct($i=FALSE)
   {
      parent::__construct('impuestos');
      if($i)
      {
         $this->codimpuesto = $i['codimpuesto'];
         $this->descripcion = $i['descripcion'];
         $this->iva = floatval($i['iva']);
         $this->recargo = floatval($i['recargo']);
      }
      else
      {
         $this->codimpuesto = NULL;
         $this->descripcion = '';
         $this->iva = 0;
         $this->recargo = 0;
      }
   }
   
   public function url()
   {
      return 'index.php?page=contabilidad_impuestos#'.$this->codimpuesto;
   }
   
   public function is_default()
   {
      if( isset(self::$default_impuesto) )
         return (self::$default_impuesto == $this->codimpuesto);
      else if( !isset($_COOKIE['default_impuesto']) )
         return FALSE;
      else if($_COOKIE['default_impuesto'] == $this->codimpuesto)
         return TRUE;
      else
         return FALSE;
   }


   public function set_default()
   {
      setcookie('default_impuesto', $this->codimpuesto, time()+FS_COOKIES_EXPIRE);
      self::$default_impuesto = $this->codimpuesto;
   }

   protected function install()
   {
      return "INSERT INTO ".$this->table_name." (codimpuesto,descripcion,iva,recargo) VALUES ('IVA8','IVA 8%','8','0');".
           "INSERT INTO ".$this->table_name." (codimpuesto,descripcion,iva,recargo) VALUES ('IVA18','IVA 18%','18','0');";
   }
   
   public function exists()
   {
      if( is_null($this->codimpuesto) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codimpuesto = '".$this->codimpuesto."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET descripcion = ".$this->var2str($this->descripcion).",
            iva = ".$this->var2str($this->iva).", recargo = ".$this->var2str($this->recargo)."
            WHERE codimpuesto = '".$this->codimpuesto."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codimpuesto,descripcion,iva,recargo) VALUES (".$this->var2str($this->codimpuesto).",
            ".$this->var2str($this->descripcion).",".$this->var2str($this->iva).",".$this->var2str($this->recargo).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codimpuesto = '".$this->codimpuesto."';");
   }
   
   public function get($cod)
   {
      $impuesto = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codimpuesto = '".$cod."';");
      if($impuesto)
         return new impuesto($impuesto[0]);
      else
         return FALSE;
   }
   
   public function all()
   {
      $impuestolist = array();
      $impuestos = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codimpuesto ASC;");
      if($impuestos)
      {
         foreach($impuestos as $i)
            $impuestolist[] = new impuesto($i);
      }
      return $impuestolist;
   }
}

?>
