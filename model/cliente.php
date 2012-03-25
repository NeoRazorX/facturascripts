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

class cliente extends fs_model
{
   public $codcliente;
   public $nombre;
   public $nombrecomercial;
   public $cifnif;
   public $telefono1;
   public $telefono2;
   public $fax;
   public $email;
   public $web;
   public $codserie;
   public $debaja;
   public $fechabaja;

   public function __construct($c=FALSE)
   {
      parent::__construct('clientes');
      if($c)
      {
         $this->codcliente = $c['codcliente'];
         $this->nombre = $c['nombre'];
         $this->nombrecomercial = $c['nombrecomercial'];
         $this->cifnif = $c['cifnif'];
         $this->telefono1 = $c['telefono1'];
         $this->telefono2 = $c['telefono2'];
         $this->fax = $c['fax'];
         $this->email = $c['email'];
         $this->web = $c['web'];
         $this->codserie = $c['codserie'];
         $this->debaja = ($c['debaja'] == 't');
         $this->fechabaja = $c['fechabaja'];
      }
      else
      {
         $this->codcliente = NULL;
         $this->nombre = '';
         $this->nombrecomercial = '';
         $this->cifnif = '';
         $this->telefono1 = '';
         $this->telefono2 = '';
         $this->fax = '';
         $this->email = '';
         $this->web = '';
         $this->codserie = NULL;
         $this->debaja = FALSE;
         $this->fechabaja = NULL;
      }
   }
   
   public function get_new_codigo()
   {
      $cod = $this->db->select("SELECT MAX(codcliente::integer) as cod FROM ".$this->table_name.";");
      if($cod)
         return sprintf('%06s', (1 + intval($cod[0]['cod'])));
      else
         return '000001';
   }
   
   public function url()
   {
      if( isset($this->codcliente) )
         return "index.php?page=general_cliente&cod=".$this->codcliente;
      else
         return "index.php?page=general_clientes";
   }

   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codcliente = '".$this->codcliente."';");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).",
            nombrecomercial = ".$this->var2str($this->nombrecomercial).", cifnif = ".$this->var2str($this->cifnif).",
            codserie = ".$this->var2str($this->codserie).", debaja = ".$this->var2str($this->debaja).",
            fechabaja = ".$this->var2str($this->fechabaja)." WHERE codcliente = '".$this->codcliente."';";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (codcliente,nombre,nombrecomercial,cifnif,codserie,debaja)
            VALUES (".$this->var2str($this->codcliente).",".$this->var2str($this->nombre).",".$this->var2str($this->nombrecomercial).",
            ".$this->var2str($this->cifnif).",".$this->var2str($this->codserie).",".$this->var2str($this->debaja).");";
      }
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codcliente = '".$this->codcliente."';");
   }
   
   public function get($cod)
   {
      $cli = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codcliente = '".$cod."';");
      if($cli)
         return new cliente($cli[0]);
      else
         return FALSE;
   }
   
   public function all($offset=0)
   {
      $clientlist = array();
      $clientes = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY codcliente ASC",
                                          FS_ITEM_LIMIT, $offset);
      if($clientes)
      {
         foreach($clientes as $c)
         {
            $clientlist[] = new cliente($c);
         }
      }
      return $clientlist;
   }
}

?>
