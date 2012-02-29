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
require_once 'model/familia.php';

class articulo extends fs_model
{
   public $referencia;
   public $codfamilia;
   public $descripcion;
   public $pvp;
   public $pvp_ant;
   public $factualizado;
   public $destacado;
   public $bloqueado;
   public $secompra;
   public $sevende;
   public $equivalencia;
   public $stockfis;
   public $stockmin;
   public $stockmax;
   public $controlstock;
   public $codbarras;
   public $observaciones;

   public function __construct($a=FALSE)
   {
      parent::__construct('articulos');
      if($a)
      {
         $this->referencia = $a['referencia'];
         $this->codfamilia = $a['codfamilia'];
         $this->descripcion = $a['descripcion'];
         $this->pvp = floatval($a['pvp']);
         $this->factualizado = $a['factualizado'];
         $this->stockfis = intval($a['stockfis']);
         $this->stockmin = intval($a['stockmin']);
         $this->stockmax = intval($a['stockmax']);
         $this->controlstock = ($a['controlstock'] == 't');
         $this->destacado = ($a['destacado'] == 't');
         $this->bloqueado = ($a['bloqueado'] == 't');
         $this->secompra = ($a['secompra'] == 't');
         $this->sevende = ($a['sevende'] == 't');
         $this->equivalencia = $a['equivalencia'];
         $this->codbarras = $a['codbarras'];
         $this->observaciones = $a['observaciones'];
      }
      else
      {
         $this->referencia = '';
         $this->codfamilia = NULL;
         $this->descripcion = '';
         $this->pvp = 0;
         $this->factualizado = Date('j-n-Y');
         $this->stockfis = 0;
         $this->stockmin = 0;
         $this->stockmax = 0;
         $this->controlstock = FALSE;
         $this->destacado = FALSE;
         $this->bloqueado = FALSE;
         $this->secompra = TRUE;
         $this->sevende = TRUE;
         $this->equivalencia = NULL;
         $this->codbarras = '';
         $this->observaciones = '';
      }
      $this->pvp_ant = 0;
   }
   
   public function show_pvp()
   {
      return number_format($this->pvp, 2, ',', '.');
   }
   
   public function url()
   {
      return "index.php?page=general_articulo&ref=".$this->referencia;
   }
   
   public function get_familia()
   {
      $fam = new familia();
      return $fam->get($this->codfamilia);
   }


   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = '".$this->referencia."';");
   }
   
   public function save()
   {
      
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE referencia = '".$this->referencia."';");
   }
   
   public function get($ref)
   {
      $art = $this->db->select("SELECT * FROM ".$this->table_name." WHERE referencia = '".$ref."';");
      if($art)
         return new articulo($art[0]);
      else
         return FALSE;
   }
   
   public function search($text='', $familia='', $offset=0)
   {
      $artilist = array();
      $buscando = FALSE;
      $sql = "SELECT * FROM ".$this->table_name." ";
      if($familia != '')
      {
         $sql .= "WHERE codfamilia = '".$familia."'";
         $buscando = TRUE;
      }
      if($text != '')
      {
         if($buscando)
            $sql .= " AND ";
         else
            $sql .= "WHERE ";
         if( is_numeric($text) )
            $sql .= "(referencia ~~ '%".$text."%' OR codbarras = '".$text."' OR descripcion ~~ '%".$text."%')";
         else
            $sql .= "(upper(referencia) ~~ '%".strtoupper($text)."%' OR upper(descripcion) ~~ '%".strtoupper($text)."%')";
      }
      $sql .= " ORDER BY referencia ASC";
      $articulos = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
      if($articulos)
      {
         foreach($articulos as $a)
         {
            $ao = new articulo($a);
            $artilist[] = $ao;
         }
      }
      return $artilist;
   }
   
   public function all($offset=0)
   {
      $artilist = array();
      $articulos = $this->db->select_limit("SELECT * FROM ".$this->table_name." ORDER BY referencia ASC", FS_ITEM_LIMIT, $offset);
      if($articulos)
      {
         foreach($articulos as $a)
         {
            $ao = new articulo($a);
            $artilist[] = $ao;
         }
      }
      return $artilist;
   }
   
   public function all_from_familia($codfamilia, $offset=0)
   {
      $artilist = array();
      $articulos = $this->db->select_limit("SELECT * FROM ".$this->table_name." WHERE codfamilia = '".$codfamilia."' ORDER BY referencia ASC",
                                           FS_ITEM_LIMIT, $offset);
      if($articulos)
      {
         foreach($articulos as $a)
         {
            $ao = new articulo($a);
            $artilist[] = $ao;
         }
      }
      return $artilist;
   }
}

?>
