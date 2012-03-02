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

class ejercicio extends fs_model
{
   public $idasientocierre;
   public $idasientopyg;
   public $idasientoapertura;
   public $plancontable;
   public $longsubcuenta;
   public $estado;
   public $fechafin;
   public $fechainicio;
   public $nombre;
   public $codejercicio;
   
   public function __construct($e = FALSE)
   {
      parent::__construct('ejercicios');
      if($e)
      {
         $this->idasientocierre = $e['idasientocierre'];
         $this->idasientopyg = $e['idasientopyg'];
         $this->idasientoapertura = $e['idasientoapertura'];
         $this->plancontable = $e['plancontable'];
         $this->longsubcuenta = $e['longsubcuenta'];
         $this->estado = $e['estado'];
         $this->fechafin = $e['fechafin'];
         $this->fechainicio = $e['fechainicio'];
         $this->nombre = $e['nombre'];
         $this->codejercicio = $e['codejercicio'];
      }
      else
      {
         $this->idasientocierre = NULL;
         $this->idasientopyg = NULL;
         $this->idasientoapertura = NULL;
         $this->plancontable = NULL;
         $this->longsubcuenta = NULL;
         $this->estado = NULL;
         $this->fechafin = NULL;
         $this->fechainicio = NULL;
         $this->nombre = '';
         $this->codejercicio = NULL;
      }
   }
   
   public function url()
   {
      return 'index.php?page=contabilidad_ejercicios#'.$this->codejercicio;
   }

   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      
   }
   
   public function save()
   {
      
   }
   
   public function delete()
   {
      
   }
   
   public function all()
   {
      $listae = array();
      $ejercicios = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codejercicio DESC;");
      if($ejercicios)
      {
         foreach($ejercicios as $e)
         {
            $eo = new ejercicio($e);
            $listae[] = $eo;
         }
      }
      return $listae;
   }
   
   public function get($cod='')
   {
      $ejercicio = $this->db->select("SELECT * FROM ".$this->table_name." WHERE codejercicio = '".$this->codejercicio."';");
      if($ejercicio)
         return new ejercicio($ejercicio[0]);
      else
         return FALSE;
   }
}

?>
