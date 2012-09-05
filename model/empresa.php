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
   public $ciudad;
   public $codpostal;
   public $direccion;
   public $administrador;
   public $codedi;
   public $cifnif;
   public $nombre;
   public $lema;
   public $horario;
   
   public function __construct()
   {
      parent::__construct('empresa');
      
      $e = $this->db->select("SELECT * FROM ".$this->table_name.";");
      if($e)
      {
         $this->id = $this->intval($e[0]['id']);
         $this->stockpedidos = ($e[0]['stockpedidos'] == 't');
         $this->contintegrada = ($e[0]['contintegrada'] == 't');
         $this->recequivalencia = ($e[0]['recequivalencia'] == 't');
         $this->codserie = $e[0]['codserie'];
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
         $this->ciudad = $e[0]['ciudad'];
         $this->codpostal = $e[0]['codpostal'];
         $this->direccion = $e[0]['direccion'];
         $this->administrador = $e[0]['administrador'];
         $this->codedi = $e[0]['codedi'];
         $this->cifnif = $e[0]['cifnif'];
         $this->nombre = $e[0]['nombre'];
         $this->lema = $e[0]['lema'];
         $this->horario = $e[0]['horario'];
      }
      else
      {
         $this->id = NULL;
         $this->stockpedidos = FALSE;
         $this->contintegrada = FALSE;
         $this->recequivalencia = FALSE;
         $this->codserie = NULL;
         $this->codalmacen = NULL;
         $this->codpago = NULL;
         $this->coddivisa = NULL;
         $this->codejercicio = NULL;
         $this->web = "http://code.google.com/p/facturascripts/";
         $this->email = '';
         $this->fax = '';
         $this->telefono = '';
         $this->codpais = NULL;
         $this->apartado = '';
         $this->provincia = NULL;
         $this->ciudad = NULL;
         $this->codpostal = NULL;
         $this->direccion = '';
         $this->administrador = '';
         $this->codedi = NULL;
         $this->cifnif = '';
         $this->nombre = '';
         $this->lema = '';
         $this->horario = '';
      }
   }
   
   public function url()
   {
      return 'index.php?page=admin_empresa';
   }

   protected function install()
   {
      
      return "INSERT INTO ".$this->table_name." (stockpedidos,contintegrada,recequivalencia,codserie,codalmacen,
         codpago,coddivisa,codejercicio,web,email,fax,telefono,codpais,apartado,provincia,ciudad,codpostal,
         direccion,administrador,codedi,cifnif,nombre,lema,horario) VALUES (NULL,FALSE,NULL,NULL,NULL,NULL,NULL,NULL,
         'http://code.google.com/p/facturascripts/',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'','',NULL,'','Empresa S.L.','','');";
   }
   
   public function exists()
   {
      if(is_null($this->id) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id='".$this->id."';");
   }
   
   public function save()
   {
      $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).", cifnif = ".$this->var2str($this->cifnif).",
         codedi = ".$this->var2str($this->codedi).", administrador = ".$this->var2str($this->administrador).",
         direccion = ".$this->var2str($this->direccion).",
         codpostal = ".$this->var2str($this->codpostal).", ciudad = ".$this->var2str($this->ciudad).",
         provincia = ".$this->var2str($this->provincia).",
         apartado = ".$this->var2str($this->apartado).",
         codpais = ".$this->var2str($this->codpais).", telefono = ".$this->var2str($this->telefono).", fax = ".$this->var2str($this->fax).",
         email = ".$this->var2str($this->email).", web = ".$this->var2str($this->web).", codejercicio = ".$this->var2str($this->codejercicio).",
         coddivisa = ".$this->var2str($this->coddivisa).", codpago = ".$this->var2str($this->codpago).",
         codalmacen = ".$this->var2str($this->codalmacen).",
         codserie = ".$this->var2str($this->codserie).",
         recequivalencia = ".$this->var2str($this->recequivalencia).",
         contintegrada = ".$this->var2str($this->contintegrada).", stockpedidos = ".$this->var2str($this->stockpedidos).",
         lema = ".$this->var2str($this->lema).", horario = ".$this->var2str($this->horario)."
         WHERE id = '".$this->id."';";
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE ".$this->table_name." WHERE id = '".$this->id."';");
   }
}

?>
