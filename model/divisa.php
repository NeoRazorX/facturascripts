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

class divisa extends fs_model
{
   public $coddivisa;
   public $descripcion;
   public $tasaconv;
   public $bandera;
   public $fecha;
   public $codiso;
   
   private static $default_divisa;

   public function __construct($d=FALSE)
   {
      parent::__construct('divisas');
      if($d)
      {
         $this->coddivisa = $d['coddivisa'];
         $this->descripcion = $d['descripcion'];
         $this->tasaconv = floatval($d['tasaconv']);
         $this->bandera = $d['bandera'];
         $this->fecha = $d['fecha'];
         $this->codiso = $d['codiso'];
      }
      else
      {
         $this->coddivisa = NULL;
         $this->descripcion = '';
         $this->tasaconv = 1;
         $this->bandera = '';
         $this->fecha = Date('d-m-Y');
         $this->codiso = NULL;
      }
   }
   
   public function is_default()
   {
      if( isset(self::$default_divisa) )
         return (self::$default_divisa == $this->coddivisa);
      else if( !isset($_COOKIE['default_divisa']) )
         return FALSE;
      else if($_COOKIE['default_divisa'] == $this->coddivisa)
         return TRUE;
      else
         return FALSE;
   }
   
   public function set_default()
   {
      setcookie('default_divisa', $this->coddivisa, time()+FS_COOKIES_EXPIRE);
      self::$default_divisa = $this->coddivisa;
   }
   
   protected function install()
   {
      return "INSERT INTO ".$this->table_name." (coddivisa,descripcion,tasaconv,bandera,fecha,codiso)
         VALUES ('EUR','EUROS','1','','".Date('j-n-Y')."','978');";
   }
   
   public function get($cod)
   {
      $divisa = $this->db->select("SELECT * FROM ".$this->table_name." WHERE coddivisa = '".$cod."';");
      if($divisa)
         return new divisa($divisa[0]);
   }
   
   public function exists()
   {
      if( is_null($this->coddivisa) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE coddivisa = '".$this->coddivisa."';");
   }
   
   public function save()
   {
      ;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE coddivisa = '".$this->coddivisa."';");
   }
   
   public function all()
   {
      $listad = array();
      $divisas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY coddivisa ASC;");
      if($divisas)
      {
         foreach($divisas as $d)
            $listad[] = new divisa($d);
      }
      return $listad;
   }
}

?>
