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
require_model('fbm_mascota.php');

class fbm_analisis extends fs_model
{
   public $id;
   public $idmascota;
   public $idtipo;
   public $tipo;
   public $nombre;
   public $fecha;
   public $resultado;
   public $notas;
   public $nueva_fecha;
   
   public function __construct($a = FALSE)
   {
      parent::__construct('fbm_analisis', 'plugins/veterinaria/');
      if($a)
      {
         $this->id = $this->intval($a['id']);
         $this->idmascota = $this->intval($a['idmascota']);
         $this->idtipo = $this->intval($a['idtipo']);
         $this->tipo = $a['tipo'];
         $this->nombre = $a['nombre'];
         $this->fecha = date('d-m-Y', strtotime($a['fecha']) );
         $this->resultado = $a['resultado'];
         $this->notas = $a['notas'];
         $this->nueva_fecha = date('d-m-Y', strtotime($a['nueva_fecha']) );
      }
      else
      {
         $this->id = NULL;
         $this->idmascota = NULL;
         $this->idtipo = NULL;
         $this->tipo = NULL;
         $this->nombre = NULL;
         $this->fecha = date('d-m-Y');
         $this->resultado = '';
         $this->notas = '';
         $this->nueva_fecha = date('d-m-Y');
      }
   }
   
   protected function install()
   {
      /// forzamos la comprobación de mascota
      new fbm_mascota();
      
      return '';
   }
   
   public function url()
   {
      return 'index.php?page=veterinaria_analisis&id='.$this->id;
   }
   
   public function tipo()
   {
      if($this->tipo == 'desparas')
      {
         return 'Desparasitaciones';
      }
      else if($this->tipo == 'analitica')
      {
         return 'Analítica';
      }
      else
         return ucfirst($this->tipo);
   }
   
   public function notas($num = 90)
   {
      if( strlen($this->notas) > $num )
      {
         return substr($this->notas, 0, $num-3).'...';
      }
      else
         return $this->notas;
   }
   
   public function get($id)
   {
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($id).";");
      if($data)
      {
         return new fbm_analisis($data[0]);
      }
      else
         return FALSE;
   }
   
   public function exists()
   {
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
         return $this->db->select("SELECT * FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function save()
   {
      if( $this->exists() )
      {
         $sql = "UPDATE ".$this->table_name." SET idmascota = ".$this->var2str($this->idmascota).",
            idtipo = ".$this->var2str($this->idtipo).", tipo = ".$this->var2str($this->tipo).",
            nombre = ".$this->var2str($this->nombre).",
            fecha = ".$this->var2str($this->fecha).", resultado = ".$this->var2str($this->resultado).",
            notas = ".$this->var2str($this->notas).", nueva_fecha = ".$this->var2str($this->nueva_fecha)."
            WHERE id = ".$this->var2str($this->id).";";
         
         return $this->db->exec($sql);
      }
      else
      {
         $sql = "INSERT INTO ".$this->table_name." (idmascota,idtipo,tipo,nombre,fecha,resultado,notas,nueva_fecha) VALUES
            (".$this->var2str($this->idmascota).",".$this->var2str($this->idtipo).",".$this->var2str($this->tipo).",
            ".$this->var2str($this->nombre).",".$this->var2str($this->fecha).",".$this->var2str($this->resultado).",
            ".$this->var2str($this->notas).",".$this->var2str($this->nueva_fecha).");";
         
         if( $this->db->exec($sql) )
         {
            $this->id = $this->db->lastval();
            return TRUE;
         }
         else
            return FALSE;
      }
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE id = ".$this->var2str($this->id).";");
   }
   
   public function all_from($idmascota, $tipo)
   {
      $lista = array();
      
      $data = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idmascota = ".$this->var2str($idmascota)." AND tipo = ".$this->var2str($tipo).";");
      if($data)
      {
         foreach($data as $d)
            $lista[] = new fbm_analisis($d);
      }
      
      return $lista;
   }
}
