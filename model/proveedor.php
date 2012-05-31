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
require_once 'model/albaran_proveedor.php';

class proveedor extends fs_model
{
   public $codproveedor;
   public $nombre;
   public $nombrecomercial;
   public $cifnif;
   public $telefono1;
   public $telefono2;
   public $fax;
   public $email;
   public $web;
   public $codserie;
   public $coddivisa;
   public $codpago;
   public $observaciones;
   
   private static $default_proveedor;

   public function __construct($p=FALSE)
   {
      parent::__construct('proveedores');
      if($p)
      {
         $this->codproveedor = $p['codproveedor'];
         $this->nombre = $p['nombre'];
         $this->nombrecomercial = $p['nombrecomercial'];
         $this->cifnif = $p['cifnif'];
         $this->telefono1 = $p['telefono1'];
         $this->telefono2 = $p['telefono2'];
         $this->fax = $p['fax'];
         $this->email = $p['email'];
         $this->web = $p['web'];
         $this->codserie = $p['codserie'];
         $this->coddivisa = $p['coddivisa'];
         $this->codpago = $p['codpago'];
         $this->observaciones = $p['observaciones'];
      }
      else
      {
         $this->codproveedor = '';
         $this->nombre = '';
         $this->nombrecomercial = '';
         $this->cifnif = '';
         $this->telefono1 = '';
         $this->telefono2 = '';
         $this->fax = '';
         $this->email = '';
         $this->web = '';
         $this->codserie = NULL;
         $this->coddivisa = NULL;
         $this->codpago = NULL;
         $this->observaciones = '';
      }
   }
   
   public function url()
   {
      return "index.php?page=general_proveedor&cod=".$this->codproveedor;
   }
   
   public function is_default()
   {
      if( isset(self::$default_proveedor) )
         return (self::$default_proveedor == $this->codproveedor);
      else if( !isset($_COOKIE['default_proveedor']) )
         return FALSE;
      else if($_COOKIE['default_proveedor'] == $this->codproveedor)
         return TRUE;
      else
         return FALSE;
   }
   
   public function set_default()
   {
      setcookie('default_proveedor', $this->codproveedor, time()+FS_COOKIES_EXPIRE);
      self::$default_proveedor = $this->codproveedor;
   }
   
   public function get_new_codigo()
   {
      $cod = $this->db->select("SELECT MAX(codproveedor::integer) as cod FROM ".$this->table_name.";");
      if($cod)
         return sprintf('%06s', (1 + intval($cod[0]['cod'])));
      else
         return '000001';
   }
   
   public function get_albaranes($offset=0)
   {
      $alb = new albaran_proveedor();
      return $alb->all_from_proveedor($this->codproveedor, $offset);
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      if( is_null($this->codproveedor) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codproveedor = '".$this->codproveedor."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).",
            nombrecomercial = ".$this->var2str($this->nombrecomercial).", cifnif = ".$this->var2str($this->cifnif).",
            telefono1 = ".$this->var2str($this->telefono1).", telefono2 = ".$this->var2str($this->telefono2).",
            fax = ".$this->var2str($this->fax).", email = ".$this->var2str($this->email).",
            web = ".$this->var2str($this->web).", codserie = ".$this->var2str($this->codserie).",
            coddivisa = ".$this->var2str($this->coddivisa).", codpago = ".$this->var2str($this->codpago).",
            observaciones = ".$this->var2str($this->observaciones)." WHERE codproveedor = '".$this->codproveedor."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codproveedor,nombre,nombrecomercial,cifnif,telefono1,telefono2,
            fax,email,web,codserie,coddivisa,codpago,observaciones) VALUES ('".$this->codproveedor."',
            ".$this->var2str($this->nombre).",".$this->var2str($this->nombrecomercial).",".$this->var2str($this->cifnif).",
            ".$this->var2str($this->telefono1).",".$this->var2str($this->telefono2).",".$this->var2str($this->fax).",
            ".$this->var2str($this->email).",".$this->var2str($this->web).",".$this->var2str($this->codserie).",
            ".$this->var2str($this->coddivisa).",".$this->var2str($this->codpago).",".$this->var2str($this->observaciones).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codproveedor = '".$this->codproveedor."';");
   }
   
   public function get($cod)
   {
      $prov = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codproveedor = '".$cod."';");
      if($prov)
         return new proveedor($prov[0]);
      else
         return FALSE;
   }
   
   public function all($offset=0)
   {
      $provelist = array();
      $proveedores = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY nombre ASC",
                                             FS_ITEM_LIMIT, $offset);
      if($proveedores)
      {
         foreach($proveedores as $p)
            $provelist[] = new proveedor($p);
      }
      return $provelist;
   }
   
   public function all_full()
   {
      $provelist = array();
      $proveedores = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY nombre ASC;");
      if($proveedores)
      {
         foreach($proveedores as $p)
            $provelist[] = new proveedor($p);
      }
      return $provelist;
   }
   
   public function search($query, $offset=0)
   {
      $prolist = array();
      $query = strtolower($query);
      $proveedores = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE codproveedor ~~ '%".$query."%'
         OR lower(nombre) ~~ '%".$query."%' OR lower(nombrecomercial) ~~ '%".$query."%' ORDER BY nombre ASC", FS_ITEM_LIMIT, $offset);
      if($proveedores)
      {
         foreach($proveedores as $p)
            $prolist[] = new proveedor($p);
      }
      return $prolist;
   }
}

?>
