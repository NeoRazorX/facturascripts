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
 * Un concepto predefinido para una partida (la línea de un asiento contable).
 */
class concepto_partida extends fs_model
{
   public $idconceptopar;
   public $concepto;
   
   public function __construct($c = FALSE)
   {
      parent::__construct('co_conceptospar');
      if($c)
      {
         $this->idconceptopar = $c['idconceptopar'];
         $this->concepto = $c['concepto'];
      }
      else
      {
         $this->idconceptopar = NULL;
         $this->concepto = NULL;
      }
   }
   
   protected function install()
   {
      return "";
   }
   
   public function get($id)
   {
      $concepto = $this->db->select("SELECT * FROM ".$this->table_name." WHERE idconceptopar = ".$this->var2str($id).";");
      if($concepto)
         return new concepto_partida($concepto[0]);
      else
         return FALSE;
   }

   public function exists()
   {
      if( is_null($this->idconceptopar) )
         return FALSE;
      else
         return $this->db->select("SELECT * FROM ".$this->table_name."
            WHERE idconceptopar = ".$this->var2str($this->idconceptopar).";");
   }
   
   public function test()
   {
      $this->concepto = $this->no_html($this->concepto);
      return TRUE;
   }
   
   public function save()
   {
      return FALSE;
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE idconceptopar = ".$this->var2str($this->idconceptopar).";");
   }
   
   public function all()
   {
      $concelist = array();
      $conceptos = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY idconceptopar ASC;");
      if($conceptos)
      {
         foreach($conceptos as $c)
            $concelist[] = new concepto_partida($c);
      }
      return $concelist;
   }
}
