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

/**
 * Periodos.
 */
class periodo extends fs_model
{
   public $codperiodo;
   public $descripcion;
   public $cadencia;

   public function __construct($g=FALSE)
   {
      parent::__construct('periodos', 'plugins/recibos_para_pagos_y_cobros/');
      if( $g )
      {
         $this->codperiodo = $g['codperiodo'];
         $this->descripcion = $g['descripcion'];
         $this->cadencia = $g['cadencia'];
      }
      else
      {
         $this->codperiodo = NULL;
         $this->descripcion = '';
         $this->cadencia = 0;
      }
   }
   
   protected function install()
   {
      $this->clean_cache();
      return "INSERT INTO periodos (codperiodo,descripcion,cadencia) VALUES
            ('DIA','Diario',1),
            ('SEMANA','Semanal',7),
            ('QUINCENA','Quincenal',15),
            ('MES','Mensual',30),
            ('BIMES','Bimensual',60),
            ('TRIMES','Trimestral',90),
            ('CUAMES','Cuatrimestral',120),
            ('SEMES','Semestral',180),
            ('ANUAL','Anual',365);";
   }
   
   public function url()
   {
      return 'index.php?page=periodos';
   }
   
   public function is_default()
   {
      return ( $this->codperiodo == $this->default_items->codperiodo() );
   }
   
   public function get($cod)
   {
      $periodo = $this->db->select("SELECT * FROM periodos WHERE codperiodo = ".$this->var2str($cod).";");
      if($periodo)
         return new periodo($periodo[0]);
      else
         return FALSE;
   }

   public function exists()
   {
      if( is_null($this->codperiodo) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM periodos
            WHERE codperiodo = ".$this->var2str($this->codperiodo).";");
   }
   
   public function test()
   {
      $this->descripcion = $this->no_html($this->descripcion);
      return TRUE;
   }
   
   public function save()
   {
      if( $this->test() )
      {
         $this->clean_cache();
         
         if( $this->exists() )
         {
            $sql = "UPDATE periodos SET descripcion = ".$this->var2str($this->descripcion).",
               cadencia = ".$this->var2str($this->cadencia)." WHERE codperiodo = ".$this->var2str($this->codperiodo).";";
         }
         else
         {
            $sql = "INSERT INTO periodos (codperiodo,descripcion,cadencia) VALUES
               (".$this->var2str($this->codperiodo).",".$this->var2str($this->descripcion).",
               ".$this->var2str($this->cadencia).");";
         }
         
         return $this->db->exec($sql);
      }
      else
         return FALSE;
   }
   
   public function delete()
   {
      $this->clean_cache();
      return $this->db->exec("DELETE FROM periodos WHERE codperiodo = ".$this->var2str($this->codperiodo).";");
   }
   
   private function clean_cache()
   {
      $this->cache->delete('m_periodo_all');
   }
   
   public function all()
   {
      $listaperiodos = $this->cache->get_array('m_periodo_all');
      if( !$listaperiodos )
      {
         $periodos = $this->db->select("SELECT * FROM periodos ORDER BY cadencia;");
         if($periodos)
         {
            foreach($periodos as $p)
               $listaperiodos[] = new periodo($p);
         }
         $this->cache->set('m_periodo_all', $listaperiodos);
      }
      return $listaperiodos;
   }
}
