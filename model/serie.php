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

class serie extends fs_model
{
   public $codserie;
   public $descripcion;
   public $siniva;
   public $irpf;
   
   public function __construct($s=FALSE)
   {
      parent::__construct('series');
      if($s)
      {
         $this->codserie = $s['codserie'];
         $this->descripcion = $s['descripcion'];
         $this->siniva = $s['siniva'];
         $this->irpf = floatval($s['irpf']);
      }
      else
      {
         $this->codserie = '';
         $this->descripcion = '';
         $this->siniva = FALSE;
         $this->irpf = 0;
      }
   }
   
   protected function install()
   {
      return '';
   }
   
   public function exists()
   {
      return $this->db->select("SELECT * FROM ".$this->table_name." WHERE codserie = '".$this->codserie."';");
   }
   
   public function save()
   {
      
   }
   
   public function delete()
   {
      return $this->db->exec("DELETE FROM ".$this->table_name." WHERE codserie = '".$this->codserie."';");
   }
   
   public function all()
   {
      $serielist = array();
      $series = $this->db->select("SELECT * FROM ".$this->table_name." ORDER BY codserie ASC;");
      if($series)
      {
         foreach($series as $s)
         {
            $so = new serie($s);
            $serielist[] = $so;
         }
      }
      return $serielist;
   }
}

?>
