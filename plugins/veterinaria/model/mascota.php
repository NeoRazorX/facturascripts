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
require_model('cliente.php');

class mascota extends fs_model
{
   public $nombre;
   public $cod_mascota;
   public $chip;
   public $pasaporte;
   public $fecha_nac;
   public $fecha_alta;
   public $sexo;
   public $raza;
   public $especie;
   public $color;
   public $esterilizado;
   public $fecha_esterilizado;
   public $altura;
   public $cod_cliente;
   
   public function __construct($m=FALSE)
   {
      parent::__construct('fbm_mascotas', 'plugins/veterinaria/');
      if($m)
      {
         $this->cod_mascota = $m['cod_mascota'];
         $this->nombre = $m['nombre'];
         $this->chip = $m['chip'];
         $this->pasaporte = $m['pasaporte'];
         $this->fecha_nac = $m['fecha_nac'];
         $this->fecha_alta = Date('d-m-Y');
         $this->sexo = $m['sexo'];
         $this->raza = $m['raza'];
         $this->especie = $m['especie'];
         $this->color = $m['color'];
         $this->esterilizado = $m['esterilizado'];
         $this->fecha_esterilizado = $m['fecha_esterilizado'];
         $this->altura = $m['altura'];
         $this->cod_cliente = $m['cod_cliente'];
      }
      else
      {
         $this->cod_mascota = NULL;
         $this->nombre = NULL;
         $this->chip = NULL;
         $this->pasaporte = NULL;
         $this->fecha_nac = Date('d-m-Y');
         $this->fecha_alta = Date('d-m-Y');
         $this->sexo = NULL;
         $this->raza = NULL;
         $this->especie = NULL;
         $this->color = NULL;
         $this->esterilizado = NULL;
         $this->fecha_esterilizado = NULL;
         $this->altura = NULL;
         $this->cod_cliente = NULL;
      }
   }

   protected function install()
   {
      return '';
   }
   
   public function url()
   {
      return 'index.php?page=veterinaria_mascotas';
   }
   
   public function nombre_cliente()
   {
      $cliente = new cliente();
      $cli0 = $cliente->get($this->cod_cliente);
      if($cli0)
         return $cli0->nombrecomercial;
      else
         return '-';
   }
   
   public function get($cod_mascota)
   {
      $mascotas = $this->db->select("SELECT * FROM ".$this->table_name." WHERE cod_mascota = ".$this->var2str($cod_mascota).";");
      if($mascotas)
         return new mascota($mascotas[0]);
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->cod_mascota) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE cod_mascota = ".$this->var2str($this->cod_mascota).";");
   }
   
   public function test()
   {
      return TRUE;
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET nombre = ".$this->var2str($this->nombre).",
            chip = ".$this->var2str($this->chip).", pasaporte = ".$this->var2str($this->pasaporte).",
            fecha_nac = ".$this->var2str($this->fecha_nac).", sexo = ".$this->var2str($this->sexo).",
            raza = ".$this->var2str($this->raza).",
            especie = ".$this->var2str($this->especie).", color = ".$this->var2str($this->color).",
            esterilizado = ".$this->var2str($this->esterilizado).",
            fecha_esterilizado = ".$this->var2str($this->fecha_esterilizado).",
            altura = ".$this->var2str($this->altura)."
            WHERE cod_mascota = ".$this->var2str($this->cod_mascota).";";
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (nombre,chip,pasaporte,fecha_nac,
            fecha_alta,sexo,raza,especie,color,esterilizado,fecha_esterilizado,altura,cod_cliente)
            VALUES (".$this->var2str($this->nombre).",".$this->var2str($this->chip).",
            ".$this->var2str($this->pasaporte).",".$this->var2str($this->fecha_nac).",
            ".$this->var2str($this->fecha_alta).",".$this->var2str($this->sexo).",
            ".$this->var2str($this->raza).",".$this->var2str($this->especie).",".$this->var2str($this->color).",
            ".$this->var2str($this->esterilizado).",".$this->var2str($this->fecha_esterilizado).",
            ".$this->var2str($this->altura).",".$this->var2str($this->cod_cliente).");";
      }
      
      return $this->db->exec($sql);
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE cod_mascota = ".$this->var2str($this->cod_mascota).";");
   }
   
   public function all()
   {
      $listam = array();
      
      $mascotas = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY nombre ASC;");
      if($mascotas)
      {
         foreach($mascotas as $m)
            $listam[] = new mascota($m);
      }
      
      return $listam;
   }
   
}

?>