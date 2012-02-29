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

class empresa extends fs_model
{
   public $id;
   public $stockpedidos;
   public $contintegrada;
   public $recequivalencia;
   public $codserie;
   public $codcuentarem;
   public $codalmacen;
   public $codpago;
   public $coddivisa;
   public $codejercicio;
   public $web;
   public $email;
   public $fax;
   public $telefono;
   public $codpais;
   public $apartado;
   public $provincia;
   public $idprovincia;
   public $ciudad;
   public $codpostal;
   public $logo;
   public $direccion;
   public $administrador;
   public $codedi;
   public $cifnif;
   public $nombre;
   
   public function __construct()
   {
      parent::__construct('empresa');
      
      $e = $this->db->select("SELECT * FROM ".$this->table_name.";");
      if($e)
      {
         $this->id = $e[0]['id'];
         $this->stockpedidos = $e[0]['stockpedidos'];
         $this->contintegrada = $e[0]['contintegrada'];
         $this->recequivalencia = $e[0]['recequivalencia'];
         $this->codserie = $e[0]['codserie'];
         $this->codcuentarem = $e[0]['codcuentarem'];
         $this->codalmacen = $e[0]['codalmacen'];
         $this->codpago = $e[0]['codpago'];
         $this->coddivisa = $e[0]['coddivisa'];
         $this->codejercicio = $e[0]['codejercicio'];
         $this->web = $e[0]['web'];
         $this->email = $e[0]['email'];
         $this->fax = $e[0]['fax'];
         $this->telefono = $e[0]['telefono'];
         $this->codpais = $e[0]['codpais'];
         $this->apartado = $e[0]['apartado'];
         $this->provincia = $e[0]['provincia'];
         $this->idprovincia = $e[0]['idprovincia'];
         $this->ciudad = $e[0]['ciudad'];
         $this->codpostal = $e[0]['codpostal'];
         $this->logo = $e[0]['logo'];
         $this->direccion = $e[0]['direccion'];
         $this->administrador = $e[0]['administrador'];
         $this->codedi = $e[0]['codedi'];
         $this->cifnif = $e[0]['cifnif'];
         $this->nombre = $e[0]['nombre'];
      }
      else
      {
         $this->id = '';
         $this->stockpedidos = NULL;
         $this->contintegrada = NULL;
         $this->recequivalencia = NULL;
         $this->codserie = NULL;
         $this->codcuentarem = NULL;
         $this->codalmacen = NULL;
         $this->codpago = NULL;
         $this->coddivisa = NULL;
         $this->codejercicio = NULL;
         $this->web = "http://";
         $this->email = '';
         $this->fax = '';
         $this->telefono = '';
         $this->codpais = NULL;
         $this->apartado = '';
         $this->provincia = NULL;
         $this->idprovincia = NULL;
         $this->ciudad = NULL;
         $this->codpostal = NULL;
         $this->logo = NULL;
         $this->direccion = '';
         $this->administrador = '';
         $this->codedi = NULL;
         $this->cifnif = '';
         $this->nombre = '';
      }
   }

   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id='".$this->id."';");
   }
   
   public function save()
   {
      $sql = "UPDATE ".$this->table_name." SET nombre = '".$this->nombre."', cifnif = '".$this->cifnif."',
              administrador = '".$this->administrador."', direccion = '".$this->direccion."',
              ciudad = '".$this->ciudad."', codpostal = '".$this->codpostal."', telefono = '".$this->telefono."',
              fax = '".$this->fax."', web = '".$this->web."', email = '".$this->email."'
              WHERE id = '".$this->id."';";
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE ".$this->table_name." WHERE id = '".$this->id."';");
   }
}

?>
